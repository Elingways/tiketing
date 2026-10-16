@extends('layouts.app')

@section('content')
@php
    $launcherColors = [
        'indigo' => 'from-indigo-500 to-indigo-600 shadow-indigo-500/25',
        'emerald' => 'from-emerald-500 to-teal-600 shadow-emerald-500/25',
        'amber' => 'from-amber-500 to-orange-500 shadow-amber-500/25',
        'rose' => 'from-rose-500 to-pink-600 shadow-rose-500/25',
        'violet' => 'from-violet-500 to-purple-600 shadow-violet-500/25',
        'sky' => 'from-sky-500 to-blue-600 shadow-sky-500/25',
        'teal' => 'from-teal-500 to-emerald-600 shadow-teal-500/25',
        'fuchsia' => 'from-fuchsia-500 to-pink-600 shadow-fuchsia-500/25',
    ];
    $statusColors = [
        'open' => 'bg-amber-50 text-amber-700 border-amber-200',
        'in_progress' => 'bg-sky-50 text-sky-700 border-sky-200',
        'escalated' => 'bg-violet-50 text-violet-700 border-violet-200',
        'waiting_next_approval' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
@endphp

<!-- 1. SuperApp Hero & Hierarchy Wallet Card -->
<div class="hero-mesh rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden mb-6">
    <div class="absolute -right-16 -top-16 w-72 h-72 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
    <div class="absolute right-24 -bottom-20 w-56 h-56 rounded-full bg-emerald-400/20 blur-2xl pointer-events-none"></div>

    <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
        <div class="lg:col-span-7 space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Dashboard Dinamis Role: {{ $role?->name }}</span>
                @if($user->department)
                    <span class="text-white/60">• {{ $user->department }}</span>
                @endif
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Halo, {{ $user->name }} 👋
            </h1>
            <p class="text-xs sm:text-sm text-indigo-100/90 max-w-2xl leading-relaxed">
                @if($user->isSuperuser())
                    Anda masuk sebagai <strong>Superuser</strong>. Anda memiliki kendali penuh untuk membuat Role baru, menyusun <strong>Pohon Hirarki Role</strong> (contoh: Mahasiswa → Dosen → Kaprodi), serta mengatur <strong>Menu SuperApp Dinamis</strong>.
                @elseif($role?->can_receive_ticket)
                    Selamat datang di <strong>Workspace Role {{ $role->name }}</strong>. Tiket yang diajukan oleh role di bawah Anda
                    ({{ $role->children->pluck('name')->join(', ') ?: 'Bawahan' }})
                    otomatis masuk ke dashboard ini dan dapat Anda selesaikan atau teruskan/eskalasi ke atasan Anda
                    (<strong>{{ $role->parent?->name ?? 'Puncak Hirarki' }}</strong>).
                @else
                    Selamat datang di portal layanan terpadu. Tiket yang Anda ajukan akan otomatis diteruskan ke
                    <strong>Role {{ $role?->parent?->name ?? 'Tujuan' }}</strong> dan diproses secara berjenjang sesuai hirarki.
                @endif
            </p>

            <!-- Quick Action Buttons inside Hero -->
            <div class="pt-2 flex flex-wrap items-center gap-3">
                @if($role?->can_create_ticket)
                    <a href="{{ route('tickets.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs shadow-lg shadow-emerald-500/25 transition">
                        @include('partials.icon', ['name' => 'plus-circle', 'class' => 'w-4 h-4'])
                        Buat Tiket Baru
                    </a>
                @endif
                @if($role?->can_receive_ticket)
                    <a href="{{ route('tickets.inbox') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-white/15 hover:bg-white/25 backdrop-blur text-white font-bold text-xs border border-white/20 transition">
                        @include('partials.icon', ['name' => 'inbox-arrow-down', 'class' => 'w-4 h-4'])
                        Buka Inbox Role {{ $role->name }} ({{ $stats['role_inbox_active'] }} Aktif)
                    </a>
                @endif
                @if($user->isSuperuser())
                    <a href="{{ route('admin.hierarchy') }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-extrabold text-xs shadow-lg transition">
                        @include('partials.icon', ['name' => 'share', 'class' => 'w-4 h-4'])
                        Kelola Hirarki Role
                    </a>
                @endif
            </div>
        </div>

        <!-- Bento Summary Metrics inside Hero -->
        <div class="lg:col-span-5 grid grid-cols-2 gap-3">
            <div class="p-4 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                <p class="text-[11px] text-indigo-200 font-semibold">Tiket Masuk ke Role {{ $role?->name }}</p>
                <p class="text-2xl sm:text-3xl font-extrabold mt-1">{{ $stats['role_inbox_active'] }}</p>
                <p class="text-[11px] text-emerald-300 mt-1">Menunggu tindakan Anda</p>
            </div>
            <div class="p-4 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                <p class="text-[11px] text-indigo-200 font-semibold">Dieskalasi / Naik Hirarki</p>
                <p class="text-2xl sm:text-3xl font-extrabold mt-1">{{ $stats['escalated_or_forwarded'] }}</p>
                <p class="text-[11px] text-amber-300 mt-1">Diteruskan ke {{ $role?->parent?->name ?? 'Atasan' }}</p>
            </div>
            <div class="p-4 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                <p class="text-[11px] text-indigo-200 font-semibold">Tiket Diajukan Saya</p>
                <p class="text-2xl sm:text-3xl font-extrabold mt-1">{{ $stats['my_tickets_total'] }}</p>
                <p class="text-[11px] text-indigo-200 mt-1">{{ $stats['my_tickets_resolved'] }} tiket selesai</p>
            </div>
            <div class="p-4 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                <p class="text-[11px] text-indigo-200 font-semibold">Total Role & Menu</p>
                <p class="text-2xl sm:text-3xl font-extrabold mt-1">{{ $stats['total_roles'] }} <span class="text-sm font-normal text-indigo-200">Role</span></p>
                <p class="text-[11px] text-indigo-200 mt-1">{{ $stats['total_menus'] }} Menu Dinamis</p>
            </div>
        </div>
    </div>
</div>

<!-- 2. SuperApp Launcher Grid (Menu Dinamis Berdasarkan Role) -->
<div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-extrabold text-slate-900">SuperApp Menu Launcher</h2>
                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-200">
                    Dinamis Sesuai Role {{ $role?->name }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
                Daftar fitur dan modul yang diaktifkan oleh Superuser untuk Role <strong>{{ $role?->name }}</strong>.
            </p>
        </div>
        @if($user->isSuperuser())
            <a href="{{ route('admin.menus') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1">
                <span>+ Kustomisasi Menu & Hak Akses</span>
            </a>
        @endif
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-4 gap-3.5">
        @foreach($launcherMenus as $appMenu)
            @php
                $grad = $launcherColors[$appMenu->color] ?? $launcherColors['indigo'];
                $hasRoute = \Illuminate\Support\Facades\Route::has($appMenu->route_name);
            @endphp
            <a href="{{ $hasRoute ? route($appMenu->route_name) : '#' }}"
               class="group relative p-4 rounded-2xl bg-slate-50/70 hover:bg-white border border-slate-200/70 hover:border-indigo-300 hover:shadow-md transition-all flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2 mb-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $grad }} text-white flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform">
                        @include('partials.icon', ['name' => $appMenu->icon, 'class' => 'w-6 h-6'])
                    </div>
                    @if($appMenu->route_name === 'tickets.inbox' && $stats['role_inbox_active'] > 0)
                        <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-500 text-white">
                            {{ $stats['role_inbox_active'] }} Masuk
                        </span>
                    @elseif($appMenu->badge_text)
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-white text-slate-600 border border-slate-200">
                            {{ $appMenu->badge_text }}
                        </span>
                    @endif
                </div>
                <div>
                    <h3 class="font-bold text-sm text-slate-900 group-hover:text-indigo-600 transition">{{ $appMenu->name }}</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">{{ $appMenu->description }}</p>
                </div>
            </a>
        @endforeach
    </div>
</div>

<!-- 3. Grid Utama: Tiket Masuk Role (Misal: Tiket dari Mahasiswa ke Dosen) & Visualisasi Pohon Hirarki -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Kolom Kiri (7 cols): Tiket Masuk ke Role Ini & Tiket Saya -->
    <div class="lg:col-span-7 space-y-6">
        @if($role?->can_receive_ticket || $user->isSuperuser())
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
                <div class="flex items-center justify-between gap-2 mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-extrabold text-slate-900">
                                Tiket Masuk — Dashboard Role {{ $role?->name }}
                            </h2>
                            <span class="px-2.5 py-0.5 text-xs font-extrabold rounded-full bg-amber-100 text-amber-800">
                                {{ $incomingTickets->count() }} Tiket
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            @if($role && $role->children->count() > 0)
                                Tiket yang masuk dari <strong>{{ $role->children->pluck('name')->join(', ') }}</strong> ke meja <strong>{{ $role->name }}</strong>.
                                @if($role->parent)
                                    Dapat dieskalasi/diteruskan ke <strong>{{ $role->parent->name }}</strong>.
                                @endif
                            @else
                                Daftar tiket yang saat ini berada pada antrean Role {{ $role?->name }}.
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('tickets.inbox') }}" class="text-xs font-bold text-indigo-600 hover:underline shrink-0">
                        Lihat Semua →
                    </a>
                </div>

                @if($incomingTickets->isEmpty())
                    <div class="p-8 rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-center">
                        <p class="text-sm font-bold text-slate-700">Belum ada tiket masuk di antrean Role {{ $role?->name }}</p>
                        <p class="text-xs text-slate-500 mt-1">
                            Saat role bawahan ({{ $role?->children->pluck('name')->join(', ') ?: 'Mahasiswa/Dosen' }}) membuat atau meneruskan tiket ke {{ $role?->name }}, tiket akan muncul di sini.
                        </p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($incomingTickets as $t)
                            @php
                                $sBadge = $statusColors[$t->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            @endphp
                            <a href="{{ route('tickets.show', $t) }}"
                               class="block p-4 rounded-2xl border border-slate-200/80 hover:border-indigo-400 bg-slate-50/40 hover:bg-white transition shadow-2xs">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-bold text-indigo-600">{{ $t->ticket_number }}</span>
                                        <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border {{ $sBadge }}">
                                            {{ $t->statusLabel() }}
                                        </span>
                                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $t->workflow_type === 'tiered_approval' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                            {{ $t->workflow_type === 'tiered_approval' ? 'Approval Berjenjang (Tahap '.$t->approval_step.')' : 'Mode Eskalasi/Disposisi' }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] text-slate-400">{{ $t->created_at->diffForHumans() }}</span>
                                </div>

                                <h3 class="font-bold text-sm text-slate-900">{{ $t->title }}</h3>
                                <p class="text-xs text-slate-500 line-clamp-2 mt-1">{{ $t->description }}</p>

                                <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-xs">
                                    <div class="flex items-center gap-2 text-slate-600">
                                        <span class="font-semibold text-slate-900">Dari: {{ $t->creator?->name }}</span>
                                        <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-bold text-[10px]">
                                            Role {{ $t->creatorRole?->name }}
                                        </span>
                                    </div>
                                    <span class="font-bold text-indigo-600 inline-flex items-center gap-1">
                                        Proses / Tindak Lanjuti →
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- Tiket yang Diajukan oleh User Ini (Misal Mahasiswa memantau tiketnya sudah di Dosen atau naik ke Kaprodi) -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Tiket yang Saya Ajukan</h2>
                    <p class="text-xs text-slate-500">Pantau posisi role hirarki yang sedang menangani tiket Anda saat ini.</p>
                </div>
                <a href="{{ route('tickets.create') }}" class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-500 transition">
                    + Buat Tiket
                </a>
            </div>

            @if($myCreatedTickets->isEmpty())
                <div class="p-6 rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-center">
                    <p class="text-xs text-slate-500">Anda belum mengajukan tiket. Klik tombol <strong>+ Buat Tiket</strong> untuk mencoba alur hirarki.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($myCreatedTickets as $mt)
                        <a href="{{ route('tickets.show', $mt) }}"
                           class="block p-4 rounded-2xl border border-slate-200/80 hover:border-indigo-300 transition">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="font-mono text-xs font-bold text-indigo-600">{{ $mt->ticket_number }}</span>
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border {{ $statusColors[$mt->status] ?? 'bg-slate-100' }}">
                                    {{ $mt->statusLabel() }}
                                </span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-900">{{ $mt->title }}</h3>
                            <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                                <span>Kategori: <strong class="text-slate-700">{{ $mt->category?->name }}</strong></span>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-900 text-white font-semibold text-[11px]">
                                    Posisi Hirarki Saat Ini: Role {{ $mt->currentRole?->name }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Kolom Kanan (5 cols): Pohon Hirarki Live & Panduan Alur -->
    <div class="lg:col-span-5 space-y-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Pohon Hirarki Role Saat Ini</h2>
                    <p class="text-xs text-slate-500">Struktur eskalasi & approval tiket dari bawah ke atas.</p>
                </div>
                @if($user->isSuperuser())
                    <a href="{{ route('admin.hierarchy') }}" class="px-3 py-1.5 rounded-xl bg-violet-50 text-violet-700 border border-violet-200 text-xs font-bold hover:bg-violet-100 transition">
                        Ubah Hirarki →
                    </a>
                @endif
            </div>

            <div class="space-y-3">
                @foreach($rootRoles as $rootNode)
                    @include('partials.hierarchy-node', ['node' => $rootNode, 'highlightRoleId' => $role?->id])
                @endforeach
            </div>
        </div>

        <!-- Katalog Kategori Layanan & Mode Workflow -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-extrabold text-slate-900">Alur Kategori Tiket Aktif</h3>
                @if($user->isSuperuser())
                    <a href="{{ route('admin.categories') }}" class="text-xs font-bold text-indigo-600 hover:underline">Kelola</a>
                @endif
            </div>
            <div class="space-y-2.5">
                @foreach($categories as $cat)
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/70">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold text-xs text-slate-900">{{ $cat->name }}</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $cat->workflow_type === 'tiered_approval' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $cat->workflow_type === 'tiered_approval' ? 'Approval Berjenjang' : 'Eskalasi / Disposisi' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Tujuan Awal: <strong>{{ $cat->initialTargetRole?->name ?? 'Atasan Langsung' }}</strong>
                            @if($cat->maxApprovalRole)
                                → Puncak Hirarki: <strong>{{ $cat->maxApprovalRole->name }}</strong>
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
