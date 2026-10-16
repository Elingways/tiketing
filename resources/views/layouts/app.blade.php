<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SuperTicket — Sistem Tiketing & Hirarki Role' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full font-sans text-slate-800 antialiased bg-slate-50">

@php
    $groupLabels = [
        'main' => 'Utama',
        'ticketing' => 'Tiketing',
        'management' => 'Manajemen',
    ];
@endphp

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="hidden lg:flex lg:w-60 lg:flex-col lg:fixed lg:inset-y-0 bg-white border-r border-slate-200/80 z-30">
        <div class="flex items-center gap-2.5 px-5 h-14 border-b border-slate-100">
            <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                @include('partials.icon', ['name' => 'ticket', 'class' => 'w-4 h-4'])
            </div>
            <span class="font-bold text-sm text-slate-900 tracking-tight">SuperTicket</span>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-5 overflow-y-auto">
            @foreach(['main', 'ticketing', 'management'] as $groupKey)
                @if(isset($sidebarMenus[$groupKey]) && $sidebarMenus[$groupKey]->count() > 0)
                    <div>
                        <p class="px-2.5 mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            {{ $groupLabels[$groupKey] ?? ucfirst($groupKey) }}
                        </p>
                        <div class="space-y-0.5">
                            @foreach($sidebarMenus[$groupKey] as $menuItem)
                                @php
                                    $isActive = request()->routeIs($menuItem->route_name) || (request()->routeIs('tickets.show') && $menuItem->route_name === 'tickets.inbox');
                                    $hasRoute = \Illuminate\Support\Facades\Route::has($menuItem->route_name);
                                @endphp
                                <a href="{{ $hasRoute ? route($menuItem->route_name) : '#' }}"
                                   class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition {{ $isActive ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        @include('partials.icon', ['name' => $menuItem->icon, 'class' => 'w-4 h-4 shrink-0 ' . ($isActive ? 'text-white' : 'text-slate-400')])
                                        <span class="truncate">{{ $menuItem->name }}</span>
                                    </div>
                                    @if($menuItem->route_name === 'tickets.inbox' && $roleInboxCount > 0)
                                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded {{ $isActive ? 'bg-white text-slate-900' : 'bg-indigo-600 text-white' }}">
                                            {{ $roleInboxCount }}
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="p-3 border-t border-slate-100">
            <div class="flex items-center justify-between gap-2 px-2 py-1">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-900 truncate">{{ $authUser->name }}</p>
                    <p class="text-[11px] text-slate-400 truncate">{{ $authUser->role?->name }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Keluar" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 lg:pl-60 flex flex-col min-w-0 pb-20 lg:pb-10">
        <!-- Ultra-clean Top Header -->
        <header class="sticky top-0 z-20 bg-white border-b border-slate-200/80">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="font-medium text-slate-700">{{ $authUser->name }}</span>
                    <span class="text-slate-300">•</span>
                    <span> Hirarki: <strong class="text-slate-700">{{ implode(' → ', $authUser->role?->getUpwardChainNames() ?? []) }}</strong></span>
                </div>

                <!-- Role Switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" type="button"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200/80 text-slate-700 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                        <span>Role: <strong>{{ $authUser->role?->name }}</strong></span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>

                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute right-0 mt-1.5 w-60 rounded-xl bg-white shadow-lg border border-slate-200 p-1.5 z-50">
                        <div class="px-2.5 py-1 border-b border-slate-100 mb-1">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Pindah Role / Akun</p>
                        </div>
                        <div class="max-h-72 overflow-y-auto space-y-0.5">
                            @foreach($allDemoUsers as $demoUser)
                                <form action="{{ route('auth.quick-switch', $demoUser) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left text-xs transition {{ $demoUser->id === $authUser->id ? 'bg-slate-100 text-slate-900 font-semibold' : 'hover:bg-slate-50 text-slate-600' }}">
                                        <div class="truncate pr-2">
                                            <p class="truncate">{{ $demoUser->name }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $demoUser->role?->name }}</p>
                                        </div>
                                        @if($demoUser->id === $authUser->id)
                                            <span class="text-[10px] font-semibold text-indigo-600">Aktif</span>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Alerts -->
        <div class="max-w-5xl w-full mx-auto px-4 sm:px-6 pt-4">
            @if(session('success'))
                <div class="mb-4 px-4 py-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 px-4 py-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 px-4 py-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Content -->
        <main class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-4 flex-1">
            @yield('content')
        </main>
    </div>
</div>

<!-- Mobile Bottom Nav -->
<div class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 px-2 py-1.5 z-40">
    <div class="flex items-center justify-around">
        @foreach($launcherMenus->take(5) as $mMenu)
            @php
                $hasRoute = \Illuminate\Support\Facades\Route::has($mMenu->route_name);
                $activeMobile = request()->routeIs($mMenu->route_name);
            @endphp
            <a href="{{ $hasRoute ? route($mMenu->route_name) : '#' }}"
               class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-lg {{ $activeMobile ? 'text-indigo-600 font-semibold' : 'text-slate-500' }}">
                @include('partials.icon', ['name' => $mMenu->icon, 'class' => 'w-5 h-5'])
                <span class="text-[10px] truncate max-w-[64px]">{{ $mMenu->name }}</span>
            </a>
        @endforeach
    </div>
</div>

</body>
</html>
