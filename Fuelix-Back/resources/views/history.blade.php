@extends('layouts.app')

@section('title', 'Transactions')
@section('page-title', 'Transactions List')
@section('page-subtitle', 'Review and control fuel transactions')

@section('content')
<section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="font-semibold">Transactions</h2>
        <div class="flex gap-2">
            <input placeholder="Search" class="rounded-lg border border-fuelix-line bg-[#0d1526] px-3 py-2 text-sm outline-none focus:border-fuelix-blue">
            <button class="rounded-lg border border-fuelix-line px-4 py-2 text-sm font-semibold text-slate-300">Firestore</button>
        </div>
    </div>

    @if (session('history_error'))
        <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
            {{ session('history_error') }}
        </div>
    @endif
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-500">
                <tr class="border-b border-fuelix-line">
                    <th class="py-3">Transaction ID</th>
                    <th>User</th>
                    <th>Station</th>
                    <th>Fuel</th>
                    <th>Price/L</th>
                    <th>Cost</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-fuelix-line">
                @forelse($transactions ?? [] as $row)
                    @php
                        $transactionId = $row['id'] ?? $row['transaction_id'] ?? '-';
                        $status = $row['status'] ?? 'Completed';
                        $date = (string) ($row['date'] ?? $row['created_at'] ?? '-');
                        $date = $date !== '-' ? substr($date, 0, 10) : '-';
                    @endphp
                    <tr class="text-slate-300">
                        <td class="py-3 font-semibold text-white"><a href="/transactions/{{ $transactionId }}" class="hover:text-fuelix-blue2">{{ $transactionId }}</a></td>
                        <td>{{ $row['user_name'] ?? 'Unknown user' }}</td>
                        <td>{{ $row['station_name'] ?? $row['station'] ?? '-' }}</td>
                        <td>{{ number_format((float) ($row['quantity_liters'] ?? 0), 1) }} L</td>
                        <td>{{ number_format((float) ($row['price_per_liter'] ?? 0), 3) }}</td>
                        <td>{{ number_format((float) ($row['amount'] ?? 0), 2) }} TND</td>
                        <td>{{ $date }}</td>
                        <td>
                            <span class="rounded px-2 py-1 text-xs {{ strtolower((string) $status) === 'completed' ? 'bg-green-500/15 text-fuelix-green' : (strtolower((string) $status) === 'pending' ? 'bg-amber-500/15 text-fuelix-amber' : 'bg-red-500/15 text-fuelix-red') }}">{{ $status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-500">No Firestore transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
