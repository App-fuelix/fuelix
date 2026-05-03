@extends('layouts.app', ['authPage' => true])

@section('title', 'Reset Password')

@section('content')
<section class="w-full max-w-md rounded-xl border border-fuelix-line bg-[#0d1526] p-8 shadow-fuelix">
    <h1 class="text-2xl font-bold">Choose New Password</h1>
    <form class="mt-8 space-y-4" method="POST" action="#">
        @csrf
        <input type="password" name="password" placeholder="New password" class="w-full rounded-md border border-fuelix-line bg-fuelix-panel px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
        <input type="password" name="password_confirmation" placeholder="Confirm password" class="w-full rounded-md border border-fuelix-line bg-fuelix-panel px-4 py-3 text-sm outline-none focus:border-fuelix-blue">
        <button type="submit" class="w-full rounded-md bg-fuelix-blue px-4 py-3 text-sm font-bold text-white">UPDATE PASSWORD</button>
    </form>
</section>
@endsection
