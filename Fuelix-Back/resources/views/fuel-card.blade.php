@extends('layouts.app')

@section('title', 'Digital Card - Fuelix')

@section('content')
<div class="min-h-screen bg-gray-100" style="background-color: #f3f4f6;">

    {{-- Header --}}
    <div class="bg-white px-5 py-4 flex items-center gap-4 shadow-sm">
        <a href="/dashboard" class="text-gray-600 hover:text-gray-900 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-gray-900 font-semibold text-lg">Digital Card</h1>
    </div>

    <main class="max-w-sm mx-auto px-5 py-6 space-y-5">

        {{-- Card Visual --}}
        <div class="relative rounded-2xl overflow-hidden" style="background: linear-gradient(135deg, #1e2a4a 0%, #2d3f6b 50%, #1a2540 100%); min-height: 180px; padding: 24px;">
            {{-- NFC icon --}}
            <div class="absolute top-5 left-5">
                <svg class="w-7 h-7 text-white opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0"/>
                </svg>
            </div>

            {{-- Fuelix brand --}}
            <div class="absolute top-5 right-5">
                <span class="text-white font-bold text-lg tracking-wide">Fuelix</span>
            </div>

            {{-- Card number --}}
            <div class="mt-10">
                <p id="cardNumber" class="text-white font-mono text-2xl tracking-widest">**** ****</p>
            </div>

            {{-- Issuer + Expiry --}}
            <div class="mt-4">
                <p id="cardIssuer" class="text-gray-300 font-medium text-sm">Freeoui</p>
                <p class="text-gray-400 text-xs mt-0.5">Valid thru <span id="cardExpiry">--/--</span></p>
            </div>
        </div>

        {{-- Balance --}}
        <div class="bg-white rounded-2xl px-5 py-4 shadow-sm">
            <p class="text-gray-500 text-sm mb-1">Balance</p>
            <div class="flex items-baseline gap-1">
                <span id="cardBalance" class="text-orange-500 font-bold text-3xl">--</span>
                <span class="text-orange-400 font-semibold text-sm">TND</span>
            </div>
        </div>

        {{-- Authorized Products --}}
        <div class="bg-white rounded-2xl px-5 py-4 shadow-sm">
            <p class="text-gray-500 text-sm mb-4">Authorized products</p>
            <div id="products" class="flex justify-around">
                {{-- Rendered by JS --}}
                <div class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 rounded-2xl bg-gray-100 animate-pulse"></div>
                    <div class="w-10 h-3 bg-gray-100 rounded animate-pulse"></div>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 rounded-2xl bg-gray-100 animate-pulse"></div>
                    <div class="w-12 h-3 bg-gray-100 rounded animate-pulse"></div>
                </div>
                <div class="flex flex-col items-center gap-2">
                    <div class="w-14 h-14 rounded-2xl bg-gray-100 animate-pulse"></div>
                    <div class="w-14 h-3 bg-gray-100 rounded animate-pulse"></div>
                </div>
            </div>
        </div>

        {{-- Scan to Pay --}}
        <button onclick="scanToPay()"
            class="w-full py-4 rounded-2xl bg-orange-500 hover:bg-orange-600 active:scale-95 transition
                   flex items-center justify-center gap-3 shadow-lg shadow-orange-500/30">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01
                       M5 8H3a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2v-3M15 4h2a2 2 0 012 2v3"/>
            </svg>
            <span class="text-white font-bold text-lg">Scan to Pay</span>
        </button>
        <p class="text-center text-gray-400 text-xs -mt-2">Secured digital payment</p>

        

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
            <a href="/fuel-card" class="flex flex-col items-center gap-1 text-orange-500">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                </svg>
                <span class="text-xs font-semibold">Card</span>
            </a>
            <a href="/history" class="flex flex-col items-center gap-1 text-gray-400 hover:text-orange-500 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                <span class="text-xs">History</span>
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

    <div class="h-20"></div>
</div>

