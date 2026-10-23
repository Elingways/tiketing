<!-- Stat Summary Cards Row untuk Staff (Personal Workload) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Tiket Aktif Saya</span>
            <span class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </span>
        </div>
        <div class="mt-3">
            <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ $myActiveCount }}</div>
            <div class="flex items-center gap-1.5 mt-1 text-[11px] text-indigo-700 font-medium">
                <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100 font-semibold">Ditugaskan</span>
                <span class="text-slate-400">ke Anda</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Sedang Dikerjakan</span>
            <span class="w-8 h-8 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </span>
        </div>
        <div class="mt-3">
            <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ $myAssignedTickets->where('status', 'in_progress')->count() }}</div>
            <div class="flex items-center gap-1.5 mt-1 text-[11px] text-sky-700 font-medium">
                <span class="px-1.5 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-100 font-semibold">In Progress</span>
                <span class="text-slate-400">dalam proses kerja</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Rata-rata Progres</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
        </div>
        <div class="mt-3">
            <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ round($myAvgPercent ?? 0) }}%</div>
            <div class="flex items-center gap-1.5 mt-1 text-[11px] text-emerald-700 font-medium">
                <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold">Penyelesaian</span>
                <span class="text-slate-400">rata-rata tiket</span>
            </div>
        </div>
    </div>

    <!-- Card Profil Staf & Tombol DETAIL untuk melihat seluruh tiket saya -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Profil &amp; Beban Kerja</span>
            <button type="button"
                    @click="staffDetail = {{ (int) $user->id }}"
                    class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-bold shadow-2xs transition flex items-center gap-1">
                <span>Detail</span>
                <span>&rarr;</span>
            </button>
        </div>
        <div class="mt-2 flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center text-xs font-bold shrink-0">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <span class="text-xs font-bold text-slate-900 block truncate">{{ $user->name }}</span>
                <span class="text-[10px] text-slate-400 block truncate">{{ $user->keahlian ?? 'Staff Pelaksana' }}</span>
            </div>
        </div>
        <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500">
            <strong class="text-slate-700">{{ $myActiveCount }} tiket</strong> sedang dikerjakan
        </div>
    </div>
</div>

