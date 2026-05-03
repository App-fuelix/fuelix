@extends('layouts.app', ['authPage' => true])

@section('title', 'FueliX Admin Login')

@section('content')
<section class="w-full max-w-md rounded-xl border border-fuelix-line bg-[#0d1526] p-8 shadow-fuelix">
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 h-12 w-12 rounded-xl bg-fuelix-blue grid place-items-center text-xl font-bold">F</div>
        <h1 class="text-2xl font-bold">FueliX</h1>
        <p class="mt-2 text-sm text-slate-400">Admin Login</p>
        <p class="mt-1 text-xs text-slate-500">Only Firestore accounts with role admin can sign in.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="space-y-4" method="POST" action="/login">
        @csrf
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" class="w-full rounded-md border border-fuelix-line bg-fuelix-panel px-4 py-3 text-sm text-white outline-none focus:border-fuelix-blue">
        <input type="password" name="password" placeholder="Password" class="w-full rounded-md border border-fuelix-line bg-fuelix-panel px-4 py-3 text-sm text-white outline-none focus:border-fuelix-blue">
        <label class="flex items-center gap-2 text-sm text-slate-400">
            <input type="checkbox" name="remember" class="rounded border-fuelix-line bg-fuelix-panel">
            Remember me
        </label>
        <button type="submit" class="w-full rounded-md bg-fuelix-blue px-4 py-3 text-sm font-bold text-white hover:bg-blue-500">LOGIN</button>
    </form>

    <div class="mt-5 text-center">
        <a href="/forgot-password" class="text-sm text-fuelix-blue2 hover:text-white">Forgot Password?</a>
    </div>
</section>
@endsection
