@extends('layouts.app')

@section('title', 'History - Fuelix')

@section('content')
<div class="min-h-screen" style="background-color: #f3f4f6;">

    {{-- Header --}}
    <div class="bg-white px-5 py-4 flex items-center gap-4 shadow-sm sticky top-0 z-10">
        <a href="/dashboard" class="text-gray-600 hover:text-gray-900 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-gray-900 font-semibold text-lg flex-1">History</h1>
        <button onclick="toggleFilter()" class="text-gray-500 hover:text-orange-500 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
            </svg>
        </button>
    </div>

    {{-- Filter bar --}}
    <div id="filterBar" class="hidden bg-white border-b border-gray-100 px-5 py-3">
        <div class="max-w-sm mx-auto flex gap-2">
            <input type="text" id="searchStation" placeholder="Rechercher une station..."
                oninput="filterTransactions()"
                class="flex-1 px-3 py-2 rounded-xl border border-gray-200 text-gray-700 text-sm
                       focus:outline-none focus:border-orange-400 placeholder-gray-400">
            <button onclick="clearFilter()" class="px-3 py-2 rounded-xl bg-gray-100 text-gray-500 text-sm hover:bg-gray-200 transition">
                Reset
            </button>
        </div>
    </div>

    <main class="max-w-sm mx-auto px-5 py-5 pb-24">

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="bg-white rounded-2xl p-4 shadow-sm">
                <p class="text-gray-400 text-xs mb-1">Total dépensé</p>
                <p id="totalSpent" class="text-gray-800 font-bold text-lg">--</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm">
                <p class="text-gray-400 text-xs mb-1">Total litres</p>
                <p id="totalLiters" class="text-orange-500 font-bold text-lg">--</p>
            </div>
        </div>

        {{-- Loading skeleton --}}
        <div id="skeleton" class="space-y-4">
            @for($i = 0; $i < 3; $i++)
            <div>
                <div class="h-4 w-20 bg-gray-200 rounded animate-pulse mb-3"></div>
                <div class="space-y-2">
                    <div class="h-16 bg-white rounded-2xl animate-pulse"></div>
                    <div class="h-16 bg-white rounded-2xl animate-pulse"></div>
                </div>
            </div>
            @endfor
        </div>

        {{-- Transactions list --}}
        <div id="transactionsList" class="hidden space-y-6"></div>

        {{-- Empty state --}}
        <div id="emptyState" class="hidden text-center py-16">
            <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="text-gray-500 font-medium">Aucune transaction</p>
            <p class="text-gray-400 text-sm mt-1">Vos transactions apparaîtront ici</p>
        </div>

    </main>

    {{-- Transaction Detail Modal --}}
    <div id="detailModal" class="hidden fixed inset-0 bg-black/50 flex items-end justify-center z-50">
        <div class="bg-white rounded-t-3xl w-full max-w-sm p-6 pb-8">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5"></div>
            <h3 class="text-gray-800 font-bold text-lg mb-5">Détails transaction</h3>
            <div id="detailContent" class="space-y-3"></div>
            <button onclick="document.getElementById('detailModal').classList.add('hidden')"
                class="w-full mt-6 py-3 rounded-2xl bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition">
                Fermer
            </button>
        </div>
    </div>

    {{-- Bottom Nav --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-6 py-3">
        <div class="max-w-sm mx-auto flex justify-around">
            <a href="/dashboard" class="flex flex-col items-center gap-1 text-gray-400 hover:text-orange-500 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-xs">Home</span>
            </a>
            <a href="/fuel-card" class="flex flex-col items-center gap-1 text-gray-400 hover:text-orange-500 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <span class="text-xs">Card</span>
            </a>
            <a href="/history" class="flex flex-col items-center gap-1 text-orange-500">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                </svg>
                <span class="text-xs font-semibold">History</span>
            </a>
            <a href="/profile" class="flex flex-col items-center gap-1 text-gray-400 hover:text-orange-500 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="text-xs">Profile</span>
            </a>
        </div>
    </nav>
</div>

<script>
const token = localStorage.getItem('token');
if (!token) window.location.href = '/login';

let allData = {};

function toggleFilter() {
    document.getElementById('filterBar').classList.toggle('hidden');
}

function clearFilter() {
    document.getElementById('searchStation').value = '';
    renderTransactions(allData);
}

function filterTransactions() {
    const search = document.getElementById('searchStation').value.toLowerCase();
    if (!search) { renderTransactions(allData); return; }

    const filtered = {};
    Object.entries(allData).forEach(([month, txs]) => {
        const matching = txs.filter(t => t.station_name?.toLowerCase().includes(search));
        if (matching.length) filtered[month] = matching;
    });
    renderTransactions(filtered);
}

function renderTransactions(grouped) {
    const list = document.getElementById('transactionsList');
    const empty = document.getElementById('emptyState');
    const entries = Object.entries(grouped);

    if (!entries.length) {
        list.classList.add('hidden');
        empty.classList.remove('hidden');
        return;
    }

    empty.classList.add('hidden');
    list.classList.remove('hidden');

    list.innerHTML = entries.map(([month, txs]) => `
        <div>
            <p class="text-gray-500 text-sm font-medium mb-3">${month}</p>
            <div class="space-y-2">
                ${txs.map(t => `
                    <div onclick="showDetail(${JSON.stringify(t).replace(/"/g, '&quot;')})"
                        class="bg-white rounded-2xl p-4 shadow-sm flex items-center gap-3 cursor-pointer hover:shadow-md transition active:scale-98">
                        <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158
                                       a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0
                                       00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782
                                       0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-gray-800 font-medium text-sm truncate">${t.station_name || 'Station inconnue'}</p>
                            <p class="text-gray-400 text-xs">${t.date} · ${t.quantity_liters}L</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-gray-800 font-semibold text-sm">${t.amount} TND</p>
                            <p class="text-gray-400 text-xs">${t.price_per_liter} TND/L</p>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');
}

function showDetail(t) {
    const content = document.getElementById('detailContent');
    const rows = [
        ['Station', t.station_name || 'Inconnue'],
        ['Date', t.date + ' à ' + t.time],
        ['Quantité', t.quantity_liters + ' L'],
        ['Prix/litre', t.price_per_liter + ' TND'],
        ['Montant total', t.amount + ' TND'],
        ['Véhicule', t.vehicle ? t.vehicle.plate_number + ' · ' + t.vehicle.model : 'Non renseigné'],
    ];

    content.innerHTML = rows.map(([label, value]) => `
        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
            <span class="text-gray-400 text-sm">${label}</span>
            <span class="text-gray-800 text-sm font-medium">${value}</span>
        </div>
    `).join('');

    document.getElementById('detailModal').classList.remove('hidden');
}

async function loadHistory() {
    const res = await fetch('/api/fuel-cards/history', {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    });

    if (res.status === 401) { window.location.href = '/login'; return; }

    const data = await res.json();

    document.getElementById('skeleton').classList.add('hidden');
    document.getElementById('totalSpent').textContent = data.total_spent;
    document.getElementById('totalLiters').textContent = data.total_liters;

    allData = data.transactions;
    renderTransactions(allData);
}

loadHistory();
</script>
@endsection
