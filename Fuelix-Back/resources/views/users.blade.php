@extends('layouts.app')

@section('title', 'Users Management')
@section('page-title', 'Users Management')
@section('page-subtitle', 'Manage drivers, admins, and account status')

@section('content')
<section class="rounded-xl border border-fuelix-line bg-fuelix-panel p-5 shadow-fuelix">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-semibold">Users</h2>
            <p class="text-xs text-slate-500">Account access and fuel activity overview</p>
        </div>
        <div class="flex gap-2">
            <input placeholder="Search user" class="rounded-lg border border-fuelix-line bg-[#0d1526] px-3 py-2 text-sm outline-none focus:border-fuelix-blue">
            <button class="rounded-lg border border-fuelix-line px-4 py-2 text-sm font-semibold text-slate-300">Firestore</button>
        </div>
    </div>

    @if (session('users_error'))
        <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
            {{ session('users_error') }}
        </div>
    @endif
    @if (session('users_success'))
        <div class="mb-5 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-200">
            {{ session('users_success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full min-w-[920px] text-left text-sm">
            <thead class="text-xs uppercase text-slate-500">
                <tr class="border-b border-fuelix-line">
                    <th class="py-3">ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Controls</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-fuelix-line">
                @forelse($users as $user)
                    @php
                        $role = ucfirst((string) ($user['role'] ?? 'user'));
                        $isAdmin = strtolower((string) ($user['role'] ?? 'user')) === 'admin';
                        $status = (string) ($user['status'] ?? (($user['is_active'] ?? true) ? 'Active' : 'Inactive'));
                        $isActive = strtolower($status) === 'active';
                        $created = (string) ($user['created_at'] ?? $user['createdAt'] ?? '-');
                        $created = $created !== '-' ? substr($created, 0, 10) : '-';
                    @endphp
                    <tr class="text-slate-300">
                        <td class="py-3 text-slate-500">{{ $user['id'] ?? '-' }}</td>
                        <td class="font-semibold text-white">{{ $user['name'] ?? 'Unnamed user' }}</td>
                        <td>{{ $user['email'] ?? '-' }}</td>
                        <td>{{ $role }}</td>
                        <td>
                            <span class="rounded px-2 py-1 text-xs {{ strtolower($status) === 'active' ? 'bg-green-500/15 text-fuelix-green' : (strtolower($status) === 'inactive' ? 'bg-red-500/15 text-fuelix-red' : 'bg-blue-500/15 text-fuelix-blue2') }}">{{ $status }}</span>
                        </td>
                        <td>{{ $created }}</td>
                        <td>
                            @if ($isAdmin)
                                <span class="rounded border border-fuelix-line px-2 py-1 text-xs text-slate-500">Protected</span>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    <a href="/users/{{ $user['id'] }}" class="rounded border border-fuelix-line px-2 py-1 text-xs text-slate-300">View/Edit</a>
                                    <form method="POST" action="/users/{{ $user['id'] }}/toggle">
                                        @csrf
                                        <button type="submit" class="rounded border border-fuelix-line px-2 py-1 text-xs {{ $isActive ? 'text-fuelix-amber' : 'text-fuelix-green' }}">{{ $isActive ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                    <form method="POST" action="/users/{{ $user['id'] }}" onsubmit="return confirm('Delete this user from Firestore?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded border border-red-500/40 px-2 py-1 text-xs text-fuelix-red">Delete</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-slate-500">
                            No Firestore users found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
