@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'Platform preferences and security controls')

@section('content')
@php
    $settings = session('admin_settings', [
        'language' => 'English',
        'currency' => 'TND',
        'refresh_interval' => '5 min',
        'email_notifications' => true,
    ]);
@endphp

@if (session('settings_success'))
    <div class="mb-5 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-200">
        {{ session('settings_success') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
        {{ $errors->first() }}
    </div>
@endif

<div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-6 shadow-fuelix">
        <h2 class="font-semibold">Platform Settings</h2>
        <form method="POST" action="/settings" class="mt-5 divide-y divide-fuelix-line">
            @csrf
            <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium">Theme</p>
                    <p class="text-xs text-slate-500">Admin dashboard theme</p>
                </div>
                <span class="rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-2 text-sm text-slate-300">Dark</span>
            </div>
            <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium">Language</p>
                    <p class="text-xs text-slate-500">Dashboard language</p>
                </div>
                <select name="language" class="rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-2 text-sm text-slate-300">
                    @foreach(['English', 'French'] as $option)
                        <option value="{{ $option }}" @selected($settings['language'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium">Currency</p>
                    <p class="text-xs text-slate-500">Default billing currency</p>
                </div>
                <select name="currency" class="rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-2 text-sm text-slate-300">
                    @foreach(['TND', 'USD', 'EUR'] as $option)
                        <option value="{{ $option }}" @selected($settings['currency'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium">Data refresh</p>
                    <p class="text-xs text-slate-500">Dashboard refresh interval</p>
                </div>
                <select name="refresh_interval" class="rounded-lg border border-fuelix-line bg-[#0d1526] px-4 py-2 text-sm text-slate-300">
                    @foreach(['5 min', '10 min', '15 min', '30 min'] as $option)
                        <option value="{{ $option }}" @selected($settings['refresh_interval'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-medium">Email Notifications</p>
                    <p class="text-xs text-slate-500">Receive admin alerts by email</p>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="email_notifications" value="1" @checked($settings['email_notifications'])>
                    Enabled
                </label>
            </div>
            <button type="submit" class="mt-5 rounded-lg bg-fuelix-blue px-5 py-2 text-sm font-semibold">Save Settings</button>
        </form>
    </section>

    <section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-6 shadow-fuelix">
        <h2 class="font-semibold">Security Settings</h2>
        <div class="mt-5 space-y-4">
            <div class="rounded-xl border border-fuelix-line bg-[#0d1526] p-4">
                <p class="font-medium">Change Password</p>
                <p class="mt-1 text-xs text-slate-500">Require current password before update.</p>
                <a href="/profile#profile-form" class="mt-4 inline-block rounded-lg bg-fuelix-blue px-4 py-2 text-sm font-semibold">Update</a>
            </div>
            <div class="rounded-xl border border-fuelix-line bg-[#0d1526] p-4">
                <p class="font-medium">Two Factor Authentication</p>
                <p class="mt-1 text-xs text-slate-500">Not configured yet for this admin panel.</p>
                <span class="mt-4 inline-block rounded-lg border border-fuelix-line px-4 py-2 text-sm text-slate-300">Coming soon</span>
            </div>
            <div class="rounded-xl border border-red-500/30 bg-red-500/5 p-4">
                <p class="font-medium text-fuelix-red">Danger Zone</p>
                <p class="mt-1 text-xs text-slate-500">Destructive actions are disabled in this safe admin view.</p>
                <span class="mt-4 inline-block rounded-lg border border-red-500/40 px-4 py-2 text-sm text-fuelix-red">Disabled</span>
            </div>
        </div>
    </section>
</div>
@endsection