<script>
const token = localStorage.getItem('token');
if (!token) window.location.href = '/login';

const productIcons = {
    essence: { label: 'Fuel', icon: `<svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>` },
    diesel:  { label: 'Fuel', icon: `<svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>` },
    carwash: { label: 'Car wash', icon: `<svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h10l2-2z"/></svg>` },
    lubricants: { label: 'Lubricants', icon: `<svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>` },
};

async function loadCard() {
    const res = await fetch('/api/fuel-cards/show', {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    });
    if (res.status === 401) { window.location.href = '/login'; return; }
    const card = await res.json();
    if (!res.ok) return;

    document.getElementById('cardNumber').textContent = card.masked_number;
    document.getElementById('cardIssuer').textContent = card.issuer;
    document.getElementById('cardExpiry').textContent = card.valid_thru;
    document.getElementById('cardBalance').textContent = parseFloat(card.balance_raw).toFixed(0);

    // Products
    const products = card.authorized_products || ['essence', 'diesel'];
    const uniqueProducts = [...new Set(products)];
    document.getElementById('products').innerHTML = uniqueProducts.map(p => {
        const info = productIcons[p.toLowerCase()] || { label: p, icon: '' };
        return `
            <div class="flex flex-col items-center gap-2">
                <div class="w-14 h-14 rounded-2xl bg-orange-50 flex items-center justify-center">
                    ${info.icon}
                </div>
                <span class="text-gray-600 text-xs capitalize">${info.label}</span>
            </div>`;
    }).join('');
}

function scanToPay() {
    const number = document.getElementById('cardNumber').textContent;
    document.getElementById('qrCardNumber').textContent = number;
    document.getElementById('qrCode').innerHTML = `
        <div class="grid grid-cols-5 gap-1 p-2">
            ${Array.from({length: 25}, (_, i) =>
                `<div class="w-7 h-7 rounded-sm ${Math.random() > 0.4 ? 'bg-gray-800' : 'bg-white border border-gray-200'}"></div>`
            ).join('')}
        </div>`;
    document.getElementById('qrModal').classList.remove('hidden');
}

function setAmount(val) {
    document.getElementById('rechargeAmount').value = val;
    document.querySelectorAll('.quick-amount').forEach(btn => {
        const active = parseInt(btn.dataset.amount) === val;
        btn.className = 'quick-amount py-2 rounded-xl border text-sm transition ' +
            (active ? 'border-orange-400 text-orange-500 bg-orange-50' : 'border-gray-200 text-gray-600 hover:border-orange-400 hover:text-orange-500');
    });
}

async function recharge() {
    const amount = parseFloat(document.getElementById('rechargeAmount').value);
    const errorEl = document.getElementById('rechargeError');
    const successEl = document.getElementById('rechargeSuccess');
    const btn = document.getElementById('rechargeBtn');

    errorEl.classList.add('hidden');
    successEl.classList.add('hidden');

    if (!amount || amount < 1) {
        errorEl.textContent = 'Entrez un montant valide';
        errorEl.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.textContent = '...';

    const res = await fetch('/api/fuel-cards/recharge', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ amount })
    });

    const data = await res.json();
    btn.disabled = false;
    btn.textContent = 'OK';

    if (res.ok) {
        successEl.textContent = '✓ Rechargé · Nouveau solde: ' + data.new_balance;
        successEl.classList.remove('hidden');
        document.getElementById('cardBalance').textContent = parseFloat(data.balance_raw).toFixed(0);
        document.getElementById('rechargeAmount').value = '';
        document.querySelectorAll('.quick-amount').forEach(b => {
            b.className = 'quick-amount py-2 rounded-xl border border-gray-200 text-gray-600 text-sm hover:border-orange-400 hover:text-orange-500 transition';
        });
    } else {
        errorEl.textContent = data.message || 'Erreur';
        errorEl.classList.remove('hidden');
    }
}

loadCard();
</script>
@endsection
