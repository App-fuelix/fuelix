@extends('layouts.app')

@section('title', 'Inscription - Fuelix')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-orange-500 mb-4">
                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white">Fuelix</h1>
            <p class="text-gray-400 mt-1">Créez votre compte</p>
        </div>

        {{-- Card --}}
        <div class="bg-gray-900 rounded-2xl p-8 shadow-xl border border-gray-800">

            <div id="error-msg" class="hidden mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm"></div>
            <div id="success-msg" class="hidden mb-5 p-4 rounded-xl bg-green-500/10 border border-green-500/30 text-green-400 text-sm"></div>

            <form id="registerForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nom complet</label>
                    <input type="text" id="name" placeholder="Votre nom" required
                        class="w-full px-4 py-3 rounded-xl bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                               focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" id="email" placeholder="vous@exemple.com" required
                        class="w-full px-4 py-3 rounded-xl bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                               focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Mot de passe</label>
                    <div class="relative">
                        <input type="password" id="password" placeholder="Min. 8 caractères" required minlength="8"
                            class="w-full px-4 py-3 rounded-xl bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                                   focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition pr-12">
                        <button type="button" onclick="togglePassword('password')"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                                       -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    {{-- Password strength --}}
                    <div class="mt-2 flex gap-1">
                        <div id="s1" class="h-1 flex-1 rounded-full bg-gray-700 transition-colors"></div>
                        <div id="s2" class="h-1 flex-1 rounded-full bg-gray-700 transition-colors"></div>
                        <div id="s3" class="h-1 flex-1 rounded-full bg-gray-700 transition-colors"></div>
                        <div id="s4" class="h-1 flex-1 rounded-full bg-gray-700 transition-colors"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Confirmer le mot de passe</label>
                    <div class="relative">
                        <input type="password" id="password_confirmation" placeholder="••••••••" required
                            class="w-full px-4 py-3 rounded-xl bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                                   focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition pr-12">
                        <button type="button" onclick="togglePassword('password_confirmation')"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                                       -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" id="submitBtn"
                    class="w-full py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold
                           transition transform active:scale-95 flex items-center justify-center gap-2">
                    <span id="btnText">Créer mon compte</span>
                    <svg id="btnSpinner" class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                </button>
            </form>

            <p class="text-center text-gray-500 text-sm mt-6">
                Déjà un compte?
                <a href="/login" class="text-orange-400 hover:text-orange-300 font-medium transition">Se connecter</a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Password strength indicator
document.getElementById('password').addEventListener('input', function () {
    const val = this.value;
    const bars = [document.getElementById('s1'), document.getElementById('s2'),
                  document.getElementById('s3'), document.getElementById('s4')];
    let strength = 0;
    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
    bars.forEach((bar, i) => {
        bar.className = 'h-1 flex-1 rounded-full transition-colors ' +
            (i < strength ? colors[strength - 1] : 'bg-gray-700');
    });
});

document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('btnSpinner');
    const errorMsg = document.getElementById('error-msg');
    const successMsg = document.getElementById('success-msg');

    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;

    if (password !== confirmation) {
        errorMsg.textContent = 'Les mots de passe ne correspondent pas';
        errorMsg.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btnText.textContent = 'Création...';
    spinner.classList.remove('hidden');
    errorMsg.classList.add('hidden');
    successMsg.classList.add('hidden');

    try {
        const res = await fetch('/api/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                password: password,
                password_confirmation: confirmation,
            })
        });

        const data = await res.json();

        if (res.ok) {
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            successMsg.textContent = 'Compte créé! Redirection...';
            successMsg.classList.remove('hidden');
            setTimeout(() => window.location.href = '/dashboard', 1000);
        } else {
            const errors = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
            errorMsg.textContent = errors || 'Une erreur est survenue';
            errorMsg.classList.remove('hidden');
        }
    } catch (err) {
        errorMsg.textContent = 'Erreur de connexion au serveur';
        errorMsg.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Créer mon compte';
        spinner.classList.add('hidden');
    }
});
</script>
@endsection
