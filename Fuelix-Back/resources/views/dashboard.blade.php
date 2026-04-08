@extends('layouts.app')

@section('title', 'Dashboard - Fuelix')

@section('content')
<div class="min-h-screen bg-gray-950">

    {{-- Navbar --}}
    <nav class="bg-gray-900 border-b border-gray-800 px-6 py-4">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-orange-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-white font-bold text-xl">Fuelix</span>
            </div>
            <div class="flex items-center gap-4">
                <span id="userName" class="text-gray-400 text-sm"></span>
                <button onclick="logout()"
                    class="text-sm text-gray-400 hover:text-white border border-gray-700 hover:border-gray-500 px-4 py-2 rounded-xl transition">
                    Déconnexion
                </button>
            </div>
        </div>
    </nav>

    {{-- Main --}}
    <main class="max-w-6xl mx-auto px-6 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-white">Bonjour, <span id="greetName">...</span> 👋</h2>
            <p class="text-gray-400 mt-1">Voici un aperçu de votre consommation</p>
        </div>

        {{-- AI Insight Banner --}}
        <div id="insightBanner" class="mb-8 p-5 rounded-2xl bg-gradient-to-r from-orange-500/20 to-orange-600/10 border border-orange-500/30 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-orange-500/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m1.636 6.364l.707-.707
                           M12 21v-1m-6.364-1.636l.707-.707M12 12a3 3 0 100-6 3 3 0 000 6z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-orange-300 font-semibold text-sm mb-1">AI Insight</p>
                <p id="insightText" class="text-white">Chargement...</p>
            </div>
            <div id="insightVariation" class="text-sm font-bold px-3 py-1 rounded-full bg-orange-500/20 text-orange-300 shrink-0"></div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-900 rounded-2xl p-5 border border-gray-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158
                                   a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0
                                   00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782
                                   0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <span class="text-gray-400 text-sm">Consommation</span>
                </div>
                <p id="totalConsumption" class="text-2xl font-bold text-white">--</p>
                <p class="text-gray-500 text-xs mt-1">Total cumulé</p>
            </div>

            <div class="bg-gray-900 rounded-2xl p-5 border border-gray-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl bg-green-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11
                                   0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1
                                   M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-gray-400 text-sm">Coût total</span>
                </div>
                <p id="totalCost" class="text-2xl font-bold text-white">--</p>
                <p class="text-gray-500 text-xs mt-1">Dépenses totales</p>
            </div>

            <div class="bg-gray-900 rounded-2xl p-5 border border-gray-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h10l2-2z"/>
                        </svg>
                    </div>
                    <span class="text-gray-400 text-sm">Moy. véhicule</span>
                </div>
                <p id="avgPerVehicle" class="text-2xl font-bold text-white">--</p>
                <p class="text-gray-500 text-xs mt-1">Par véhicule</p>
            </div>

            <div class="bg-gray-900 rounded-2xl p-5 border border-gray-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-xl bg-orange-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <span class="text-gray-400 text-sm">Tendance</span>
                </div>
                <p id="monthlyTrend" class="text-2xl font-bold text-white">--</p>
                <p class="text-gray-500 text-xs mt-1">vs mois dernier</p>
            </div>
        </div>

        {{-- Chart --}}
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-white font-semibold">Consommation hebdomadaire</h3>
                    <p class="text-gray-400 text-sm">7 derniers jours (litres)</p>
                </div>
                <span id="lastUpdated" class="text-gray-500 text-xs"></span>
            </div>
            <div id="chart" class="flex items-end gap-3 h-40">
                {{-- Bars rendered by JS --}}
            </div>
            <div id="chartLabels" class="flex gap-3 mt-2">
                {{-- Labels rendered by JS --}}
            </div>
        </div>

        {{-- Fuel Card Preview --}}
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-white font-semibold">Ma carte carburant</h3>
                <a href="/fuel-card" class="text-orange-400 hover:text-orange-300 text-sm transition">Voir détails →</a>
            </div>
            <div id="cardPreview" class="text-gray-400 text-sm">Chargement...</div>
        </div>

    </main>
</div>

<script>
    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    if (!token) window.location.href = '/login';

    // Set user name
    document.getElementById('userName').textContent = user.name || '';
    document.getElementById('greetName').textContent = user.name?.split(' ')[0] || '';

    async function fetchDashboard() {
        try {
            const res = await fetch('/api/dashboard', {
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
            });

            if (res.status === 401) { window.location.href = '/login'; return; }

            const data = await res.json();

            // Stats
            document.getElementById('totalConsumption').textContent = data.total_consumption;
            document.getElementById('totalCost').textContent = data.total_cost;
            document.getElementById('avgPerVehicle').textContent = data.average_per_vehicle;

            const trend = data.monthly_trend;
            const trendEl = document.getElementById('monthlyTrend');
            trendEl.textContent = trend;
            trendEl.className = 'text-2xl font-bold ' + (trend.startsWith('-') ? 'text-green-400' : 'text-red-400');

            // AI Insight
            document.getElementById('insightText').textContent = data.ai_insight.text;
            document.getElementById('insightVariation').textContent = data.ai_insight.variation;
            document.getElementById('lastUpdated').textContent = 'Mis à jour: ' + data.last_updated;

            // Chart
            renderChart(data.weekly_consumption);

        } catch (err) {
            console.error(err);
        }
    }

    function renderChart(weeklyData) {
        const chart = document.getElementById('chart');
        const labels = document.getElementById('chartLabels');
        const values = Object.values(weeklyData).map(Number);
        const days = Object.keys(weeklyData);
        const max = Math.max(...values, 1);

        chart.innerHTML = '';
        labels.innerHTML = '';

        days.forEach((day, i) => {
            const val = values[i];
            const heightPct = Math.max((val / max) * 100, 4);
            const isToday = i === days.length - 1;

            const bar = document.createElement('div');
            bar.className = 'flex-1 rounded-t-lg transition-all duration-500 ' +
                (isToday ? 'bg-orange-500' : 'bg-gray-700 hover:bg-gray-600');
            bar.style.height = heightPct + '%';
            bar.title = `${val}L`;
            chart.appendChild(bar);

            const label = document.createElement('div');
            label.className = 'flex-1 text-center text-xs ' + (isToday ? 'text-orange-400' : 'text-gray-500');
            label.textContent = day;
            labels.appendChild(label);
        });
    }

    async function fetchCard() {
        try {
            const res = await fetch('/api/fuel-cards/show', {
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (res.ok) {
                document.getElementById('cardPreview').innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-8 rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white font-mono font-semibold">${data.masked_number}</p>
                                <p class="text-gray-500 text-xs">${data.issuer} · Expire ${data.valid_thru}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-white font-bold text-lg">${data.balance}</p>
                            <span class="text-xs px-2 py-1 rounded-full ${data.status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'}">
                                ${data.status}
                            </span>
                        </div>
                    </div>
                `;
            }
        } catch (err) {}
    }

    async function logout() {
        await fetch('/api/logout', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    }

    fetchDashboard();
    fetchCard();
</script>
@endsection
