@extends('layouts.app')

@section('title', 'Connexion - Fuelix')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
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
            <p class="text-gray-400 mt-1">Connectez-vous à votre compte</p>
        </div>

        {{-- Card --}}
        <div class="bg-gray-900 rounded-2xl p-8 shadow-xl border border-gray-800">

            {{-- Error message --}}
            <div id="error-msg" class="hidden mb-5 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm"></div>

            <form id="loginForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" id="email" placeholder="vous@exemple.com" required
                        class="w-full px-4 py-3 rounded-xl bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                               focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Mot de passe</label>
                    <div class="relative">
                        <input type="password" id="password" placeholder="••••••••" required
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
                </div>

                <div class="flex justify-end">
                    <a href="/forgot-password" class="text-sm text-orange-400 hover:text-orange-300 transition">
                        Mot de passe oublié?
                    </a>
                </div>

                <button type="submit" id="submitBtn"
                    class="w-full py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold
                           transition transform active:scale-95 flex items-center justify-center gap-2">
                    <span id="btnText">Se connecter</span>
                    <svg id="btnSpinner" class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                </button>
            </form>

            <p class="text-center text-gray-500 text-sm mt-6">
                Pas encore de compte?
                <a href="/register" class="text-orange-400 hover:text-orange-300 font-medium transition">S'inscrire</a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('btnSpinner');
    const errorMsg = document.getElementById('error-msg');

    btn.disabled = true;
    btnText.textContent = 'Connexion...';
    spinner.classList.remove('hidden');
    errorMsg.classList.add('hidden');

    try {
        const res = await fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
            })
        });

        const data = await res.json();

        if (res.ok) {
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            window.location.href = '/dashboard';
        } else {
            errorMsg.textContent = data.message || 'Email ou mot de passe incorrect';
            errorMsg.classList.remove('hidden');
        }
    } catch (err) {
        errorMsg.textContent = 'Erreur de connexion au serveur';
        errorMsg.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Se connecter';
        spinner.classList.add('hidden');
    }
});
</script>
@endsection
