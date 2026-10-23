@extends('layouts.app')

@section('content')
<div x-data="{
    staffDetail: {{ $selectedStaffId ? (int) $selectedStaffId : 'null' }},
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

    <!-- Page Header (Dedicated Menu Staff Progres) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Dashboard</span>
                <span>/</span>
                <a href="{{ route('tickets.inbox') }}" class="hover:text-slate-700">Tiketing</a>
                <span>/</span>
                <span class="text-slate-900 font-semibold">Staff Progres</span>
                @if($user->role)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                        {{ $user->role->name }}
                    </span>
                @endif
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Staff Progres — Monitoring Kinerja &amp; Beban Kerja Tim IT</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Pantau nama staff, progres pengerjaan, dan jumlah tiket yang sedang dikerjakan secara terfokus.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start md:self-auto">
            <a href="{{ route('tickets.inbox') }}"
               class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-1.5">
                <span>&larr;</span>
                <span>Kembali ke Antrean Tiket</span>
            </a>
        </div>
    </div>

    <!-- Summary Stat Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- 1. Total Staf -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Total Tim Staf</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ $totalStaffCount }} <span class="text-xs font-normal text-slate-400">orang</span></div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] text-emerald-700 font-medium">
                    <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold">{{ $activeStaffCount }} aktif</span>
                    <span class="text-slate-400">memegang pekerjaan</span>
                </div>
            </div>
        </div>

        <!-- 2. Total Tiket Dikerjakan -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Tiket Sedang Dikerjakan</span>
                <span class="w-8 h-8 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
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
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Rata-rata Progres Tim</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
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
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500">Terkendala / Blocked</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ $blockedCount }} <span class="text-xs font-normal text-slate-400">tiket</span></div>
                <div class="flex items-center gap-1.5 mt-1 text-[11px] font-medium {{ $blockedCount > 0 ? 'text-rose-700' : 'text-slate-400' }}">
                    @if($blockedCount > 0)
                        <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 font-semibold">Butuh Bantuan</span>
                        <span class="text-slate-400">atasan/vendor</span>
                    @else
                        <span>Semua pekerjaan lancar</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Kartu Beban Kerja Setiap Staff (Grid View) -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Staff Progres</h2>
                <p class="text-[11px] text-slate-500 mt-0.5">Daftar staf teknisi beserta status beban kerja saat ini. Klik <strong>Detail</strong> untuk melihat rincian tiket per staf.</p>
            </div>
            <span class="px-3 py-1 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-semibold shadow-2xs">
                {{ $staffProgress->count() }} Teknisi Terdaftar
            </span>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($staffProgress as $progress)
                @php
                    $isBlocked = ($progress['blocked'] ?? 0) > 0;
                    $percent = $progress['percent'] ?? 0;
                @endphp
                <div class="bg-white rounded-2xl border p-4.5 transition hover:shadow-md flex flex-col justify-between {{ $isBlocked ? 'border-rose-200 bg-rose-50/10' : 'border-slate-200/80 hover:border-indigo-200' }}">
                    <div>
                        <!-- Header Card Staf -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-sm font-bold shrink-0">
                                    {{ strtoupper(substr($progress['user']->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-slate-900 truncate">{{ $progress['user']->name }}</h3>
                                    <span class="text-[11px] text-slate-500 block truncate">{{ $progress['user']->keahlian ?? ($progress['user']->jabatan ?? 'Teknisi IT') }}</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $isBlocked ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' }}">
                                {{ $isBlocked ? 'Blocked' : 'Normal' }}
                            </span>
                        </div>

                        <!-- Progress Bar & Tiket Count -->
                        <div class="mt-4 pt-3 border-t border-slate-100">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-medium text-slate-600">{{ $progress['tickets'] }} tiket sedang dikerjakan</span>
                                <span class="font-bold text-emerald-700">{{ $progress['percent'] === null ? '—' : $progress['percent'] . '%' }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full bg-emerald-500 transition-all duration-300" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        <!-- Quick Tickets Preview -->
                        <div class="mt-3 space-y-1.5">
                            @foreach($progress['open_tickets']->take(2) as $ot)
                                <div class="text-[11px] p-2 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between gap-2">
                                    <div class="truncate min-w-0">
                                        <span class="font-mono font-bold text-indigo-600 mr-1">{{ $ot->ticket_number }}</span>
                                        <span class="text-slate-700 truncate">{{ $ot->title }}</span>
                                    </div>
                                    <span class="font-bold text-slate-600 shrink-0">{{ $ot->progressPercent() ?? 0 }}%</span>
                                </div>
                            @endforeach
                            @if($progress['tickets'] > 2)
                                <p class="text-[10px] text-slate-400 text-center">+{{ $progress['tickets'] - 2 }} tiket lainnya</p>
                            @endif
                        </div>
                    </div>

                    <!-- Footer Tombol Detail -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">
                            @if($isBlocked)
                                <span class="font-bold text-rose-700">{{ $progress['blocked'] }} tiket terkendala</span>
                            @else
                                Siap ditugaskan
                            @endif
                        </span>
                        <button type="button"
                                @click="staffDetail = {{ (int) $progress['user']->id }}"
                                class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition flex items-center gap-1.5 shadow-2xs">
                            <span>Detail</span>
                            <span>&rarr;</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-xs text-slate-400">
                    Belum ada staff terdaftar dalam unit ini.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Section: Rincian Seluruh Pekerjaan yang Sedang Berjalan -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-white flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Daftar Tiket yang Sedang Dikerjakan Seluruh Staf</h2>
                <p class="text-xs text-slate-500 mt-0.5">Tinjau perkembangan checklist pekerjaan, penilaian prioritas, dan catatan kendala.</p>
            </div>
            <span class="text-xs font-semibold text-slate-500">{{ $totalTicketsHandled }} Tiket Berjalan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/60 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4 min-w-[150px]">Ticket ID</th>
                        <th class="py-3.5 px-4 min-w-[240px]">Subject</th>
                        <th class="py-3.5 px-4 min-w-[160px]">Staff Penanggung Jawab</th>
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
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-4 px-4 align-top whitespace-nowrap">
                                    <a href="{{ route('tickets.show', $openTicket) }}" class="font-mono font-bold text-indigo-600 hover:text-indigo-800 transition">
                                        {{ $openTicket->ticket_number }}
                                    </a>
                                    <div class="mt-1">
                                        @include('tickets.partials.dates', ['ticket' => $openTicket, 'format' => 'd M Y'])
                                    </div>
                                </td>

                                <td class="py-4 px-4 align-top">
                                    <a href="{{ route('tickets.show', $openTicket) }}" class="font-semibold text-slate-900 hover:text-indigo-600 block leading-snug">
                                        {{ $openTicket->title }}
                                    </a>
                                    <span class="text-[11px] text-slate-400 block mt-0.5">{{ $openTicket->category?->name ?? 'Umum' }} · Pemohon: {{ $openTicket->creator?->name }}</span>
                                    @if($openTicket->progress_note)
                                        <p class="mt-1.5 text-[11px] text-slate-600 bg-amber-50/70 p-2 rounded-lg border border-amber-200/60 leading-relaxed">
                                            <span class="font-bold text-amber-900">Catatan Staf:</span> {{ $openTicket->progress_note }}
                                        </p>
                                    @endif
                                </td>

                                <td class="py-4 px-4 align-top whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                                            {{ strtoupper(substr($progress['user']->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-bold text-slate-900 block truncate">{{ $progress['user']->name }}</span>
                                            <button type="button" @click="staffDetail = {{ (int) $progress['user']->id }}" class="text-[10px] font-semibold text-indigo-600 hover:underline">
                                                Lihat Tiket Staf
                                            </button>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4 align-top whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-emerald-500" style="width: {{ $openTicket->progressPercent() ?? 0 }}%"></div>
                                        </div>
                                        <span class="font-bold text-emerald-700">{{ $openTicket->progressPercent() === null ? '0%' : $openTicket->progressPercent() . '%' }}</span>
                                    </div>
                                    @if(($openTicket->problems_count ?? 0) > 0)
                                        <span class="text-[10px] text-slate-500 block mt-0.5">
                                            {{ $openTicket->done_problems_count ?? 0 }}/{{ $openTicket->problems_count }} poin checklist selesai
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
                                    <span class="inline-block text-[11px] font-medium px-2.5 py-1 rounded-md border {{ $openTicket->work_status === 'blocked' ? 'bg-rose-50 text-rose-700 border-rose-200 font-bold' : 'bg-sky-50 text-sky-700 border-sky-200' }}">
                                        {{ $openTicket->workStatusLabel() ?? $openTicket->statusLabel() }}
                                    </span>
                                </td>

                                <td class="py-4 px-4 align-top text-right whitespace-nowrap">
                                    <a href="{{ route('tickets.show', $openTicket) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs transition shadow-2xs">
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
                                    <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">✓</span>
                                    <p class="text-xs font-semibold text-slate-700">Tidak ada tiket yang sedang dikerjakan saat ini</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">Semua pekerjaan telah selesai atau belum ada tiket yang dibagikan.</p>
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
