<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FueliX Admin')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        fuelix: {
                            bg: '#111827',
                            panel: '#151f33',
                            panel2: '#1b2740',
                            line: '#263653',
                            blue: '#2f80ed',
                            blue2: '#56a4ff',
                            green: '#22c55e',
                            amber: '#f59e0b',
                            red: '#ef4444'
                        }
                    },
                    boxShadow: {
                        fuelix: '0 24px 70px rgba(0, 0, 0, .32)'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(47, 128, 237, .12), transparent 34rem),
                #111827;
        }
    </style>
</head>
<body class="min-h-screen bg-fuelix-bg text-slate-100 antialiased">
    @php
        $authPage = $authPage ?? false;
        $navItems = [
            ['label' => 'Dashboard', 'href' => '/dashboard', 'icon' => 'D'],
            ['label' => 'Users', 'href' => '/users', 'icon' => 'U'],
            ['label' => 'Transactions', 'href' => '/history', 'icon' => 'T'],
            ['label' => 'Analytics', 'href' => '/analytics', 'icon' => 'A'],
            ['label' => 'Settings', 'href' => '/settings', 'icon' => 'S'],
            ['label' => 'Profile', 'href' => '/profile', 'icon' => 'P'],
        ];
    @endphp

    @if($authPage)
        <main class="min-h-screen flex items-center justify-center px-6 py-10">
            @yield('content')
        </main>
    @else
        <div class="min-h-screen lg:flex">
            <aside class="hidden lg:flex lg:w-64 lg:flex-col border-r border-fuelix-line bg-[#0d1526]/95">
                <div class="h-16 flex items-center gap-3 px-6 border-b border-fuelix-line">
                    <div class="h-8 w-8 rounded-lg bg-fuelix-blue grid place-items-center font-bold">F</div>
                    <span class="text-lg font-bold tracking-tight">FueliX</span>
                </div>
                <nav class="flex-1 px-4 py-5 space-y-1">
                    @foreach($navItems as $item)
                        @php $active = request()->is(ltrim($item['href'], '/')); @endphp
                        <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition {{ $active ? 'bg-fuelix-blue text-white shadow-lg shadow-blue-900/30' : 'text-slate-400 hover:bg-fuelix-panel2 hover:text-white' }}">
                            <span class="w-5 text-center">{{ $item['icon'] }}</span>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
                <div class="p-4 text-xs text-slate-500 border-t border-fuelix-line">Admin workspace</div>
            </aside>

            <div class="flex-1 min-w-0">
                <header class="sticky top-0 z-20 h-16 border-b border-fuelix-line bg-fuelix-bg/90 backdrop-blur">
                    <div class="h-full flex items-center justify-between px-5 lg:px-8">
                        <div class="flex items-center gap-3">
                            <div class="lg:hidden h-8 w-8 rounded-lg bg-fuelix-blue grid place-items-center font-bold">F</div>
                            <div>
                                <h1 class="font-semibold">@yield('page-title', 'Dashboard')</h1>
                                <p class="text-xs text-slate-500">@yield('page-subtitle', 'FueliX admin control center')</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="h-9 rounded-lg border border-fuelix-line bg-fuelix-panel px-3 text-xs text-slate-300">Search</button>
                            <button class="h-9 rounded-lg border border-fuelix-line bg-fuelix-panel px-3 text-xs text-slate-300">Alerts</button>
                            <a href="/profile" class="h-9 w-9 rounded-full bg-gradient-to-br from-fuelix-blue to-fuelix-blue2 grid place-items-center text-sm font-bold">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</a>
                        </div>
                    </div>
                </header>

                <main class="px-5 py-6 lg:px-8">
                    @yield('content')
                </main>
            </div>
        </div>
    @endif

    @stack('scripts')
</body>
</html>
