@extends('layouts.app')

@section('title', 'FueliX Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Live overview of platform fuel activity')

@section('content')
<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['label' => 'Client Users', 'value' => number_format($stats['users'] ?? 0), 'meta' => 'Firestore users'],
        ['label' => 'Transactions', 'value' => number_format($stats['transactions'] ?? 0), 'meta' => 'Recorded refuels'],
        ['label' => 'Consumption', 'value' => number_format((float) ($stats['consumption'] ?? 0), 1) . ' L', 'meta' => 'Total liters'],
        ['label' => 'Expenses', 'value' => number_format((float) ($stats['expenses'] ?? 0), 2) . ' TND', 'meta' => 'Total amount'],
    ] as $stat)
        <article class="rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
            <p class="text-xs text-slate-400">{{ $stat['label'] }}</p>
            <div class="mt-3 flex items-end justify-between">
                <h2 class="text-2xl font-bold">{{ $stat['value'] }}</h2>
                <span class="text-xs text-slate-500">{{ $stat['meta'] }}</span>
            </div>
        </article>
    @endforeach
</div>

@if (session('dashboard_error'))
    <div class="mt-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
        {{ session('dashboard_error') }}
    </div>
@endif

<div class="mt-6 grid gap-6 xl:grid-cols-[2fr_1fr]">
    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="font-semibold">Fuel Consumption Trend</h2>
                <p class="text-xs text-slate-500">Monthly liters consumed</p>
            </div>
            <div class="flex gap-2 text-xs text-slate-400">
                <span>Analytics</span><span>Overview</span>
            </div>
        </div>
        <canvas id="fuelTrend" height="105"></canvas>
    </section>

    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
        <h2 class="font-semibold">System Health</h2>
        <div class="mt-5 space-y-4">
            <div class="flex items-center justify-between border-b border-fuelix-line pb-3">
                <span class="text-sm text-slate-400">API Status</span>
                <span class="rounded bg-green-500/15 px-2 py-1 text-xs text-fuelix-green">Online</span>
            </div>
            <div class="flex items-center justify-between border-b border-fuelix-line pb-3">
                <span class="text-sm text-slate-400">ML Insights</span>
                <span class="rounded bg-blue-500/15 px-2 py-1 text-xs text-fuelix-blue2">Active</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-sm text-slate-400">Firestore Sync</span>
                <span class="rounded bg-green-500/15 px-2 py-1 text-xs text-fuelix-green">Synced</span>
            </div>
        </div>
    </section>
</div>

<section class="mt-6 rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="font-semibold">Recent Transactions</h2>
        <a href="/history" class="text-sm text-fuelix-blue2">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-500">
                <tr class="border-b border-fuelix-line">
                    <th class="py-3">Transaction ID</th>
                    <th>User</th>
                    <th>Station</th>
                    <th>Fuel</th>
                    <th>Cost</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-fuelix-line">
                @forelse($recentTransactions ?? [] as $row)
                    @php
                        $transactionId = $row['id'] ?? $row['transaction_id'] ?? '-';
                        $status = $row['status'] ?? 'Completed';
                    @endphp
                    <tr class="text-slate-300">
                        <td class="py-3 font-medium text-white"><a href="/transactions/{{ $transactionId }}" class="hover:text-fuelix-blue2">{{ $transactionId }}</a></td>
                        <td>{{ $row['user_name'] ?? 'Unknown user' }}</td>
                        <td>{{ $row['station_name'] ?? $row['station'] ?? '-' }}</td>
                        <td>{{ number_format((float) ($row['quantity_liters'] ?? 0), 1) }} L</td>
                        <td>{{ number_format((float) ($row['amount'] ?? 0), 2) }} TND</td>
                        <td><span class="rounded px-2 py-1 text-xs {{ strtolower((string) $status) === 'completed' ? 'bg-green-500/15 text-fuelix-green' : 'bg-amber-500/15 text-fuelix-amber' }}">{{ $status }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-500">No Firestore transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('fuelTrend');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels ?? []),
                datasets: [{
                    data: @json($chartData ?? []),
                    borderColor: '#56a4ff',
                    backgroundColor: 'rgba(47, 128, 237, .18)',
                    tension: .42,
                    fill: true,
                    pointRadius: 3
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: 'rgba(148,163,184,.08)' }, ticks: { color: '#94a3b8' } },
                    y: { grid: { color: 'rgba(148,163,184,.08)' }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    }
</script>
@endpush
