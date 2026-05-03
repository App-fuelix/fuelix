@extends('layouts.app')

@section('title', 'Analytics')
@section('page-title', 'Analytics')
@section('page-subtitle', 'Fuel usage, spending, and operational patterns from Firestore')

@section('content')
@php
    $stats = $stats ?? ['users' => 0, 'transactions' => 0, 'consumption' => 0, 'expenses' => 0];
    $fuelLabels = $fuelLabels ?? [];
    $fuelData = $fuelData ?? [];
    $expenseLabels = $expenseLabels ?? [];
    $expenseData = $expenseData ?? [];
    $distributionData = $distributionData ?? [0, 0, 0];
    $topUserLabels = $topUserLabels ?? [];
    $topUserData = $topUserData ?? [];
    $hasFuelData = array_sum($fuelData) > 0;
    $hasExpenseData = array_sum($expenseData) > 0;
    $hasDistributionData = array_sum($distributionData) > 0;
    $hasTopUsers = array_sum($topUserData) > 0;
@endphp

@if (session('analytics_error'))
    <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
        {{ session('analytics_error') }}
    </div>
@endif

<div class="mb-6 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['label' => 'Client Users', 'value' => number_format($stats['users']), 'hint' => 'Non-admin accounts'],
        ['label' => 'Transactions', 'value' => number_format($stats['transactions']), 'hint' => 'Firestore records'],
        ['label' => 'Consumption', 'value' => number_format((float) $stats['consumption'], 1) . ' L', 'hint' => 'Total fuel volume'],
        ['label' => 'Expenses', 'value' => number_format((float) $stats['expenses'], 2) . ' TND', 'hint' => 'Total fuel amount'],
    ] as $item)
        <article class="rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
            <p class="text-xs text-slate-400">{{ $item['label'] }}</p>
            <h2 class="mt-3 text-2xl font-bold">{{ $item['value'] }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ $item['hint'] }}</p>
        </article>
    @endforeach
</div>

<div class="grid gap-6 xl:grid-cols-2">
    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
        <h2 class="font-semibold">Fuel Consumption Trend</h2>
        <p class="text-xs text-slate-500">Liters consumed by month</p>
        <div class="mt-5 h-80">
            @if ($hasFuelData)
                <canvas id="analyticsFuelTrend"></canvas>
            @else
                <div class="grid h-full place-items-center rounded-xl border border-dashed border-fuelix-line text-sm text-slate-500">
                    No fuel consumption data found in Firestore.
                </div>
            @endif
        </div>
    </section>

    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
        <h2 class="font-semibold">Monthly Expenses</h2>
        <p class="text-xs text-slate-500">Fuel cost by month</p>
        <div class="mt-5 h-80">
            @if ($hasExpenseData)
                <canvas id="analyticsExpenses"></canvas>
            @else
                <div class="grid h-full place-items-center rounded-xl border border-dashed border-fuelix-line text-sm text-slate-500">
                    No expense data found in Firestore.
                </div>
            @endif
        </div>
    </section>

    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
        <h2 class="font-semibold">Refuel Size Distribution</h2>
        <p class="text-xs text-slate-500">Small, medium, and large refuels</p>
        <div class="mt-5 grid gap-5 md:grid-cols-[220px_1fr] md:items-center">
            <div class="h-56">
                @if ($hasDistributionData)
                    <canvas id="analyticsDistribution"></canvas>
                @else
                    <div class="grid h-full place-items-center rounded-xl border border-dashed border-fuelix-line text-sm text-slate-500">
                        No refuels
                    </div>
                @endif
            </div>
            <div class="space-y-3 text-sm">
                @foreach([
                    ['Small refuels', ($distributionData[0] ?? 0) . ' tx', 'bg-fuelix-blue', '<= 25 L'],
                    ['Medium refuels', ($distributionData[1] ?? 0) . ' tx', 'bg-fuelix-green', '26-45 L'],
                    ['Large refuels', ($distributionData[2] ?? 0) . ' tx', 'bg-fuelix-amber', '> 45 L'],
                ] as $item)
                    <div class="flex items-center justify-between rounded-lg bg-[#0d1526] px-3 py-2">
                        <span class="flex items-center gap-2 text-slate-400">
                            <span class="h-2.5 w-2.5 rounded-full {{ $item[2] }}"></span>
                            {{ $item[0] }}
                            <span class="text-xs text-slate-600">{{ $item[3] }}</span>
                        </span>
                        <span class="font-semibold">{{ $item[1] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
        <h2 class="font-semibold">Top User Consumption</h2>
        <p class="text-xs text-slate-500">Highest total fuel usage by client</p>
        <div class="mt-5 h-80">
            @if ($hasTopUsers)
                <canvas id="analyticsTopUsers"></canvas>
            @else
                <div class="grid h-full place-items-center rounded-xl border border-dashed border-fuelix-line text-sm text-slate-500">
                    No client consumption ranking yet.
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    const chartColors = {
        blue: '#56a4ff',
        green: '#22c55e',
        amber: '#f59e0b',
        grid: 'rgba(148,163,184,.08)',
        text: '#94a3b8'
    };
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: chartColors.grid }, ticks: { color: chartColors.text } },
            y: { beginAtZero: true, grid: { color: chartColors.grid }, ticks: { color: chartColors.text } }
        }
    };

    const fuelLabels = @json($fuelLabels);
    const fuelData = @json($fuelData);
    const expenseLabels = @json($expenseLabels);
    const expenseData = @json($expenseData);
    const distributionData = @json($distributionData);
    const topUserLabels = @json($topUserLabels);
    const topUserData = @json($topUserData);
    const hasValues = (values) => values.some((value) => Number(value) > 0);

    const fuelTrend = document.getElementById('analyticsFuelTrend');
    if (fuelTrend && fuelLabels.length && hasValues(fuelData)) {
        new Chart(fuelTrend, {
            type: 'line',
            data: {
                labels: fuelLabels,
                datasets: [{
                    data: fuelData,
                    borderColor: chartColors.blue,
                    backgroundColor: 'rgba(86,164,255,.14)',
                    tension: .42,
                    fill: true,
                    pointRadius: 3
                }]
            },
            options: baseOptions
        });
    }

    const expenses = document.getElementById('analyticsExpenses');
    if (expenses && expenseLabels.length && hasValues(expenseData)) {
        new Chart(expenses, {
            type: 'bar',
            data: {
                labels: expenseLabels,
                datasets: [{ data: expenseData, backgroundColor: chartColors.blue, borderRadius: 6 }]
            },
            options: baseOptions
        });
    }

    const distribution = document.getElementById('analyticsDistribution');
    if (distribution && hasValues(distributionData)) {
        new Chart(distribution, {
            type: 'doughnut',
            data: {
                labels: ['Small refuels', 'Medium refuels', 'Large refuels'],
                datasets: [{ data: distributionData, backgroundColor: [chartColors.blue, chartColors.green, chartColors.amber], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '58%' }
        });
    }

    const topUsers = document.getElementById('analyticsTopUsers');
    if (topUsers && topUserLabels.length && hasValues(topUserData)) {
        new Chart(topUsers, {
            type: 'bar',
            data: {
                labels: topUserLabels,
                datasets: [{ data: topUserData, backgroundColor: chartColors.blue, borderRadius: 6 }]
            },
            options: {
                ...baseOptions,
                indexAxis: 'y'
            }
        });
    }
</script>
@endpush
