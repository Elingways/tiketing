@extends('layouts.app')

@section('content')
@php
    $statusColors = [
        'open' => 'bg-amber-50 text-amber-700 border-amber-200',
        'in_progress' => 'bg-sky-50 text-sky-700 border-sky-200',
        'escalated' => 'bg-violet-50 text-violet-700 border-violet-200',
        'waiting_next_approval' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
@endphp

<!-- Header Ringkas -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-lg font-bold text-slate-900">Dashboard {{ $role?->name }}</h1>
        <p class="text-xs text-slate-500 mt-0.5">
            {{ $user->name }}
            @if($role?->parent)
                • Hirarki Atasan: <strong class="text-slate-700">{{ $role->parent->name }}</strong>
            @endif
        </p>
    </div>

    <div class="flex items-center gap-2">
        @if($role?->can_create_ticket)
            <button type="button" @click="createTicketModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition">
                @include('partials.icon', ['name' => 'plus-circle', 'class' => 'w-4 h-4'])
                Buat Tiket
            </button>
        @endif
        @if($user->isSuperuser())
            <a href="{{ route('admin.hierarchy') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs border border-slate-200 transition">
                @include('partials.icon', ['name' => 'share', 'class' => 'w-4 h-4 text-slate-500'])
                Atur Hirarki
            </a>
        @endif
    </div>
</div>

<!-- 4 Ringkasan Utama -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="p-4 rounded-xl bg-white border border-slate-200">
        <p class="text-xs text-slate-500">Tiket Masuk Role {{ $role?->name }}</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['role_inbox_active'] }}</p>
    </div>
    <div class="p-4 rounded-xl bg-white border border-slate-200">
        <p class="text-xs text-slate-500">Diteruskan ke Atasan</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['escalated_or_forwarded'] }}</p>
    </div>
    <div class="p-4 rounded-xl bg-white border border-slate-200">
        <p class="text-xs text-slate-500">Total Tiket Saya</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['my_tickets_total'] }}</p>
    </div>
    <div class="p-4 rounded-xl bg-white border border-slate-200">
        <p class="text-xs text-slate-500">Sudah Di-Approve / Selesai</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['my_tickets_resolved'] }}</p>
    </div>
</div>

<!-- Menu Cepat Sesuai Role -->
<div class="mb-6">
    <div class="flex items-center justify-between mb-2.5">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Menu Akses ({{ $role?->name }})</h2>
        @if($user->isSuperuser())
            <a href="{{ route('admin.menus') }}" class="text-xs font-medium text-indigo-600 hover:underline">Kelola Menu</a>
        @endif
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        @foreach($launcherMenus as $appMenu)
            @php
                $hasRoute = \Illuminate\Support\Facades\Route::has($appMenu->route_name);
            @endphp
            @if($appMenu->route_name === 'tickets.create')
                <button type="button" @click="createTicketModal = true"
                        class="flex items-center gap-3 p-3 rounded-xl bg-white border border-slate-200 hover:border-indigo-400 transition text-left">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        @include('partials.icon', ['name' => $appMenu->icon, 'class' => 'w-4 h-4'])
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-xs text-slate-900 truncate">{{ $appMenu->name }}</p>
                        <p class="text-[11px] text-slate-400 truncate">Buka Modal</p>
                    </div>
                </button>
            @else
                <a href="{{ $hasRoute ? route($appMenu->route_name) : '#' }}"
                   class="flex items-center gap-3 p-3 rounded-xl bg-white border border-slate-200 hover:border-indigo-400 transition">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        @include('partials.icon', ['name' => $appMenu->icon, 'class' => 'w-4 h-4'])
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-xs text-slate-900 truncate">{{ $appMenu->name }}</p>
                        <p class="text-[11px] text-slate-400 truncate">{{ $appMenu->badge_text ?? 'Buka' }}</p>
                    </div>
                </a>
            @endif
        @endforeach
    </div>
</div>

<!-- Fokus Inti: Tiket Masuk Role & Riwayat Tiket Saya -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-8 space-y-6">
        @if($role?->can_receive_ticket || $user->isSuperuser())
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Tiket Masuk — Role {{ $role?->name }}</h2>
                        <p class="text-xs text-slate-500">Tiket dari role di bawah Anda yang menunggu diproses atau dieskalasi.</p>
                    </div>
                    <a href="{{ route('tickets.inbox') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Semua →</a>
                </div>

                @if($incomingTickets->isEmpty())
                    <div class="p-8 text-center text-xs text-slate-400">
                        Belum ada tiket masuk di antrean Role {{ $role?->name }}.
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($incomingTickets as $t)
                            <div class="flex items-center justify-between gap-4 px-5 py-3.5 hover:bg-slate-50 transition">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-semibold text-indigo-600">{{ $t->ticket_number }}</span>
                                        <a href="{{ route('tickets.show', $t) }}" class="text-xs font-semibold text-slate-900 hover:text-indigo-600 truncate">
                                            {{ $t->title }}
                                        </a>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Dari: <span class="font-medium text-slate-700">{{ $t->creator?->name }}</span> ({{ $t->creatorRole?->name }})
                                        • Prioritas: {{ $t->priorityLabel() }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if($t->attachment_path)
                                        <button type="button"
                                                @click="openDocPreview('{{ route('tickets.preview', $t) }}', '{{ route('tickets.attachment', $t) }}', '{{ addslashes($t->attachment_name ?? 'Dokumen') }}')"
                                                class="text-[11px] font-medium px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                                            📎 Preview
                                        </button>
                                    @endif
                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md border {{ $statusColors[$t->status] ?? 'bg-slate-100' }}">
                                        {{ $t->statusLabel() }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- Riwayat Tiket Saya -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Riwayat Tiket Saya</h2>
                    <p class="text-xs text-slate-500">Pantau tiket yang sudah di-approve maupun sedang berjalan.</p>
                </div>
                <a href="{{ route('tickets.my', ['tab' => 'approved']) }}" class="text-xs font-semibold text-indigo-600 hover:underline">Lihat Riwayat Approved →</a>
            </div>

            @if($myCreatedTickets->isEmpty())
                <div class="p-8 text-center text-xs text-slate-400">
                    Anda belum mengajukan tiket.
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($myCreatedTickets as $mt)
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5 hover:bg-slate-50 transition">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-semibold text-indigo-600">{{ $mt->ticket_number }}</span>
                                    <a href="{{ route('tickets.show', $mt) }}" class="text-xs font-semibold text-slate-900 hover:text-indigo-600 truncate">
                                        {{ $mt->title }}
                                    </a>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Posisi Hirarki: <strong class="text-slate-700">Role {{ $mt->currentRole?->name }}</strong>
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if($mt->attachment_path)
                                    <button type="button"
                                            @click="openDocPreview('{{ route('tickets.preview', $mt) }}', '{{ route('tickets.attachment', $mt) }}', '{{ addslashes($mt->attachment_name ?? 'Dokumen') }}')"
                                            class="text-[11px] font-medium px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                                        📎 Preview
                                    </button>
                                @endif
                                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md border {{ $statusColors[$mt->status] ?? 'bg-slate-100' }}">
                                    {{ $mt->statusLabel() }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Kolom Kanan: Struktur Hirarki Role -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-slate-900">Struktur Hirarki Role</h2>
                @if($user->isSuperuser())
                    <a href="{{ route('admin.hierarchy') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Edit</a>
                @endif
            </div>
            <div class="space-y-2">
                @foreach($rootRoles as $rootNode)
                    @include('partials.hierarchy-node', ['node' => $rootNode, 'highlightRoleId' => $role?->id])
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
