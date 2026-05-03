@extends('layouts.app', ['authPage' => true])

@section('title', 'Forgot Password')

@section('content')
<section class="w-full max-w-md rounded-xl border border-fuelix-line bg-[#0d1526] p-8 shadow-fuelix">
    <h1 class="text-2xl font-bold">Reset Password</h1>
    <p class="mt-2 text-sm text-slate-400">Enter your email to receive reset instructions.</p>
    <form class="mt-8 space-y-4" method="POST" action="#">
        @csrf
        <input type="email" name="email" placeholder="Email" class="w-full rounded-md border border-fuelix-line bg-fuelix-panel px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
        <button type="submit" class="w-full rounded-md bg-fuelix-blue px-4 py-3 text-sm font-bold text-white">SEND RESET LINK</button>
    </form>
    <a href="/login" class="mt-5 inline-block text-sm text-fuelix-blue2">Back to login</a>
</section>
@endsection