<!-- Section: Tabel Tugas & Pekerjaan Saya (Clean & Responsif) -->
<div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 bg-white flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Daftar Tiket Ditugaskan Kepada Saya</h2>
            <p class="text-xs text-slate-500 mt-0.5">Selesaikan poin-poin pekerjaan, input kendala pengerjaan, dan laporkan progres.</p>
        </div>

        <form action="{{ route('tickets.inbox') }}" method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="hidden" name="scope" value="{{ $scope }}">
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Cari No. Tiket, Judul..."
                   class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-slate-900">
            <select name="status" onchange="this.form.submit()"
                    class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 font-medium focus:outline-none">
                <option value="">Status: Semua</option>
                <option value="open" {{ ($statusFilter ?? '') === 'open' ? 'selected' : '' }}>Open</option>
                <option value="in_progress" {{ ($statusFilter ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="resolved" {{ ($statusFilter ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
            </select>
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition">Filter</button>
            @if($search || $categoryId || $statusFilter)
                <a href="{{ route('tickets.inbox', ['scope' => $scope]) }}" class="px-2.5 py-1.5 rounded-xl bg-rose-50 text-rose-700 text-xs font-semibold hover:bg-rose-100 transition">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200/80 bg-slate-50/60 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    <th class="py-3.5 px-4 min-w-[150px]">Ticket ID</th>
                    <th class="py-3.5 px-4 min-w-[240px]">Subject</th>
                    <th class="py-3.5 px-4 min-w-[150px]">Customer</th>
                    <th class="py-3.5 px-4 min-w-[140px]">Channel</th>
                    <th class="py-3.5 px-4 min-w-[120px]">Priority</th>
                    <th class="py-3.5 px-4 min-w-[140px]">Progres Kerja</th>
                    <th class="py-3.5 px-4 min-w-[100px]">Status</th>
                    <th class="py-3.5 px-4 min-w-[110px] text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                @forelse($tickets as $ticket)
                    @php
                        $initial = strtoupper(substr($ticket->creator?->name ?? 'U', 0, 1));
                        $prog = $ticket->progressPercent();
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- 1. Ticket ID & Dates (TIDAK TERPOTONG!) -->
                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <a href="{{ route('tickets.show', $ticket) }}" class="font-mono font-bold text-indigo-600 hover:text-indigo-800 transition">
                                {{ $ticket->ticket_number }}
                            </a>
                            <div class="mt-1">
                                @include('tickets.partials.dates', ['ticket' => $ticket, 'format' => 'd M Y'])
                            </div>
                        </td>

                        <!-- 2. Subject -->
                        <td class="py-4 px-4 align-top">
                            <a href="{{ route('tickets.show', $ticket) }}" class="font-semibold text-slate-900 hover:text-indigo-600 block leading-snug">
                                {{ $ticket->title }}
                            </a>
                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                @if($ticket->attachment_path)
                                    <button type="button"
                                            @click="openDocPreview('{{ route('tickets.preview', $ticket) }}', '{{ route('tickets.attachment', $ticket) }}', '{{ addslashes($ticket->attachment_name ?? 'Dokumen Lampiran') }}')"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50/70 hover:bg-indigo-100 text-indigo-700 text-[10px] font-medium border border-indigo-100 transition">
                                        {{ $ticket->isImageAttachment() ? '🖼️ Gambar Bukti' : '📎 Preview Dokumen' }}
                                    </button>
                                @endif
                                @if(($ticket->problems_count ?? 0) > 0)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">
                                        ✓ {{ $ticket->done_problems_count ?? 0 }}/{{ $ticket->problems_count }} poin selesai
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- 3. Customer -->
                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ $initial }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-semibold text-slate-900 block truncate">{{ $ticket->creator?->name ?? 'Pemohon' }}</span>
                                    <span class="text-[11px] text-slate-400 block truncate">{{ $ticket->creatorRole?->name }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- 4. Channel -->
                        <td class="py-4 px-4 align-top">
                            <span class="inline-block text-xs text-slate-700 font-medium">
                                {{ $ticket->category?->name ?? 'Umum' }}
                            </span>
                        </td>

                        <!-- 5. Priority -->
                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 text-xs font-medium border border-slate-200">
                                <span class="w-2 h-2 rounded-full {{ $ticket->priority === 'urgent' ? 'bg-rose-600' : ($ticket->priority === 'high' ? 'bg-orange-500' : ($ticket->priority === 'low' ? 'bg-emerald-500' : 'bg-amber-400')) }}"></span>
                                <span>{{ $ticket->priorityLabel() }}</span>
                            </div>
                        </td>

                        <!-- 6. Progres Kerja -->
                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full bg-emerald-500" style="width: {{ $prog ?? 0 }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-emerald-700">{{ $prog === null ? '0%' : $prog . '%' }}</span>
                            </div>
                            @if($ticket->work_status === 'blocked')
                                <span class="text-[10px] font-bold text-rose-700 block mt-0.5">⚠️ Terkendala / Blocked</span>
                            @endif
                        </td>

                        <!-- 7. Status -->
                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <span class="inline-block text-[11px] font-medium px-2.5 py-1 rounded-md border {{ $statusColors[$ticket->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $ticket->statusLabel() }}
                            </span>
                        </td>

                        <!-- 8. Action (Update Progres) -->
                        <td class="py-4 px-4 align-top text-right whitespace-nowrap">
                            <a href="{{ route('tickets.show', $ticket) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs transition shadow-2xs">
                                <span>Kerjakan</span>
                                <span>&rarr;</span>
                            </a>
                        </td>
                    </tr>
                    @if($ticket->progress_note)
                        <tr class="bg-amber-50/40">
                            <td colspan="8" class="px-4 py-2 text-[11px] text-slate-700 border-t border-amber-100/60">
                                <span class="font-bold text-amber-900">Catatan Pengerjaan:</span> {{ $ticket->progress_note }}
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="py-12 px-4 text-center">
                            <div class="max-w-xs mx-auto text-center">
                                <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2">✓</span>
                                <p class="text-xs font-semibold text-slate-700">Tidak ada tiket yang sedang ditugaskan kepada Anda</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Semua pekerjaan telah selesai atau belum ada tiket baru.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
