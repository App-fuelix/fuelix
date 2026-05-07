@extends('layouts.app')

@section('title', 'User Details')
@section('page-title', 'User Details')
@section('page-subtitle', 'View and edit Firestore client account')

@section('content')
@if (session('user_error'))
    <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
        {{ session('user_error') }}
    </div>
@endif
@if (session('user_success'))
    <div class="mb-5 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-200">
        {{ session('user_success') }}
    </div>
@endif
@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
        {{ $errors->first() }}
    </div>
@endif

@if (! $firestoreUser)
    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-8 text-center shadow-fuelix">
        <h2 class="text-xl font-semibold">User not found</h2>
        <p class="mt-2 text-sm text-slate-400">No Firestore user was found for this ID.</p>
        <a href="/users" class="mt-6 inline-block rounded-lg bg-fuelix-blue px-5 py-2 text-sm font-semibold">Back to users</a>
    </section>
@else
    @php
        $isAdmin = strtolower((string) ($firestoreUser['role'] ?? 'user')) === 'admin';
        $status = (string) ($firestoreUser['status'] ?? (($firestoreUser['is_active'] ?? true) ? 'Active' : 'Inactive'));
    @endphp

    <div class="grid gap-6 xl:grid-cols-[1fr_1.3fr]">
        <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-6 shadow-fuelix">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold">{{ $firestoreUser['name'] ?? 'Unnamed user' }}</h2>
                    <p class="mt-1 text-sm text-slate-400">{{ $firestoreUser['email'] ?? '-' }}</p>
                </div>
                <span class="rounded px-2 py-1 text-xs {{ strtolower($status) === 'active' ? 'bg-green-500/15 text-fuelix-green' : 'bg-red-500/15 text-fuelix-red' }}">{{ $status }}</span>
            </div>

            <div class="mt-6 divide-y divide-fuelix-line">
                @foreach([
                    ['Firestore ID', $firestoreUser['id'] ?? '-'],
                    ['Role', ucfirst((string) ($firestoreUser['role'] ?? 'user'))],
                    ['Phone', $firestoreUser['phone'] ?? '-'],
                    ['City', $firestoreUser['city'] ?? '-'],
                    ['Created', substr((string) ($firestoreUser['created_at'] ?? '-'), 0, 10)],
                ] as $item)
                    <div class="flex items-center justify-between py-3">
                        <span class="text-sm text-slate-500">{{ $item[0] }}</span>
                        <span class="text-sm text-slate-200">{{ $item[1] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-6 shadow-fuelix">
            <h2 class="font-semibold">Edit User</h2>

            @if ($isAdmin)
                <div class="mt-5 rounded-lg border border-fuelix-line bg-[#0d1526] p-5 text-sm text-slate-400">
                    Admin users are protected. You can view this account, but edit, deactivate, and delete actions are disabled.
                </div>
            @else
                <form method="POST" action="/users/{{ $firestoreUser['id'] }}" class="mt-5 grid gap-4 md:grid-cols-2">
                    @csrf
                    <div>
                        <label class="text-xs text-slate-500">Name</label>
                        <input name="name" value="{{ old('name', $firestoreUser['name'] ?? '') }}" class="mt-2 w-full rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Email</label>
                        <input type="email" name="email" value="{{ old('email', $firestoreUser['email'] ?? '') }}" class="mt-2 w-full rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Phone</label>
                        <input name="phone" value="{{ old('phone', $firestoreUser['phone'] ?? '') }}" class="mt-2 w-full rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">City</label>
                        <input name="city" value="{{ old('city', $firestoreUser['city'] ?? '') }}" class="mt-2 w-full rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
                    </div>
                    <div class="md:col-span-2 flex flex-wrap gap-3">
                        <button type="submit" class="rounded-lg bg-fuelix-blue px-5 py-2 text-sm font-semibold">Save Changes</button>
                        <a href="/users" class="rounded-lg border border-fuelix-line px-5 py-2 text-sm text-slate-300">Back</a>
                    </div>
                </form>
            @endif
        </section>
    </div>

    {{-- Fuel Card Section --}}
    <section class="mt-6 rounded-xl border border-fuelix-line bg-fuelix-panel p-6 shadow-fuelix">
        <h2 class="font-semibold">Fuel Card</h2>
        
        @if (!empty($fuelCards) && count($fuelCards) > 0)
            @php
                $card = $fuelCards[0];
                $currentPlanId = $card['card_plan_id'] ?? 'bronze';
            @endphp
            
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-xs text-slate-500">Card Number</label>
                    <div class="mt-2 rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm">
                        {{ $card['masked_number'] ?? '**** **** **** ****' }}
                    </div>
                </div>
                <div>
                    <label class="text-xs text-slate-500">Balance</label>
                    <div class="mt-2 rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm">
                        {{ number_format((float) ($card['balance'] ?? 0), 2) }} TND
                    </div>
                </div>
                <div>
                    <label class="text-xs text-slate-500">Current Level</label>
                    <div class="mt-2 rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm">
                        {{ $card['card_plan_name'] ?? 'Bronze Card' }}
                    </div>
                </div>
                <div>
                    <label class="text-xs text-slate-500">Valid Thru</label>
                    <div class="mt-2 rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm">
                        {{ $card['valid_thru'] ?? '-' }}
                    </div>
                </div>
            </div>

            @if (!$isAdmin)
                <form method="POST" action="/users/{{ $firestoreUser['id'] }}/card-level" class="mt-6">
                    @csrf
                    @method('PUT')
                    
                    <label class="text-sm font-semibold">Change Card Level</label>
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        @foreach($availablePlans ?? [] as $plan)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-fuelix-line bg-[#0d1526] p-4 transition hover:border-fuelix-blue {{ $currentPlanId === $plan['id'] ? 'border-fuelix-blue bg-fuelix-blue/5' : '' }}">
                                <input 
                                    type="radio" 
                                    name="plan_id" 
                                    value="{{ $plan['id'] }}"
                                    {{ $currentPlanId === $plan['id'] ? 'checked' : '' }}
                                    class="h-4 w-4 text-fuelix-blue focus:ring-fuelix-blue"
                                >
                                <div class="flex-1">
                                    <div class="text-sm font-semibold">{{ $plan['name'] ?? '' }}</div>
                                    <div class="text-xs text-slate-500">Tier {{ $plan['tier_level'] ?? 1 }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    
                    <button type="submit" class="mt-4 rounded-lg bg-fuelix-blue px-5 py-2 text-sm font-semibold">
                        Update Card Level
                    </button>
                </form>
            @endif
        @else
            <div class="mt-5 rounded-lg border border-fuelix-line bg-[#0d1526] p-8 text-center">
                <p class="text-sm text-slate-400">No fuel card found for this user.</p>
                @if (!$isAdmin)
                    <form method="POST" action="/users/{{ $firestoreUser['id'] }}/create-card" class="mt-4">
                        @csrf
                        <button type="submit" class="rounded-lg bg-fuelix-blue px-5 py-2 text-sm font-semibold">
                            Create Bronze Card
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </section>

    <section class="mt-6 rounded-xl border border-fuelix-line bg-fuelix-panel p-6 shadow-fuelix">
        <h2 class="font-semibold">User Transactions</h2>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="text-xs uppercase text-slate-500">
                    <tr class="border-b border-fuelix-line">
                        <th class="py-3">ID</th>
                        <th>Station</th>
                        <th>Fuel</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-fuelix-line">
                    @forelse($transactions as $transaction)
                        <tr class="text-slate-300">
                            <td class="py-3 font-semibold text-white">{{ $transaction['id'] ?? '-' }}</td>
                            <td>{{ $transaction['station_name'] ?? $transaction['station'] ?? '-' }}</td>
                            <td>{{ number_format((float) ($transaction['quantity_liters'] ?? 0), 1) }} L</td>
                            <td>{{ number_format((float) ($transaction['amount'] ?? 0), 2) }} TND</td>
                            <td>{{ substr((string) ($transaction['date'] ?? $transaction['created_at'] ?? '-'), 0, 10) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">No transactions found for this user.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection
