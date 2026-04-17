<?php

namespace App\Http\Resources;

use App\Services\FirestoreService;
use App\Services\FirestoreUserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource;

        /** @var FirestoreService $firestore */
        $firestore = app(FirestoreService::class);

        /** @var FirestoreUserService $firestoreUsers */
        $firestoreUsers = app(FirestoreUserService::class);

        $firestoreUser = $firestoreUsers->findByEmail($user->email);
        $uid = $firestoreUser['id'] ?? null;

        if (!$uid) {
            return $this->emptyDashboard();
        }

        // Load all transactions from Firestore subcollection
        $transactions = $firestore->subList('users', $uid, 'transactions');

        // Load vehicles count
        $vehicles = $firestore->subList('users', $uid, 'vehicles');
        $vehicleCount = count($vehicles);

        // 1. Total consumption
        $totalLiters = array_sum(array_map(fn($t) => (float)($t['quantity_liters'] ?? 0), $transactions));

        // 2. Total cost
        $totalCost = array_sum(array_map(fn($t) =>
            (float)($t['quantity_liters'] ?? 0) * (float)($t['price_per_liter'] ?? 0),
            $transactions
        ));

        // 3. Average per vehicle
        $avgPerVehicle = $vehicleCount > 0 ? round($totalLiters / $vehicleCount, 1) : 0;

        // 4. Monthly trend
        $currentMonth = now()->month;
        $currentYear  = now()->year;
        $prevMonth    = now()->subMonth()->month;
        $prevYear     = now()->subMonth()->year;

        $currentMonthLiters = array_sum(array_map(fn($t) => (float)($t['quantity_liters'] ?? 0),
            array_filter($transactions, fn($t) => $this->inMonth($t['date'] ?? '', $currentMonth, $currentYear))
        ));

        $prevMonthLiters = array_sum(array_map(fn($t) => (float)($t['quantity_liters'] ?? 0),
            array_filter($transactions, fn($t) => $this->inMonth($t['date'] ?? '', $prevMonth, $prevYear))
        ));

        $trendPercent = $prevMonthLiters > 0
            ? round((($currentMonthLiters - $prevMonthLiters) / $prevMonthLiters) * 100, 1)
            : 0;

        $trendSign = $trendPercent >= 0 ? '+' : '';

        // 5. Weekly consumption (last 7 days)
        $weeklyConsumption = [];
        for ($i = 6; $i >= 0; $i--) {
            $date    = now()->subDays($i)->format('Y-m-d');
            $dayName = now()->subDays($i)->format('D');
            $dayLiters = array_sum(array_map(fn($t) => (float)($t['quantity_liters'] ?? 0),
                array_filter($transactions, fn($t) => str_starts_with($t['date'] ?? '', $date))
            ));
            $weeklyConsumption[$dayName] = $dayLiters;
        }

        // 6. Weekly change for AI insight
        $thisWeekLiters = array_sum(array_map(fn($t) => (float)($t['quantity_liters'] ?? 0),
            array_filter($transactions, fn($t) => Carbon::parse($t['date'] ?? now())->gte(now()->startOfWeek()))
        ));

        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd   = now()->subWeek()->endOfWeek();
        $lastWeekLiters = array_sum(array_map(fn($t) => (float)($t['quantity_liters'] ?? 0),
            array_filter($transactions, function ($t) use ($lastWeekStart, $lastWeekEnd) {
                $d = Carbon::parse($t['date'] ?? now());
                return $d->gte($lastWeekStart) && $d->lte($lastWeekEnd);
            })
        ));

        $weeklyChange = $lastWeekLiters > 0
            ? round((($thisWeekLiters - $lastWeekLiters) / $lastWeekLiters) * 100, 1)
            : 0;

        $insightText = $trendPercent > 0
            ? "Your fuel consumption increased by {$trendPercent}% this month"
            : "Good job! Your fuel consumption decreased by " . abs($trendPercent) . "% this month";

        return [
            'ai_insight' => [
                'text'      => $insightText,
                'variation' => $weeklyChange >= 0 ? "+{$weeklyChange}%" : "{$weeklyChange}%",
                'period'    => 'this week',
            ],
            'total_consumption'  => round($totalLiters, 0) . 'L',
            'total_cost'         => number_format($totalCost, 0, ',', ' ') . ' TND',
            'average_per_vehicle' => round($avgPerVehicle, 0) . 'L',
            'monthly_trend'      => $trendSign . $trendPercent . '%',
            'weekly_consumption' => $weeklyConsumption,
            'last_updated'       => now()->toDateTimeString(),
        ];
    }

    private function inMonth(string $dateStr, int $month, int $year): bool
    {
        if ($dateStr === '') return false;
        try {
            $d = Carbon::parse($dateStr);
            return $d->month === $month && $d->year === $year;
        } catch (\Throwable) {
            return false;
        }
    }

    private function emptyDashboard(): array
    {
        $weekly = [];
        for ($i = 6; $i >= 0; $i--) {
            $weekly[now()->subDays($i)->format('D')] = 0;
        }

        return [
            'ai_insight'          => ['text' => 'No data yet. Start tracking your fuel!', 'variation' => '0%', 'period' => 'this week'],
            'total_consumption'   => '0L',
            'total_cost'          => '0 TND',
            'average_per_vehicle' => '0L',
            'monthly_trend'       => '0%',
            'weekly_consumption'  => $weekly,
            'last_updated'        => now()->toDateTimeString(),
        ];
    }
}
