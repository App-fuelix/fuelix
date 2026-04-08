<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource; // On passe l'User ici

        // 1. Total Consumption (litres cumulés)
        $totalConsumption = $user->transactions()->sum('quantity_liters') ?? 0;

        // 2. Total Cost
        $totalCost = $user->transactions()
            ->sum(DB::raw('quantity_liters * price_per_liter')) ?? 0;

        // 3. Average per Vehicle
        $vehicleCount = $user->vehicles()->count();
        $avgPerVehicle = $vehicleCount > 0 ? round($totalConsumption / $vehicleCount, 1) : 0;

        // 4. Monthly Trend % (vs mois précédent)
        $currentMonthLiters = $user->transactions()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('quantity_liters');

        $previousMonthLiters = $user->transactions()
            ->whereMonth('date', now()->subMonth()->month)
            ->whereYear('date', now()->subMonth()->year)
            ->sum('quantity_liters');

        $monthlyTrendPercent = $previousMonthLiters > 0
            ? round((($currentMonthLiters - $previousMonthLiters) / $previousMonthLiters) * 100, 1)
            : 0;

        $monthlyTrendSign = $monthlyTrendPercent >= 0 ? '+' : '';

        // 5. Weekly Consumption (derniers 7 jours) pour le graphique
        $weeklyData = $user->transactions()
            ->where('date', '>=', now()->subDays(6))
            ->selectRaw("DATE(date) as day, SUM(quantity_liters) as liters")
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('liters', 'day')
            ->toArray();

        // Remplir les 7 jours même si pas de données (0)
        $weeklyConsumption = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayName = now()->subDays($i)->format('D'); // Mon, Tue...
            $weeklyConsumption[$dayName] = $weeklyData[$date] ?? 0;
        }

        // 6. AI Insight simple (exemple basique – à enrichir plus tard)
        $insightText = $monthlyTrendPercent > 0
            ? "Your fuel consumption increased by {$monthlyTrendPercent}% this month"
            : "Good job! Your fuel consumption decreased by " . abs($monthlyTrendPercent) . "% this month";

        // Variation weekly pour insight (optionnel)
        $weeklyChange = $this->getWeeklyChange($user);

        return [
            'ai_insight' => [
                'text' => $insightText,
                'variation' => $weeklyChange > 0 ? "+{$weeklyChange}%" : "{$weeklyChange}%",
                'period' => 'this week', // ou 'this month' selon besoin
            ],
            'total_consumption' => round($totalConsumption, 0) . 'L',
            'total_cost' => number_format($totalCost, 0, ',', ' ') . ' TND',
            'average_per_vehicle' => round($avgPerVehicle, 0) . 'L',
            'monthly_trend' => $monthlyTrendSign . $monthlyTrendPercent . '%',
            'weekly_consumption' => $weeklyConsumption, // { "Mon": 120, "Tue": 85, ... }
            'last_updated' => now()->toDateTimeString(),
        ];
    }

    private function getWeeklyChange($user): float
    {
        $thisWeek = $user->transactions()
            ->where('date', '>=', now()->startOfWeek())
            ->sum('quantity_liters');

        $lastWeek = $user->transactions()
            ->whereBetween('date', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->sum('quantity_liters');

        return $lastWeek > 0 ? round((($thisWeek - $lastWeek) / $lastWeek) * 100, 1) : 0;
    }
}