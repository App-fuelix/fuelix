<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AiInsightService
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly FirestoreUserService $firestoreUsers,
    ) {
    }

    public function forUser(User $user): array
    {
        $firestoreUser = $this->firestoreUsers->findByEmail($user->email);
        $uid = $firestoreUser['id'] ?? null;

        if (!$uid) {
            return $this->emptyInsights();
        }

        $transactions = $this->firestore->subList('users', $uid, 'transactions');
        $vehicles = $this->firestore->subList('users', $uid, 'vehicles');

        return $this->fromPreparedData($transactions, $vehicles);
    }

    public function fromPreparedData(array $transactions, array $vehicles = []): array
    {
        $payload = [
            'transactions' => array_map(fn ($transaction) => $this->normalizeTransaction($transaction), $transactions),
            'vehicles' => array_map(fn ($vehicle) => $this->normalizeVehicle($vehicle), $vehicles),
        ];

        try {
            $response = Http::timeout(6)
                ->acceptJson()
                ->post(rtrim((string) config('services.ml.url'), '/') . '/insights', $payload);

            if ($response->successful() && is_array($response->json())) {
                return $response->json();
            }
        } catch (\Throwable) {
            // Keep the app usable if the local ML service is offline.
        }

        return $this->fallbackInsights($payload['transactions']);
    }

    public function dashboardInsight(array $transactions, array $vehicles = []): array
    {
        $insights = $this->fromPreparedData($transactions, $vehicles);
        return $insights['dashboard_insight'] ?? [
            'text' => 'No data yet. Start tracking your fuel!',
            'variation' => '0%',
            'period' => 'this month',
        ];
    }

    private function normalizeTransaction(array $transaction): array
    {
        return [
            'id' => (string) ($transaction['id'] ?? ''),
            'date' => (string) ($transaction['date'] ?? now()->toIso8601String()),
            'amount' => (float) ($transaction['amount'] ?? 0),
            'quantity_liters' => (float) ($transaction['quantity_liters'] ?? 0),
            'price_per_liter' => (float) ($transaction['price_per_liter'] ?? 0),
            'station_name' => (string) ($transaction['station_name'] ?? ''),
            'vehicle_id' => (string) ($transaction['vehicle_id'] ?? ''),
            'fuel_card_id' => (string) ($transaction['fuel_card_id'] ?? ''),
        ];
    }

    private function normalizeVehicle(array $vehicle): array
    {
        return [
            'id' => (string) ($vehicle['id'] ?? ''),
            'model' => (string) ($vehicle['model'] ?? ''),
            'fuel_type' => (string) ($vehicle['fuel_type'] ?? ''),
            'average_consumption' => (float) ($vehicle['average_consumption'] ?? 0),
        ];
    }

    private function fallbackInsights(array $transactions): array
    {
        if (empty($transactions)) {
            return $this->emptyInsights();
        }

        $monthly = [];
        foreach ($transactions as $transaction) {
            try {
                $month = Carbon::parse($transaction['date'] ?? now())->format('Y-m');
            } catch (\Throwable) {
                continue;
            }

            $monthly[$month] ??= [
                'month' => $month,
                'total_liters' => 0,
                'total_cost' => 0,
                'transaction_count' => 0,
                'avg_price' => 0,
            ];

            $monthly[$month]['total_liters'] += (float) ($transaction['quantity_liters'] ?? 0);
            $monthly[$month]['total_cost'] += (float) ($transaction['amount'] ?? 0);
            $monthly[$month]['transaction_count']++;
        }

        ksort($monthly);
        $monthly = array_values(array_map(function (array $row) {
            $row['total_liters'] = round($row['total_liters'], 2);
            $row['total_cost'] = round($row['total_cost'], 2);
            $row['avg_price'] = $row['total_liters'] > 0 ? round($row['total_cost'] / $row['total_liters'], 3) : 0;
            return $row;
        }, $monthly));

        $dashboard = $this->buildDashboardFallback($monthly);
        $lastMonth = end($monthly) ?: ['total_liters' => 0, 'total_cost' => 0];

        return [
            'generated_at' => now()->toIso8601String(),
            'transaction_count' => count($transactions),
            'prediction' => [
                'predicted_monthly_liters' => round((float) $lastMonth['total_liters'], 2),
                'estimated_monthly_cost_tnd' => round((float) $lastMonth['total_cost'], 2),
                'model_metrics' => ['source' => 'laravel_fallback'],
            ],
            'monthly_comparison' => array_slice($monthly, -6),
            'anomalies' => [],
            'recommendations' => [
                'Vos recommandations seront plus précises après l’analyse complète de votre historique.',
            ],
            'dashboard_insight' => $dashboard,
        ];
    }

    private function buildDashboardFallback(array $monthly): array
    {
        if (count($monthly) < 2) {
            return [
                'text' => 'Pas encore assez de transactions pour comparer les tendances.',
                'variation' => '0%',
                'period' => 'this month',
            ];
        }

        $current = (float) $monthly[array_key_last($monthly)]['total_liters'];
        $previous = (float) $monthly[array_key_last($monthly) - 1]['total_liters'];
        $variation = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
        $sign = $variation >= 0 ? '+' : '';
        $direction = $variation >= 0 ? 'augmente' : 'diminue';

        return [
            'text' => sprintf('Votre consommation %s de %.1f%% par rapport au mois precedent.', $direction, abs($variation)),
            'variation' => sprintf('%s%.1f%%', $sign, $variation),
            'period' => 'this month',
        ];
    }

    private function emptyInsights(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'transaction_count' => 0,
            'prediction' => [
                'predicted_monthly_liters' => 0,
                'estimated_monthly_cost_tnd' => 0,
                'model_metrics' => [],
            ],
            'monthly_comparison' => [],
            'anomalies' => [],
            'recommendations' => [
                'Ajoutez des transactions pour recevoir des recommandations personnalisees.',
            ],
            'dashboard_insight' => [
                'text' => 'No data yet. Start tracking your fuel!',
                'variation' => '0%',
                'period' => 'this month',
            ],
        ];
    }
}
