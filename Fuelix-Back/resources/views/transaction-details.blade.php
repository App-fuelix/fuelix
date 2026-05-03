@extends('layouts.app')

@section('title', 'Transaction Details')
@section('page-title', 'Transaction Details')
@section('page-subtitle', 'Detailed view for transaction ' . ($transactionId ?? ''))

@section('content')
<div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-6 shadow-fuelix">
        @if (session('transaction_error'))
            <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                {{ session('transaction_error') }}
            </div>
        @endif

        <div class="mb-6 flex items-center justify-between">
            <h2 class="font-semibold">Transaction ID: {{ $transactionId }}</h2>
            <a href="/history" class="rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-2 text-sm text-slate-300">Back</a>
        </div>

        @if ($transaction)
            @php
                $status = $transaction['status'] ?? 'Completed';
                $details = [
                    ['User', $transaction['user_name'] ?? 'Unknown user'],
                    ['Email', $transaction['user_email'] ?? '-'],
                    ['Station', $transaction['station_name'] ?? $transaction['station'] ?? '-'],
                    ['Fuel Amount', number_format((float) ($transaction['quantity_liters'] ?? 0), 1) . ' Liters'],
                    ['Price / Liter', number_format((float) ($transaction['price_per_liter'] ?? 0), 3) . ' TND'],
                    ['Total Cost', number_format((float) ($transaction['amount'] ?? 0), 2) . ' TND'],
                    ['Date', $transaction['date'] ?? $transaction['created_at'] ?? '-'],
                    ['Status', $status],
                ];
            @endphp
            <dl class="divide-y divide-fuelix-line">
                @foreach($details as $item)
                    <div class="grid grid-cols-3 gap-4 py-4">
                        <dt class="text-sm text-slate-500">{{ $item[0] }}</dt>
                        <dd class="col-span-2 text-sm font-medium {{ $item[0] === 'Status' && strtolower((string) $item[1]) === 'completed' ? 'text-fuelix-green' : 'text-white' }}">{{ $item[1] }}</dd>
                    </div>
                @endforeach
            </dl>
        @else
            <div class="rounded-xl border border-fuelix-line bg-[#0d1526] p-8 text-center text-slate-400">
                No Firestore transaction found for this ID.
            </div>
        @endif
    </section>

    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-6 shadow-fuelix">
        <h2 class="font-semibold">Controls</h2>
        <div class="mt-5 space-y-3">
            <button class="w-full rounded-lg bg-fuelix-blue px-4 py-3 text-sm font-semibold">Mark as Reviewed</button>
            <button class="w-full rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm text-slate-300">Export Details</button>
            <button class="w-full rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-fuelix-red">Flag Transaction</button>
        </div>
    </section>
</div>
@endsection
