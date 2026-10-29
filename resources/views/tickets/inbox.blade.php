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

<div x-data="{
    staffDetail: null,
}">

    <!-- Toast Notification untuk Inline Actions (Priority & Status) -->
    <div x-data="{ show: false, message: '' }"
         @toast.window="show = true; message = $event.detail.message; setTimeout(() => { show = false }, 3500)"
         x-show="show" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-3"
         class="fixed bottom-5 right-5 z-50 flex items-center gap-2.5 bg-slate-900 text-white px-4 py-3 rounded-xl shadow-2xl text-xs font-medium border border-slate-700">
        <span class="flex h-2 w-2 rounded-full bg-emerald-400"></span>
        <span x-text="message"></span>
    </div>

    <!-- Header & Scope Tabs (SaaS Style) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Dashboard</span>
                <span>/</span>
                <span class="text-slate-900 font-semibold">
                    @if($roleType === 'head')
                        Monitoring &amp; Antrean Kepala Staff
                    @elseif($roleType === 'admin')
                        Triase Helpdesk Admin IT
                    @elseif($roleType === 'staff')
                        Pekerjaan Staff
                    @else
                        All Tickets
                    @endif
                </span>
                @if($user->role)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                        Role: {{ $user->role->name }}
                    </span>
                @endif
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                @if($roleType === 'head')
                    Dashboard Kepala Staff — Antrean &amp; Progres Tim
                @elseif($roleType === 'admin')
                    Dashboard Admin IT — Manajemen &amp; Triase Seluruh Tiket
                @elseif($roleType === 'staff')
                    Dashboard Staff IT — Antrean Pekerjaan Saya
                @else
                    Tiket Masuk &amp; Manajemen Antrean
                @endif
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                @if($roleType === 'head')
                    Pantau nama staff, progres pengerjaan, dan jumlah tiket yang sedang dikerjakan.
                @elseif($roleType === 'admin')
                    Kelola antrean tiket, ganti prioritas secara cepat, dan distribusikan tiket ke teknisi sesuai keahlian.
                @elseif($roleType === 'staff')
                    Kerjakan tugas yang diarahkan kepada Anda, checklist poin pekerjaan (%), dan laporkan kendala kerja.
                @else
                    Kelola antrean tiket masuk dan proses persetujuan sesuai hirarki role Anda.
                @endif
            </p>
        </div>

        <div class="flex items-center gap-1.5 bg-slate-100/80 p-1 rounded-xl border border-slate-200/80 self-start md:self-auto">
            <a href="{{ route('tickets.inbox', ['scope' => 'current_role', 'category_id' => $categoryId, 'search' => $search]) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-medium transition {{ $scope === 'current_role' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                Antrean {{ $user->role?->name ?? 'Role' }}
            </a>
            <a href="{{ route('tickets.inbox', ['scope' => 'history_role', 'category_id' => $categoryId, 'search' => $search]) }}"
               class="px-3.5 py-1.5 rounded-lg text-xs font-medium transition {{ $scope === 'history_role' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                Riwayat Diteruskan
            </a>
            @if($user->isSuperuser())
                <a href="{{ route('tickets.inbox', ['scope' => 'all_system', 'category_id' => $categoryId, 'search' => $search]) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-medium transition {{ $scope === 'all_system' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900' }}">
                    Semua Tiket
                </a>
            @endif
        </div>
    </div>

    <!-- MVC View Architecture: Render partial sesuai peran dari folder masing-masing -->
    @if($roleType === 'head')
        @include('tickets.dashboards.head.index')
    @elseif($roleType === 'admin')
        @include('tickets.dashboards.admin.index')
    @elseif($roleType === 'staff')
        @include('tickets.dashboards.staff.index')
    @else
        @include('tickets.dashboards.general.index')
    @endif

    <!-- Shared Modal: Detail Tiket per Staff (Folder Shared) -->
    @include('tickets.dashboards.shared.staff-detail-modal')

</div>
@endsection
