@extends('layouts.app', ['authPage' => true])

@section('title', 'FueliX Register')

@section('content')
<section class="w-full max-w-md rounded-xl border border-fuelix-line bg-[#0d1526] p-8 shadow-fuelix">
    <div class="mb-8 text-center">
        <div class="mx-auto mb-4 h-12 w-12 rounded-xl bg-fuelix-blue grid place-items-center text-xl font-bold">F</div>
        <h1 class="text-2xl font-bold">Create Admin</h1>
        <p class="mt-2 text-sm text-slate-400">Register a FueliX admin account</p>
    </div>
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="space-y-4" method="POST" action="/register">
        @csrf
        <input type="text" name="name" value="{{ old('name') }}" placeholder="Full name" class="w-full rounded-md border border-fuelix-line bg-fuelix-panel px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" class="w-full rounded-md border border-fuelix-line bg-fuelix-panel px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
        <input type="password" name="password" placeholder="Password" class="w-full rounded-md border border-fuelix-line bg-fuelix-panel px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
        <input type="password" name="password_confirmation" placeholder="Confirm password" class="w-full rounded-md border border-fuelix-line bg-fuelix-panel px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
        <button type="submit" class="w-full rounded-md bg-fuelix-blue px-4 py-3 text-sm font-bold text-white">CREATE ACCOUNT</button>
    </form>
    <div class="mt-5 text-center">
        <a href="/login" class="text-sm text-fuelix-blue2 hover:text-white">Already have an account?</a>
    </div>
</section>
@endsection
