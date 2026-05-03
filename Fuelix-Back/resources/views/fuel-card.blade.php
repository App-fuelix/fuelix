@extends('layouts.app')

@section('title', 'Fuel Card')
@section('page-title', 'Fuel Card')
@section('page-subtitle', 'Fuel cards belong to client accounts')

@section('content')
<section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-8 text-center shadow-fuelix">
    <h2 class="text-xl font-semibold">No admin fuel card</h2>
    <p class="mx-auto mt-3 max-w-xl text-sm text-slate-400">
        Fuel cards and card transactions are attached to client accounts in Firestore. Use Users or Transactions to review real client activity.
    </p>
    <div class="mt-6 flex justify-center gap-3">
        <a href="/users" class="rounded-lg bg-fuelix-blue px-5 py-2 text-sm font-semibold">View Users</a>
        <a href="/history" class="rounded-lg border border-fuelix-line px-5 py-2 text-sm text-slate-300">View Transactions</a>
    </div>
</section>
@endsection
