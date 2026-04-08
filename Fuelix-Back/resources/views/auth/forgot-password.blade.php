@extends('layouts.app')

@section('title', 'Mot de passe oublié - Fuelix')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-orange-500 mb-4">
                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white">Fuelix</h1>
        </div>

        {{-- Step 1: Enter email --}}
        <div id="stepEmail" class="bg-gray-900 rounded-2xl p-8 shadow-xl border border-gray-800">
            <div class="mb-6">
                <div class="w-12 h-12 rounded-2xl bg-orange-500/20 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h2 class="text-white font-bold text-xl">Mot de passe oublié?</h2>
                <p class="text-gray-400 text-sm mt-1">Entrez votre email pour recevoir un lien de réinitialisation</p>
            </div>

            <div id="emailError" class="hidden mb-4 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm"></div>
            <div id="emailSuccess" class="hidden mb-4 p-4 rounded-xl bg-green-500/10 border border-green-500/30 text-green-400 text-sm"></div>

            <form id="forgotForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                    <input type="email" id="email" placeholder="vous@exemple.com" required
                        class="w-full px-4 py-3 rounded-xl bg-gray-800 border border-gray-700 text-white placeholder-gray-500
                               focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
                </div>

                <button type="submit" id="submitBtn"
                    class="w-full py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold
                           transition active:scale-95 flex items-center justify-center gap-2">
                    <span id="btnText">Envoyer le lien</span>
                    <svg id="btnSpinner" class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                </button>
            </form>

            <p class="text-center text-gray-500 text-sm mt-6">
                <a href="/login" class="text-orange-400 hover:text-orange-300 transition">← Retour à la connexion</a>
            </p>
        </div>

        {{-- Step 2: Email sent confirmation --}}
        <div id="stepSent" class="hidden bg-gray-900 rounded-2xl p-8 shadow-xl border border-gray-800 text-center">
            <div class="w-16 h-16 rounded-full bg-green-500/20 flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-white font-bold text-xl mb-2">Email envoyé!</h2>
            <p class="text-gray-400 text-sm mb-1">Un lien de réinitialisation a été envoyé à</p>
            <p id="sentEmail" class="text-orange-400 font-medium text-sm mb-6"></p>
            <p class="text-gray-500 text-xs mb-6">Vérifiez votre boîte mail et cliquez sur le lien. Il expire dans 60 minutes.</p>

            <button onclick="resend()" id="resendBtn"
                class="w-full py-3 rounded-xl border border-gray-700 text-gray-300 hover:border-orange-500 hover:text-orange-400
                       transition text-sm font-medium mb-3">
                Renvoyer l'email
            </button>
            <a href="/login"
                class="block w-full py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold
                       transition text-sm text-center">
                Retour à la connexion
            </a>
        </div>

    </div>
</div>

<script>
let lastEmail = '';

document.getElementById('forgotForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    await sendReset();
});

async function sendReset() {
    const email = document.getElementById('email').value;
    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const spinner = document.getElementById('btnSpinner');
    const errorEl = document.getElementById('emailError');
    const successEl = document.getElementById('emailSuccess');

    errorEl.classList.add('hidden');
    successEl.classList.add('hidden');
    btn.disabled = true;
    btnText.textContent = 'Envoi...';
    spinner.classList.remove('hidden');

    try {
        const res = await fetch('/api/forgot-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email })
        });

        const data = await res.json();

        if (res.ok) {
            lastEmail = email;
            document.getElementById('sentEmail').textContent = email;
            document.getElementById('stepEmail').classList.add('hidden');
            document.getElementById('stepSent').classList.remove('hidden');
        } else {
            errorEl.textContent = data.message || 'Une erreur est survenue';
            errorEl.classList.remove('hidden');
        }
    } catch (err) {
        errorEl.textContent = 'Erreur de connexion au serveur';
        errorEl.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Envoyer le lien';
        spinner.classList.add('hidden');
    }
}

async function resend() {
    const btn = document.getElementById('resendBtn');
    btn.disabled = true;
    btn.textContent = 'Envoi...';

    try {
        const res = await fetch('/api/forgot-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email: lastEmail })
        });

        const data = await res.json();
        btn.textContent = res.ok ? '✓ Email renvoyé!' : data.message || 'Erreur';
        setTimeout(() => {
            btn.disabled = false;
            btn.textContent = 'Renvoyer l\'email';
        }, 3000);
    } catch {
        btn.disabled = false;
        btn.textContent = 'Renvoyer l\'email';
    }
}
</script>
@endsection
