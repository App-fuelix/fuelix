@extends('layouts.app')

@section('title', 'Profile - Fuelix')

@section('content')
<div class="min-h-screen" style="background-color: #f3f4f6;">

    {{-- Header --}}
    <div class="bg-white px-5 py-4 flex items-center gap-4 shadow-sm">
        <a href="/dashboard" class="text-gray-600 hover:text-gray-900 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-gray-900 font-semibold text-lg">Profile</h1>
    </div>

    <main class="max-w-sm mx-auto px-5 py-6 pb-24 space-y-3">

        {{-- Avatar Card --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm flex flex-col items-center">
            {{-- Avatar --}}
            <div class="w-20 h-20 rounded-full overflow-hidden mb-3 bg-orange-100 flex items-center justify-center">
                <svg class="w-14 h-14 text-orange-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                </svg>
            </div>

            <p id="profileName" class="text-gray-800 font-bold text-xl">--</p>
            <p id="profileEmail" class="text-gray-400 text-sm mt-0.5">--</p>
            <p id="profilePhone" class="text-gray-400 text-sm mt-0.5"></p>

            <button onclick="document.getElementById('editModal').classList.remove('hidden')"
                class="mt-4 px-6 py-2 rounded-full bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold transition active:scale-95">
                Edit Profile
            </button>
        </div>

        {{-- Settings --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">

            {{-- Appearance --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"/>
                        </svg>
                    </div>
                    <span class="text-gray-700 font-medium text-sm">Appearance</span>
                </div>
                <button onclick="toggleSwitch(this)" class="relative w-11 h-6 rounded-full bg-orange-500 transition-colors focus:outline-none" data-on="true">
                    <span class="absolute top-0.5 right-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform"></span>
                </button>
            </div>

            {{-- Notifications --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M5.85 3.5a.75.75 0 00-1.117-1 9.719 9.719 0 00-2.348 4.876.75.75 0 001.479.248A8.219 8.219 0 015.85 3.5zM19.267 2.5a.75.75 0 10-1.118 1 8.22 8.22 0 011.987 4.124.75.75 0 001.48-.248A9.72 9.72 0 0019.266 2.5z"/>
                            <path fill-rule="evenodd" d="M12 2.25A6.75 6.75 0 005.25 9v.75a8.217 8.217 0 01-2.119 5.52.75.75 0 00.298 1.206c1.544.57 3.16.99 4.831 1.243a3.75 3.75 0 107.48 0 24.583 24.583 0 004.83-1.244.75.75 0 00.298-1.205 8.217 8.217 0 01-2.118-5.52V9A6.75 6.75 0 0012 2.25zM9.75 18c0-.034 0-.067.002-.1a25.05 25.05 0 004.496 0l.002.1a2.25 2.25 0 11-4.5 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="text-gray-700 font-medium text-sm">Notifications</span>
                </div>
                <button onclick="toggleSwitch(this)" class="relative w-11 h-6 rounded-full bg-orange-500 transition-colors focus:outline-none" data-on="true">
                    <span class="absolute top-0.5 right-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform"></span>
                </button>
            </div>

            {{-- Security --}}
            <div onclick="document.getElementById('securityModal').classList.remove('hidden')"
                class="flex items-center justify-between px-5 py-4 cursor-pointer hover:bg-gray-50 transition">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="text-gray-700 font-medium text-sm">Security</span>
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </div>

        {{-- Log Out --}}
        <button onclick="logout()"
            class="w-full py-4 rounded-2xl bg-red-500 hover:bg-red-600 text-white font-bold transition active:scale-95 flex items-center justify-center gap-2 shadow-lg shadow-red-500/30">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Log Out
        </button>

    </main>

    {{-- Edit Profile Modal --}}
    <div id="editModal" class="hidden fixed inset-0 bg-black/50 flex items-end justify-center z-50">
        <div class="bg-white rounded-t-3xl w-full max-w-sm p-6 pb-8">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5"></div>
            <h3 class="text-gray-800 font-bold text-lg mb-5">Edit Profile</h3>

            <div id="editSuccess" class="hidden mb-3 p-3 rounded-xl bg-green-50 border border-green-200 text-green-600 text-sm"></div>
            <div id="editError" class="hidden mb-3 p-3 rounded-xl bg-red-50 border border-red-200 text-red-500 text-sm"></div>

            <form id="editForm" class="space-y-4">
                <div>
                    <label class="block text-gray-500 text-xs mb-1">Nom</label>
                    <input type="text" id="editName"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-800 text-sm
                               focus:outline-none focus:border-orange-400 transition">
                </div>
                <div>
                    <label class="block text-gray-500 text-xs mb-1">Email</label>
                    <input type="email" id="editEmail"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-800 text-sm
                               focus:outline-none focus:border-orange-400 transition">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                        class="flex-1 py-3 rounded-xl bg-gray-100 text-gray-600 font-medium text-sm hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" id="editBtn"
                        class="flex-1 py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold text-sm transition active:scale-95">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Security Modal --}}
    <div id="securityModal" class="hidden fixed inset-0 bg-black/50 flex items-end justify-center z-50">
        <div class="bg-white rounded-t-3xl w-full max-w-sm p-6 pb-8">
            <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5"></div>
            <h3 class="text-gray-800 font-bold text-lg mb-5">Security</h3>

            <div id="pwdSuccess" class="hidden mb-3 p-3 rounded-xl bg-green-50 border border-green-200 text-green-600 text-sm"></div>
            <div id="pwdError" class="hidden mb-3 p-3 rounded-xl bg-red-50 border border-red-200 text-red-500 text-sm"></div>

            <form id="pwdForm" class="space-y-4">
                <div>
                    <label class="block text-gray-500 text-xs mb-1">Mot de passe actuel</label>
                    <input type="password" id="currentPwd"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-800 text-sm
                               focus:outline-none focus:border-orange-400 transition">
                </div>
                <div>
                    <label class="block text-gray-500 text-xs mb-1">Nouveau mot de passe</label>
                    <input type="password" id="newPwd" minlength="8"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-800 text-sm
                               focus:outline-none focus:border-orange-400 transition">
                </div>
                <div>
                    <label class="block text-gray-500 text-xs mb-1">Confirmer</label>
                    <input type="password" id="confirmPwd"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 text-gray-800 text-sm
                               focus:outline-none focus:border-orange-400 transition">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('securityModal').classList.add('hidden')"
                        class="flex-1 py-3 rounded-xl bg-gray-100 text-gray-600 font-medium text-sm hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" id="pwdBtn"
                        class="flex-1 py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold text-sm transition active:scale-95">
                        Modifier
                    </button>
                </div>
            </form>
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
            <a href="/history" class="flex flex-col items-center gap-1 text-gray-400 hover:text-orange-500 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                <span class="text-xs">History</span>
            </a>
            <a href="/profile" class="flex flex-col items-center gap-1 text-orange-500">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                </svg>
                <span class="text-xs font-semibold">Profile</span>
            </a>
        </div>
    </nav>
</div>

<script>
const token = localStorage.getItem('token');
if (!token) window.location.href = '/login';

function toggleSwitch(btn) {
    const isOn = btn.dataset.on === 'true';
    btn.dataset.on = !isOn;
    const dot = btn.querySelector('span');
    if (isOn) {
        btn.classList.replace('bg-orange-500', 'bg-gray-300');
        dot.style.transform = 'translateX(0)';
        dot.style.right = 'auto';
        dot.style.left = '2px';
    } else {
        btn.classList.replace('bg-gray-300', 'bg-orange-500');
        dot.style.left = 'auto';
        dot.style.right = '2px';
    }
}

async function loadProfile() {
    const res = await fetch('/api/me', {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    });
    if (res.status === 401) { window.location.href = '/login'; return; }
    const user = await res.json();

    document.getElementById('profileName').textContent = user.name;
    document.getElementById('profileEmail').textContent = user.email;
    document.getElementById('editName').value = user.name;
    document.getElementById('editEmail').value = user.email;
    localStorage.setItem('user', JSON.stringify(user));
}

document.getElementById('editForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('editBtn');
    const success = document.getElementById('editSuccess');
    const error = document.getElementById('editError');
    success.classList.add('hidden');
    error.classList.add('hidden');
    btn.disabled = true;
    btn.textContent = '...';

    const res = await fetch('/api/profile', {
        method: 'PUT',
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
            name: document.getElementById('editName').value,
            email: document.getElementById('editEmail').value,
        })
    });

    const data = await res.json();
    btn.disabled = false;
    btn.textContent = 'Enregistrer';

    if (res.ok) {
        document.getElementById('profileName').textContent = data.user.name;
        document.getElementById('profileEmail').textContent = data.user.email;
        localStorage.setItem('user', JSON.stringify(data.user));
        success.textContent = '✓ Profil mis à jour';
        success.classList.remove('hidden');
        setTimeout(() => document.getElementById('editModal').classList.add('hidden'), 1200);
    } else {
        const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
        error.textContent = errors;
        error.classList.remove('hidden');
    }
});

document.getElementById('pwdForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('pwdBtn');
    const success = document.getElementById('pwdSuccess');
    const error = document.getElementById('pwdError');
    success.classList.add('hidden');
    error.classList.add('hidden');

    const newPwd = document.getElementById('newPwd').value;
    const confirm = document.getElementById('confirmPwd').value;
    if (newPwd !== confirm) {
        error.textContent = 'Les mots de passe ne correspondent pas';
        error.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.textContent = '...';

    const res = await fetch('/api/change-password', {
        method: 'PUT',
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
            current_password: document.getElementById('currentPwd').value,
            password: newPwd,
            password_confirmation: confirm,
        })
    });

    const data = await res.json();
    btn.disabled = false;
    btn.textContent = 'Modifier';

    if (res.ok) {
        success.textContent = '✓ ' + data.message;
        success.classList.remove('hidden');
        document.getElementById('pwdForm').reset();
        setTimeout(() => document.getElementById('securityModal').classList.add('hidden'), 1200);
    } else {
        error.textContent = data.message || 'Erreur';
        error.classList.remove('hidden');
    }
});

async function logout() {
    await fetch('/api/logout', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token }
    });
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '/login';
}

loadProfile();
</script>
@endsection
