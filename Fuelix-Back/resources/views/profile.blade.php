@extends('layouts.app')

@section('title', 'Admin Profile')
@section('page-title', 'Admin Profile')
@section('page-subtitle', 'Account and security overview')

@section('content')
@if (session('profile_success'))
    <div class="mb-5 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-200">
        {{ session('profile_success') }}
    </div>
@endif

@if (session('password_success'))
    <div class="mb-5 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-200">
        {{ session('password_success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
        {{ $errors->first() }}
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-[.9fr_1.4fr]">
    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-8 text-center shadow-fuelix">
        <div class="mx-auto h-28 w-28 rounded-full bg-gradient-to-br from-slate-200 to-slate-500 ring-4 ring-fuelix-line"></div>
        <h2 class="mt-5 text-2xl font-bold">{{ auth()->user()->name ?? 'Admin User' }}</h2>
        <p class="text-sm text-slate-400">Administrator</p>
        <p class="mt-1 text-sm text-slate-500">{{ auth()->user()->email ?? 'admin@fuelix.local' }}</p>
        <p class="mt-5 text-xs text-slate-500">Last Login - 12 Mar 2026, 10:08 AM</p>
        <div class="mt-7 flex justify-center gap-3">
            <a href="#profile-form" class="rounded-lg bg-fuelix-blue px-5 py-2 text-sm font-semibold">Edit Profile</a>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="rounded-lg border border-fuelix-line px-5 py-2 text-sm text-slate-300">Log Out</button>
            </form>
        </div>
    </section>

    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-6 shadow-fuelix">
        <h2 class="font-semibold">Edit Admin Profile</h2>
        <form id="profile-form" method="POST" action="/profile" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="text-xs text-slate-500">Name</label>
                <input name="name" value="{{ old('name', auth()->user()->name) }}" class="mt-2 w-full rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
            </div>
            <div>
                <label class="text-xs text-slate-500">Email</label>
                <input name="email" type="email" value="{{ old('email', auth()->user()->email) }}" class="mt-2 w-full rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
            </div>
            <button type="submit" class="rounded-lg bg-fuelix-blue px-5 py-2 text-sm font-semibold">Save Profile</button>
        </form>

        <div class="my-6 border-t border-fuelix-line"></div>

        <h2 class="font-semibold">Change Password</h2>
        <form method="POST" action="/profile/password" class="mt-5 space-y-4">
            @csrf
            <input name="current_password" type="password" placeholder="Current password" class="w-full rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
            <input name="password" type="password" placeholder="New password" class="w-full rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
            <input name="password_confirmation" type="password" placeholder="Confirm new password" class="w-full rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
            <button type="submit" class="rounded-lg border border-fuelix-line px-5 py-2 text-sm text-slate-300">Update Password</button>
        </form>
    </section>
</div>
@endsection
