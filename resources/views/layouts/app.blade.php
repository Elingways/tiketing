<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SuperTicket Kampus — Sistem Tiketing & Hirarki Dinamis' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        .superapp-gradient {
            background: radial-gradient(circle at 15% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 45%),
                        radial-gradient(circle at 85% 10%, rgba(16, 185, 129, 0.12) 0%, transparent 40%),
                        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }
        .hero-mesh {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 45%, #4338ca 100%);
        }
    </style>
</head>
<body class="min-h-full font-sans text-slate-800 antialiased superapp-gradient" x-data="{ mobileMenuOpen: false, switcherOpen: false }">

@php
    $groupLabels = [
        'main' => 'Menu Utama',
        'ticketing' => 'Layanan Tiketing',
        'management' => 'Manajemen Superuser',
    ];
    $roleBadgeColors = [
        'indigo' => 'bg-indigo-500/15 text-indigo-700 border-indigo-200',
        'emerald' => 'bg-emerald-500/15 text-emerald-700 border-emerald-200',
        'amber' => 'bg-amber-500/15 text-amber-700 border-amber-200',
        'rose' => 'bg-rose-500/15 text-rose-700 border-rose-200',
        'violet' => 'bg-violet-500/15 text-violet-700 border-violet-200',
        'sky' => 'bg-sky-500/15 text-sky-700 border-sky-200',
        'teal' => 'bg-teal-500/15 text-teal-700 border-teal-200',
        'fuchsia' => 'bg-fuchsia-500/15 text-fuchsia-700 border-fuchsia-200',
    ];
    $iconColorStyles = [
        'indigo' => 'bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-indigo-500/25',
        'emerald' => 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-emerald-500/25',
        'amber' => 'bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-amber-500/25',
        'rose' => 'bg-gradient-to-br from-rose-500 to-pink-600 text-white shadow-rose-500/25',
        'violet' => 'bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-violet-500/25',
        'sky' => 'bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-sky-500/25',
        'teal' => 'bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-teal-500/25',
        'fuchsia' => 'bg-gradient-to-br from-fuchsia-500 to-pink-600 text-white shadow-fuchsia-500/25',
    ];
@endphp

