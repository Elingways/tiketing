@extends('layouts.app')

@section('content')
<div x-data="{
    staffDetail: {{ $selectedStaffId ? (int) $selectedStaffId : 'null' }},
    selectedStaffFilter: '{{ $selectedStaffId ? (string) $selectedStaffId : 'all' }}',
    searchQuery: '',
    statusFilter: 'all',
    toggleStaffFilter(id) {
        if (this.selectedStaffFilter === String(id)) {
            this.selectedStaffFilter = 'all';
        } else {
            this.selectedStaffFilter = String(id);
        }
    },
    resetFilters() {
        this.selectedStaffFilter = 'all';
        this.searchQuery = '';
        this.statusFilter = 'all';
    }
}">

    <!-- Toast Notification untuk Inline Actions -->
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

    <!-- Page Header Modern -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100/80 shadow-2xs">
                @include('partials.icon', ['name' => 'chart-bar', 'class' => 'w-5 h-5'])
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-lg font-bold text-slate-900 tracking-tight">Staff Progres</h1>
                    @if($user->role)
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                            Unit {{ $user->role->name }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Monitoring Kinerja &amp; Beban Kerja Tim — Pantau alokasi tiket, persentase penyelesaian, dan status kendala staf secara terpusat.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <a href="{{ route('tickets.inbox') }}"
               class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-1.5 cursor-pointer">
                <span>&larr;</span>
                <span>Kembali ke Antrean Tiket</span>
            </a>
        </div>
    </div>

    <!-- Summary Stat Cards Row (4 Modern KPI Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- 1. Total Staf -->
        <div class="bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Total Tim Staf</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                    @include('partials.icon', ['name' => 'users', 'class' => 'w-4 h-4'])
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ $totalStaffCount }} <span class="text-xs font-normal text-slate-400">teknisi</span></div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px]">
                    <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold">{{ $activeStaffCount }} aktif</span>
                    <span class="text-slate-400">memegang pekerjaan</span>
                </div>
            </div>
        </div>

        <!-- 2. Tiket Sedang Dikerjakan -->
        <div class="bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Tiket Sedang Dikerjakan</span>
                <span class="w-8 h-8 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600">
                    @include('partials.icon', ['name' => 'ticket', 'class' => 'w-4 h-4'])
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ $totalTicketsHandled }} <span class="text-xs font-normal text-slate-400">tiket</span></div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-500">
                    <span class="px-1.5 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-100 font-semibold">Aktif</span>
                    <span class="text-slate-400">sedang diproses tim</span>
                </div>
            </div>
        </div>

        <!-- 3. Rata-rata Progres Tim -->
        <div class="bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Rata-rata Progres Tim</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                    @include('partials.icon', ['name' => 'check-circle', 'class' => 'w-4 h-4'])
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ round($avgProgress ?? 0) }}%</div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-emerald-700 font-medium">
                    <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold">Terselesaikan</span>
                    <span class="text-slate-400">akumulasi poin (%)</span>
                </div>
            </div>
        </div>

        <!-- 4. Kendala / Blocked -->
        <div class="bg-white rounded-2xl p-4.5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">Terkendala / Blocked</span>
                <span class="w-8 h-8 rounded-xl {{ $blockedCount > 0 ? 'bg-rose-50 border border-rose-100 text-rose-600' : 'bg-slate-50 border border-slate-100 text-slate-400' }} flex items-center justify-center">
                    @include('partials.icon', ['name' => 'exclamation-circle', 'class' => 'w-4 h-4'])
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ $blockedCount }} <span class="text-xs font-normal text-slate-400">tiket</span></div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] font-medium">
                    @if($blockedCount > 0)
                        <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 font-semibold">Butuh Tindakan</span>
                        <span class="text-slate-400">atasan/vendor</span>
                    @else
                        <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold">Lancar</span>
                        <span class="text-slate-400">tidak ada hambatan</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Filter & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 mb-6 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <!-- Live Search -->
        <div class="relative flex-1 max-w-md">
            <input type="text" x-model="searchQuery"
                   placeholder="Cari nomor tiket, judul permohonan, atau staf..."
                   class="w-full pl-9 pr-8 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50/50 focus:bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
            <div class="absolute left-3 top-2.5 text-slate-400 pointer-events-none">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <button type="button" x-show="searchQuery" @click="searchQuery = ''"
                    class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs cursor-pointer">
                ✕
            </button>
        </div>

        <!-- Filter Controls -->
        <div class="flex items-center gap-2 flex-wrap">
            <!-- Status Filter Chips -->
            <div class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200/70">
                <button type="button" @click="statusFilter = 'all'"
                        :class="statusFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer">
                    Semua
                </button>
                <button type="button" @click="statusFilter = 'in_progress'"
                        :class="statusFilter === 'in_progress' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer">
                    Normal
                </button>
                <button type="button" @click="statusFilter = 'blocked'"
                        :class="statusFilter === 'blocked' ? 'bg-white text-rose-700 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg text-xs transition cursor-pointer flex items-center gap-1">
                    <span>Blocked</span>
                    @if($blockedCount > 0)
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    @endif
                </button>
            </div>

            <!-- Reset Button -->
            <button type="button" x-show="selectedStaffFilter !== 'all' || searchQuery !== '' || statusFilter !== 'all'"
                    @click="resetFilters()"
                    class="px-3 py-1.5 rounded-xl border border-rose-200 bg-rose-50/70 text-rose-700 text-xs font-semibold hover:bg-rose-100 transition cursor-pointer flex items-center gap-1">
                <span>✕</span>
                <span>Reset Filter</span>
            </button>
        </div>
    </div>

    <!-- Active Filter Notification Bar (if filtered by staff) -->
    <div x-show="selectedStaffFilter !== 'all'" x-cloak
         class="mb-6 px-4 py-2.5 rounded-xl bg-indigo-50/80 border border-indigo-200/80 flex items-center justify-between text-xs">
        <div class="flex items-center gap-2 text-indigo-900 font-medium">
            <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
            <span>Menampilkan data tiket yang difilter untuk staf yang dipilih.</span>
        </div>
        <button type="button" @click="selectedStaffFilter = 'all'"
                class="font-bold text-indigo-700 hover:text-indigo-900 underline cursor-pointer">
            Tampilkan Semua Staf
        </button>
    </div>

    <!-- Section: Kartu Beban Kerja Staf (Interactive Team Grid) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Staff Progres</h2>
                <p class="text-xs text-slate-500 mt-0.5">Daftar staf teknisi beserta status beban kerja saat ini. Klik kartu staf untuk memfilter tabel tiket di bawah.</p>
            </div>
            <span class="px-3 py-1 rounded-xl bg-slate-100 border border-slate-200/70 text-slate-700 text-xs font-semibold">
                {{ $staffProgress->count() }} Teknisi Terdaftar
            </span>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($staffProgress as $progress)
                @php
                    $isBlocked = ($progress['blocked'] ?? 0) > 0;
                    $percent = $progress['percent'] ?? 0;
                @endphp
                <div class="rounded-2xl border p-4.5 transition cursor-pointer flex flex-col justify-between"
                     :class="selectedStaffFilter === '{{ $progress['user']->id }}'
                         ? 'border-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/15 shadow-sm'
                         : 'border-slate-200/80 bg-white hover:border-slate-300 hover:shadow-xs'"
                     @click="toggleStaffFilter('{{ $progress['user']->id }}')">
                    <div>
                        <!-- Header Card Staf -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white flex items-center justify-center text-sm font-bold shrink-0 shadow-2xs">
                                    {{ strtoupper(substr($progress['user']->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-slate-900 truncate">{{ $progress['user']->name }}</h3>
                                    <span class="text-[11px] text-slate-500 block truncate">
                                        {{ $progress['user']->keahlian ?? ($progress['user']->jabatan ?? 'Teknisi') }}
                                    </span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $isBlocked ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' }}">
                                {{ $isBlocked ? 'Blocked' : 'Normal' }}
                            </span>
                        </div>

                        <!-- Progress Bar & Tiket Count -->
                        <div class="mt-4 pt-3 border-t border-slate-100">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-medium text-slate-600">{{ $progress['tickets'] }} tiket berjalan</span>
                                <span class="font-bold text-emerald-700">{{ $progress['percent'] === null ? '0%' : $progress['percent'] . '%' }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full bg-emerald-500 transition-all duration-300" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        <!-- Quick Tickets Preview -->
                        <div class="mt-3 space-y-1.5">
                            @foreach($progress['open_tickets']->take(2) as $ot)
                                <div class="text-[11px] p-2 rounded-xl bg-slate-50/80 border border-slate-100 flex items-center justify-between gap-2">
                                    <div class="truncate min-w-0">
                                        <span class="font-mono font-bold text-indigo-600 mr-1">{{ $ot->ticket_number }}</span>
                                        <span class="text-slate-700 truncate">{{ $ot->title }}</span>
                                    </div>
                                    <span class="font-bold text-slate-600 shrink-0">{{ $ot->progressPercent() ?? 0 }}%</span>
                                </div>
                            @endforeach
                            @if($progress['tickets'] > 2)
                                <p class="text-[10px] text-slate-400 text-center font-medium">+{{ $progress['tickets'] - 2 }} tiket lainnya</p>
                            @endif
                        </div>
                    </div>

                    <!-- Footer Tombol Detail & Filter Status -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400 font-medium">
                            @if($isBlocked)
                                <span class="font-bold text-rose-600">{{ $progress['blocked'] }} tiket terkendala</span>
                            @else
                                <span class="text-emerald-700 font-medium">Siap ditugaskan</span>
                            @endif
                        </span>
                        <div class="flex items-center gap-1.5" @click.stop>
                            <button type="button"
                                    @click="staffDetail = {{ (int) $progress['user']->id }}"
                                    class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white text-xs font-semibold transition flex items-center gap-1 shadow-2xs cursor-pointer">
                                <span>Detail</span>
                                <span>&rarr;</span>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-xs text-slate-400">
                    Belum ada staff terdaftar dalam unit ini.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Section: Rincian Seluruh Pekerjaan yang Sedang Berjalan (Tabel Dinamis) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Daftar Tiket yang Sedang Dikerjakan Seluruh Staf</h2>
                <p class="text-xs text-slate-500 mt-0.5">Tinjau perkembangan checklist pekerjaan, penilaian prioritas, dan catatan kendala secara real-time.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                    {{ $totalTicketsHandled }} Tiket Aktif
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/70 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4 min-w-[150px]">Ticket ID</th>
                        <th class="py-3.5 px-4 min-w-[240px]">Subject &amp; Deskripsi</th>
                        <th class="py-3.5 px-4 min-w-[170px]">Staff Penanggung Jawab</th>
                        <th class="py-3.5 px-4 min-w-[150px]">Poin Pekerjaan (%)</th>
                        <th class="py-3.5 px-4 min-w-[130px]">Prioritas</th>
                        <th class="py-3.5 px-4 min-w-[110px]">Status</th>
                        <th class="py-3.5 px-4 min-w-[90px] text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @php $hasRows = false; @endphp
                    @foreach($staffProgress as $progress)
                        @foreach($progress['open_tickets'] as $openTicket)
                            @php $hasRows = true; @endphp
                            <tr class="hover:bg-slate-50/80 transition"
                                x-show="(selectedStaffFilter === 'all' || selectedStaffFilter === '{{ $progress['user']->id }}')
                                    && (statusFilter === 'all' || '{{ $openTicket->work_status }}' === statusFilter || (statusFilter === 'blocked' && '{{ $openTicket->work_status }}' === 'blocked'))
                                    && ('{{ strtolower($openTicket->ticket_number . ' ' . $openTicket->title . ' ' . $progress['user']->name . ' ' . ($progress['user']->keahlian ?? '')) }}'.includes(searchQuery.toLowerCase()))">
                                <td class="py-4 px-4 align-top whitespace-nowrap">
                                    <a href="{{ route('tickets.show', $openTicket) }}" class="font-mono font-bold text-indigo-600 hover:text-indigo-800 transition">
                                        {{ $openTicket->ticket_number }}
                                    </a>
                                    <div class="mt-1">
                                        @include('tickets.partials.dates', ['ticket' => $openTicket, 'format' => 'd M Y'])
                                    </div>
                                </td>

                                <td class="py-4 px-4 align-top">
                                    <a href="{{ route('tickets.show', $openTicket) }}" class="font-bold text-slate-900 hover:text-indigo-600 block leading-snug">
                                        {{ $openTicket->title }}
                                    </a>
                                    <span class="text-[11px] text-slate-500 block mt-0.5">
                                        {{ $openTicket->category?->name ?? 'Layanan' }} · Pemohon: <span class="text-slate-700 font-medium">{{ $openTicket->creator?->name }}</span>
                                    </span>
                                    @if($openTicket->progress_note)
                                        <div class="mt-2 text-[11px] text-slate-700 bg-amber-50/80 p-2.5 rounded-xl border border-amber-200/80 leading-relaxed">
                                            <span class="font-bold text-amber-900 block mb-0.5">Catatan Staf:</span>
                                            {{ $openTicket->progress_note }}
                                        </div>
                                    @endif
                                </td>

                                <td class="py-4 px-4 align-top whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($progress['user']->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-bold text-slate-900 block truncate">{{ $progress['user']->name }}</span>
                                            <button type="button" @click="toggleStaffFilter('{{ $progress['user']->id }}')"
                                                    class="text-[10px] font-semibold text-indigo-600 hover:underline cursor-pointer">
                                                Filter Staf Ini
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4 align-top whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 h-2 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-emerald-500" style="width: {{ $openTicket->progressPercent() ?? 0 }}%"></div>
                                        </div>
                                        <span class="font-bold text-emerald-700">{{ $openTicket->progressPercent() === null ? '0%' : $openTicket->progressPercent() . '%' }}</span>
                                    </div>
                                    @if(($openTicket->problems_count ?? 0) > 0)
                                        <span class="text-[10px] text-slate-500 block mt-1 font-medium">
                                            {{ $openTicket->done_problems_count ?? 0 }}/{{ $openTicket->problems_count }} checklist selesai
                                        </span>
                                    @endif
                                </td>

                                <td class="py-4 px-4 align-top whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 text-xs font-medium border border-slate-200">
                                        <span class="w-2 h-2 rounded-full {{ $openTicket->priority === 'urgent' ? 'bg-rose-600' : ($openTicket->priority === 'high' ? 'bg-orange-500' : ($openTicket->priority === 'low' ? 'bg-emerald-500' : 'bg-amber-400')) }}"></span>
                                        <span>{{ $openTicket->priorityLabel() }}</span>
                                    </span>
                                </td>

                                <td class="py-4 px-4 align-top whitespace-nowrap">
                                    <span class="inline-block text-[11px] font-semibold px-2.5 py-1 rounded-lg border {{ $openTicket->work_status === 'blocked' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-sky-50 text-sky-700 border-sky-200' }}">
                                        {{ $openTicket->workStatusLabel() ?? $openTicket->statusLabel() }}
                                    </span>
                                </td>

                                <td class="py-4 px-4 align-top text-right whitespace-nowrap">
                                    <a href="{{ route('tickets.show', $openTicket) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-2xs cursor-pointer">
                                        <span>Detail</span>
                                        <span>&rarr;</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach

                    @if(!$hasRows)
                        <tr>
                            <td colspan="7" class="py-12 px-4 text-center">
                                <div class="max-w-xs mx-auto text-center">
                                    <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 text-sm font-bold">✓</span>
                                    <p class="text-xs font-semibold text-slate-700">Tidak ada tiket yang sedang dikerjakan saat ini</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Semua pekerjaan telah selesai atau belum ada tiket yang dibagikan ke staf.</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reusable Modal: Detail Tiket per Staff -->
    @include('tickets.dashboards.shared.staff-detail-modal')

</div>
@endsection
