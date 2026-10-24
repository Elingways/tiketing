@extends('layouts.app')

@section('content')
<div x-data="{
    staffDetail: {{ $selectedStaffId ? (int) $selectedStaffId : 'null' }},
    selectedStaffFilter: '{{ $selectedStaffId ? (string) $selectedStaffId : 'all' }}',
    searchQuery: '',
    statusFilter: 'all',
    toggleStaffFilter(id) {
        this.selectedStaffFilter = (this.selectedStaffFilter === String(id)) ? 'all' : String(id);
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

    <!-- Page Header Clean & Professional -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100 shadow-2xs">
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
                    Monitoring Kinerja &amp; Beban Kerja Tim IT — Pantau alokasi tiket, persentase progres, dan status tiket secara real-time.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('tickets.inbox') }}"
               class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-1.5 cursor-pointer">
                <span>&larr;</span>
                <span>Antrean Tiket</span>
            </a>
        </div>
    </div>

    <!-- Summary Stat Cards Row (4 KPI Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- 1. Total Staf -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0 shadow-2xs">
                    @include('partials.icon', ['name' => 'users', 'class' => 'w-5 h-5'])
                </div>
                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    {{ $activeStaffCount }} Aktif
                </span>
            </div>
            <div class="mt-4">
                <p class="text-xs font-semibold text-slate-500">Total Tim Staf</p>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">{{ $totalStaffCount }}</div>
                <p class="text-[11px] text-slate-400 mt-1">Teknisi terdaftar dalam unit</p>
            </div>
        </div>

        <!-- 2. Tiket Sedang Dikerjakan -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600 shrink-0 shadow-2xs">
                    @include('partials.icon', ['name' => 'ticket', 'class' => 'w-5 h-5'])
                </div>
                @if($totalTicketsHandled > 0)
                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-100">
                        Diproses
                    </span>
                @else
                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200/60">
                        Kosong
                    </span>
                @endif
            </div>
            <div class="mt-4">
                <p class="text-xs font-semibold text-slate-500">Tiket Sedang Dikerjakan</p>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">{{ $totalTicketsHandled }}</div>
                <p class="text-[11px] text-slate-400 mt-1">
                    {{ $totalTicketsHandled > 0 ? 'Tiket aktif ditangani tim' : 'Semua antrean tertangani' }}
                </p>
            </div>
        </div>

        <!-- 3. Rata-rata Progres Tim -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0 shadow-2xs">
                    @include('partials.icon', ['name' => 'check-circle', 'class' => 'w-5 h-5'])
                </div>
                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    Checklist
                </span>
            </div>
            <div class="mt-4">
                <p class="text-xs font-semibold text-slate-500">Rata-rata Progres Tim</p>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">{{ round($avgProgress ?? 0) }}%</div>
                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-2">
                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-300" style="width: {{ round($avgProgress ?? 0) }}%"></div>
                </div>
            </div>
        </div>

        <!-- 4. Kendala / Blocked -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl {{ $blockedCount > 0 ? 'bg-rose-50 border border-rose-100 text-rose-600' : 'bg-slate-50 border border-slate-100 text-slate-400' }} flex items-center justify-center shrink-0 shadow-2xs">
                    @include('partials.icon', ['name' => 'exclamation-circle', 'class' => 'w-5 h-5'])
                </div>
                @if($blockedCount > 0)
                    <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 animate-pulse">
                        Perlu Tindakan
                    </span>
                @else
                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                        Lancar
                    </span>
                @endif
            </div>
            <div class="mt-4">
                <p class="text-xs font-semibold text-slate-500">Terkendala / Blocked</p>
                <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">{{ $blockedCount }}</div>
                <p class="text-[11px] mt-1 {{ $blockedCount > 0 ? 'text-rose-600 font-medium' : 'text-slate-400' }}">
                    {{ $blockedCount > 0 ? 'Memerlukan intervensi bantuan' : 'Tidak ada tiket yang terhambat' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Section: Kartu Beban Kerja Tim Staf -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm mb-6 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Tim Staf &amp; Beban Kerja</h2>
                <p class="text-[11px] text-slate-500 mt-0.5">Pilih kartu teknisi untuk menyaring rincian tiket pekerjaan di bawah secara interaktif.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-white border border-slate-200 text-slate-600 shadow-2xs">
                {{ $staffProgress->count() }} Teknisi Terdaftar
            </span>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($staffProgress as $progress)
                @php
                    $isBlocked = ($progress['blocked'] ?? 0) > 0;
                    $percent = $progress['percent'] ?? 0;
                    $ticketCount = $progress['tickets'] ?? 0;
                @endphp
                <div class="rounded-2xl border transition cursor-pointer flex flex-col justify-between overflow-hidden group shadow-2xs hover:shadow-md"
                     :class="selectedStaffFilter === '{{ $progress['user']->id }}'
                         ? 'border-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/20'
                         : 'border-slate-200/80 bg-white hover:border-slate-300'"
                     @click="toggleStaffFilter('{{ $progress['user']->id }}')">
                    
                    <!-- Decorative Top Header Banner -->
                    <div class="h-12 bg-gradient-to-r from-indigo-500/10 via-violet-500/10 to-indigo-500/5 px-4 pt-2.5 flex items-start justify-between border-b border-slate-100/80">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/90 text-slate-700 border border-slate-200/70 shadow-2xs">
                            {{ $progress['user']->role?->name ?? 'Staf Unit' }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 {{ $isBlocked ? 'bg-rose-50 text-rose-700 border border-rose-200 animate-pulse' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $isBlocked ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                            <span>{{ $isBlocked ? 'Blocked' : 'Normal' }}</span>
                        </span>
                    </div>

                    <div class="p-4 pt-0">
                        <!-- Profile Identity Section -->
                        <div class="flex items-start gap-3 -mt-5">
                            <!-- Avatar / Photo -->
                            <div class="relative shrink-0">
                                @if($progress['user']->avatar_url)
                                    <img src="{{ $progress['user']->avatar_url }}" alt="{{ $progress['user']->name }}"
                                         class="w-12 h-12 rounded-2xl object-cover ring-2 ring-white shadow-md bg-white">
                                @else
                                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-600 text-white flex items-center justify-center text-sm font-bold shadow-md shadow-indigo-500/20 ring-2 ring-white">
                                        {{ strtoupper(substr($progress['user']->name, 0, 2)) }}
                                    </div>
                                @endif
                                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-white {{ $ticketCount > 0 ? ($isBlocked ? 'bg-rose-500' : 'bg-emerald-500') : 'bg-slate-300' }}"
                                      title="{{ $ticketCount > 0 ? 'Sedang menangani tiket' : 'Tersedia' }}"></span>
                            </div>

                            <!-- Name & Email -->
                            <div class="min-w-0 pt-5">
                                <h3 class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 transition truncate">{{ $progress['user']->name }}</h3>
                                <p class="text-[11px] text-slate-400 truncate flex items-center gap-1 mt-0.5">
                                    <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                    <span class="truncate">{{ $progress['user']->email }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Badge Keahlian / Spesialisasi -->
                        @if($progress['user']->keahlian)
                            <div class="mt-2.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-indigo-50/80 text-indigo-700 border border-indigo-100">
                                    <svg class="w-3 h-3 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877m-3.703 3.797l-2.073-2.073m0 0l-1.06-1.06a8.25 8.25 0 00-11.668 0 8.25 8.25 0 000 11.668 8.25 8.25 0 0011.668 0l1.06-1.06m-2.073-2.073l2.073 2.073" />
                                    </svg>
                                    <span class="truncate">{{ $progress['user']->keahlian }}</span>
                                </span>
                            </div>
                        @endif

                        <!-- Mini Stats Bar -->
                        <div class="grid grid-cols-3 gap-2 p-2 mt-3 rounded-xl bg-slate-50 border border-slate-100 text-center">
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Tiket Aktif</span>
                                <span class="text-xs font-bold text-slate-800">{{ $ticketCount }}</span>
                            </div>
                            <div class="border-x border-slate-200/60">
                                <span class="text-[10px] text-slate-400 block font-medium">Progres</span>
                                <span class="text-xs font-bold text-emerald-600">{{ $progress['percent'] === null ? '0%' : $progress['percent'] . '%' }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block font-medium">Kendala</span>
                                <span class="text-xs font-bold {{ $isBlocked ? 'text-rose-600' : 'text-slate-500' }}">{{ $progress['blocked'] ?? 0 }}</span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-3">
                            <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full bg-emerald-500 transition-all duration-300" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        <!-- Content Preview: Tiket yang sedang ditangani -->
                        <div class="mt-3">
                            @if($ticketCount > 0)
                                <div class="space-y-1.5">
                                    @foreach($progress['open_tickets']->take(2) as $ot)
                                        <div class="text-[11px] p-2 rounded-xl bg-slate-50/80 border border-slate-100 flex items-center justify-between gap-2">
                                            <div class="truncate min-w-0">
                                                <span class="font-mono font-bold text-indigo-600 mr-1">{{ $ot->ticket_number }}</span>
                                                <span class="text-slate-700 truncate">{{ $ot->title }}</span>
                                            </div>
                                            <span class="font-bold text-slate-600 shrink-0">{{ $ot->progressPercent() ?? 0 }}%</span>
                                        </div>
                                    @endforeach
                                    @if($ticketCount > 2)
                                        <p class="text-[10px] text-slate-400 text-center font-medium">+{{ $ticketCount - 2 }} tiket lainnya</p>
                                    @endif
                                </div>
                            @else
                                <div class="py-2.5 px-3 rounded-xl bg-slate-50 border border-slate-100/80 flex items-center justify-between text-xs">
                                    <span class="text-slate-500 text-[11px] flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Kapasitas tersedia
                                    </span>
                                    <span class="text-[11px] font-semibold text-slate-400">Siap ditugaskan</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Footer Tombol Detail & Filter -->
                    <div class="p-3 px-4 border-t border-slate-100 bg-slate-50/40 flex items-center justify-between">
                        <span class="text-[11px] font-medium"
                              :class="selectedStaffFilter === '{{ $progress['user']->id }}' ? 'text-indigo-600 font-bold' : 'text-slate-400'">
                            <span x-text="selectedStaffFilter === '{{ $progress['user']->id }}' ? '● Sedang difilter' : 'Klik untuk filter'"></span>
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

    <!-- Section: Rincian Seluruh Pekerjaan yang Sedang Berjalan (Tabel Dinamis Terpadu) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Integrated Toolbar Header -->
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Daftar Tiket yang Sedang Dikerjakan Seluruh Staf</h2>
                <p class="text-xs text-slate-500 mt-0.5">Tinjau checklist poin pekerjaan, penilaian prioritas, dan catatan kendala teknisi.</p>
            </div>

            <!-- Integrated Search & Filter Controls -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Search Box -->
                <div class="relative">
                    <input type="text" x-model="searchQuery"
                           placeholder="Cari nomor, judul, staf..."
                           class="w-48 sm:w-60 pl-8 pr-7 py-1.5 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
                    <div class="absolute left-2.5 top-2 text-slate-400 pointer-events-none">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <button type="button" x-show="searchQuery" @click="searchQuery = ''"
                            class="absolute right-2 top-1.5 text-slate-400 hover:text-slate-600 text-xs cursor-pointer">
                        ✕
                    </button>
                </div>

                <!-- Status Filter Chips -->
                <div class="inline-flex p-0.5 rounded-xl bg-slate-200/70 border border-slate-200">
                    <button type="button" @click="statusFilter = 'all'"
                            :class="statusFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                        Semua
                    </button>
                    <button type="button" @click="statusFilter = 'in_progress'"
                            :class="statusFilter === 'in_progress' ? 'bg-white text-slate-900 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                        Normal
                    </button>
                    <button type="button" @click="statusFilter = 'blocked'"
                            :class="statusFilter === 'blocked' ? 'bg-white text-rose-700 shadow-2xs font-semibold' : 'text-slate-600 hover:text-slate-900'"
                            class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer flex items-center gap-1">
                        <span>Blocked</span>
                        @if($blockedCount > 0)
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        @endif
                    </button>
                </div>

                <!-- Reset Filter Button -->
                <button type="button" x-show="selectedStaffFilter !== 'all' || searchQuery !== '' || statusFilter !== 'all'"
                        @click="resetFilters()"
                        class="px-2.5 py-1 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 text-xs font-semibold hover:bg-rose-100 transition cursor-pointer flex items-center gap-1">
                    <span>✕</span>
                    <span>Reset</span>
                </button>
            </div>
        </div>

        <!-- Filter Active Banner (If filtered) -->
        <div x-show="selectedStaffFilter !== 'all'" x-cloak
             class="px-5 py-2.5 bg-indigo-50/80 border-b border-indigo-100 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 text-indigo-900 font-medium">
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                <span>Menampilkan tiket untuk staf yang dipilih.</span>
            </div>
            <button type="button" @click="selectedStaffFilter = 'all'"
                    class="font-bold text-indigo-700 hover:text-indigo-900 underline cursor-pointer">
                Tampilkan Semua Staf
            </button>
        </div>

        @if($totalTicketsHandled > 0)
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
                        @foreach($staffProgress as $progress)
                            @foreach($progress['open_tickets'] as $openTicket)
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
                                            @if($progress['user']->avatar_url)
                                                <img src="{{ $progress['user']->avatar_url }}" alt="{{ $progress['user']->name }}"
                                                     class="w-8 h-8 rounded-xl object-cover ring-1 ring-slate-200 shrink-0">
                                            @else
                                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200/60 flex items-center justify-center text-xs font-bold shrink-0">
                                                    {{ strtoupper(substr($progress['user']->name, 0, 2)) }}
                                                </div>
                                            @endif
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
                    </tbody>
                </table>
            </div>
        @else
            <!-- Friendly Empty State -->
            <div class="py-12 px-4 text-center">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3 border border-indigo-100 shadow-2xs">
                    @include('partials.icon', ['name' => 'ticket', 'class' => 'w-6 h-6'])
                </div>
                <h3 class="text-sm font-bold text-slate-900">Belum Ada Tiket yang Sedang Dikerjakan</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Saat ini seluruh tiket sudah terselesaikan atau belum ada tiket baru yang didelegasikan ke staf teknisi.
                </p>
                <div class="mt-4">
                    <a href="{{ route('tickets.inbox') }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-2xs transition">
                        <span>Lihat Antrean Tiket Masuk</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Reusable Modal: Detail Tiket per Staff -->
    @include('tickets.dashboards.shared.staff-detail-modal')

</div>
@endsection