<div class="flex min-h-screen">
    <!-- Sidebar Desktop ala SuperApp -->
    <aside class="hidden lg:flex lg:w-72 lg:flex-col lg:fixed lg:inset-y-0 bg-white/85 backdrop-blur-xl border-r border-slate-200/80 z-30">
        <!-- Brand Header -->
        <div class="flex items-center gap-3.5 px-6 h-20 border-b border-slate-100">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 via-violet-600 to-fuchsia-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                @include('partials.icon', ['name' => 'ticket', 'class' => 'w-6 h-6'])
            </div>
            <div>
                <div class="flex items-center gap-1.5">
                    <span class="font-extrabold text-lg tracking-tight text-slate-900">SuperTicket</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-200">Pro</span>
                </div>
                <p class="text-xs text-slate-500">Dynamic Hierarchy App</p>
            </div>
        </div>

        <!-- Card Identitas Role & Jalur Hirarki Aktif -->
        <div class="px-4 pt-4">
            <div class="p-3.5 rounded-2xl bg-slate-900 text-white shadow-md relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 rounded-full bg-indigo-500/20 blur-xl"></div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur flex items-center justify-center font-bold text-sm">
                        {{ $authUser->initials() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-slate-300 truncate">{{ $authUser->name }}</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span class="font-bold text-xs text-white">{{ $authUser->role?->name ?? 'Tanpa Role' }}</span>
                        </div>
                    </div>
                </div>
                @if($authUser->role)
                    <div class="mt-3 pt-2.5 border-t border-white/10 text-[11px] text-slate-300 flex items-center justify-between">
                        <span>Atasan Hirarki:</span>
                        <span class="font-semibold text-amber-300">
                            {{ $authUser->role->parent?->name ?? ($authUser->isSuperuser() ? 'Semua Hirarki' : 'Puncak Hirarki') }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Navigasi Menu Dinamis Berdasarkan Hak Akses Role -->
        <nav class="flex-1 px-4 py-4 space-y-5 overflow-y-auto">
            @foreach(['main', 'ticketing', 'management'] as $groupKey)
                @if(isset($sidebarMenus[$groupKey]) && $sidebarMenus[$groupKey]->count() > 0)
                    <div>
                        <p class="px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            {{ $groupLabels[$groupKey] ?? ucfirst($groupKey) }}
                        </p>
                        <div class="space-y-1">
                            @foreach($sidebarMenus[$groupKey] as $menuItem)
                                @php
                                    $isActive = request()->routeIs($menuItem->route_name) || (request()->routeIs('tickets.show') && $menuItem->route_name === 'tickets.inbox');
                                    $iconStyle = $iconColorStyles[$menuItem->color] ?? $iconColorStyles['indigo'];
                                    $hasRoute = \Illuminate\Support\Facades\Route::has($menuItem->route_name);
                                @endphp
                                <a href="{{ $hasRoute ? route($menuItem->route_name) : '#' }}"
                                   class="group flex items-center justify-between px-3 py-2.5 rounded-2xl text-sm font-semibold transition-all {{ $isActive ? 'bg-indigo-50/90 text-indigo-700 shadow-xs ring-1 ring-indigo-200/80' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-8 h-8 rounded-xl flex items-center justify-center shadow-sm transition-transform group-hover:scale-105 {{ $iconStyle }}">
                                            @include('partials.icon', ['name' => $menuItem->icon, 'class' => 'w-4 h-4'])
                                        </span>
                                        <span class="truncate">{{ $menuItem->name }}</span>
                                    </div>
                                    @if($menuItem->route_name === 'tickets.inbox' && $roleInboxCount > 0)
                                        <span class="px-2 py-0.5 text-xs font-extrabold rounded-full bg-rose-500 text-white shadow-xs">
                                            {{ $roleInboxCount }}
                                        </span>
                                    @elseif($menuItem->badge_text)
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 group-hover:bg-white">
                                            {{ $menuItem->badge_text }}
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <!-- Logout Footer -->
        <div class="p-4 border-t border-slate-200/70">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:text-rose-600 hover:bg-rose-50 border border-slate-200/80 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    Keluar Sesi
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 lg:pl-72 flex flex-col min-w-0 pb-24 lg:pb-10">
        <!-- Top Glass Header dengan Fitur Quick Role Switcher -->
        <header class="sticky top-0 z-20 bg-white/80 backdrop-blur-xl border-b border-slate-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="lg:hidden flex items-center gap-2">
                        <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold">
                            @include('partials.icon', ['name' => 'ticket', 'class' => 'w-5 h-5'])
                        </div>
                        <span class="font-extrabold text-slate-900">SuperTicket</span>
                    </a>
                    <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
                        <span class="font-semibold text-slate-700">Alur Hirarki Anda:</span>
                        @if($authUser->role)
                            <div class="flex items-center gap-1 flex-wrap">
                                @foreach($authUser->role->getUpwardChainNames() as $idx => $chainName)
                                    @if($idx > 0)
                                        <span class="text-slate-400">→</span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded-lg font-semibold {{ $idx === 0 ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $chainName }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Role Switcher (Memudahkan Simulasi Pindah Role Mahasiswa -> Dosen -> Kaprodi -> Superuser) -->
                <div class="flex items-center gap-2.5">
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" type="button"
                                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl text-xs font-bold bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-sm hover:opacity-95 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                            <span>Ganti Role Simulasi: {{ $authUser->role?->name }}</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>

                        <div x-show="open" @click.outside="open = false" x-cloak
                             x-transition
                             class="absolute right-0 mt-2 w-80 rounded-3xl bg-white shadow-2xl border border-slate-200/90 p-3 z-50">
                            <div class="px-2 py-1.5 border-b border-slate-100 mb-2">
                                <p class="text-xs font-extrabold text-slate-900">Simulasi Pindah Role Cepat (1-Klik)</p>
                                <p class="text-[11px] text-slate-500">Uji alur tiket dari Mahasiswa → Dosen → Kaprodi → Dekan atau Superuser.</p>
                            </div>
                            <div class="space-y-1 max-h-80 overflow-y-auto">
                                @foreach($allDemoUsers as $demoUser)
                                    <form action="{{ route('auth.quick-switch', $demoUser) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="w-full flex items-center justify-between p-2 rounded-2xl text-left transition {{ $demoUser->id === $authUser->id ? 'bg-indigo-50 border border-indigo-200' : 'hover:bg-slate-50' }}">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                                    {{ $demoUser->initials() }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold text-slate-900 truncate">{{ $demoUser->name }}</p>
                                                    <p class="text-[11px] text-slate-500 truncate">
                                                        Role: <strong class="text-indigo-600">{{ $demoUser->role?->name }}</strong>
                                                        @if($demoUser->role?->parent)
                                                            (→ {{ $demoUser->role->parent->name }})
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                            @if($demoUser->id === $authUser->id)
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-600 text-white">Aktif</span>
                                            @else
                                                <span class="text-[10px] font-semibold text-indigo-600">Pilih →</span>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Notifications -->
        <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            @if(session('success'))
                <div class="mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0">✓</span>
                        <p class="text-sm font-semibold">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0">!</span>
                        <p class="text-sm font-semibold">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800">
                    <p class="text-xs font-bold uppercase tracking-wider mb-1">Periksa Input Anda:</p>
                    <ul class="list-disc list-inside text-xs space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Main Page Content -->
        <main class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-4 flex-1">
            @yield('content')
        </main>
    </div>
</div>

<!-- Mobile Bottom Navigation Bar ala SuperApp -->
<div class="lg:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-xl border-t border-slate-200/90 px-3 py-2 z-40">
    <div class="flex items-center justify-around">
        @foreach($launcherMenus->take(5) as $mMenu)
            @php
                $hasRoute = \Illuminate\Support\Facades\Route::has($mMenu->route_name);
                $activeMobile = request()->routeIs($mMenu->route_name);
            @endphp
            <a href="{{ $hasRoute ? route($mMenu->route_name) : '#' }}"
               class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl {{ $activeMobile ? 'text-indigo-600 font-bold' : 'text-slate-500' }}">
                <div class="relative">
                    @include('partials.icon', ['name' => $mMenu->icon, 'class' => 'w-5 h-5'])
                    @if($mMenu->route_name === 'tickets.inbox' && $roleInboxCount > 0)
                        <span class="absolute -top-1.5 -right-2 px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-rose-500 text-white">
                            {{ $roleInboxCount }}
                        </span>
                    @endif
                </div>
                <span class="text-[10px] truncate max-w-[68px]">{{ $mMenu->name }}</span>
            </a>
        @endforeach
    </div>
</div>

</body>
</html>
