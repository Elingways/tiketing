<!-- Stat Summary Cards Row (Helpy Style) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Open Tickets</span>
            <span class="w-8 h-8 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>
            </span>
        </div>
        <div class="mt-3">
            <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ $openTicketsCount }}</div>
            <div class="flex items-center gap-1.5 mt-1 text-[11px] text-emerald-700 font-medium">
                <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold">↑ Aktif</span>
                <span class="text-slate-400">tiket dalam antrean</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Pending Response</span>
            <span class="w-8 h-8 rounded-xl bg-amber-50/60 border border-amber-100 flex items-center justify-center text-amber-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
        </div>
        <div class="mt-3">
            <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ $pendingCount }}</div>
            <div class="flex items-center gap-1.5 mt-1 text-[11px] text-amber-700 font-medium">
                <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 font-semibold">Needs attention</span>
                <span class="text-slate-400">belum ditugaskan</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">In Progress</span>
            <span class="w-8 h-8 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </span>
        </div>
        <div class="mt-3">
            <div class="text-2xl font-bold text-slate-900 tracking-tight">{{ $inProgressCount }}</div>
            <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-500">
                <span class="px-1.5 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-100 font-semibold">Teknisi</span>
                <span class="text-slate-400">sedang pengerjaan</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-xs transition flex flex-col justify-between">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500">Staff Progres</span>
            @if($staffProgress->isNotEmpty())
                <button type="button" @click="staffDetail = {{ (int) $staffProgress->first()['user']->id }}"
                        class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">
                    Lihat Semua &rarr;
                </button>
            @endif
        </div>
        <div class="mt-2 space-y-1 overflow-hidden">
            @forelse($staffProgress->take(2) as $progress)
                <div class="flex items-center justify-between text-xs py-0.5">
                    <span class="font-medium text-slate-900 truncate max-w-[120px]">{{ $progress['user']->name }}</span>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[10px] text-slate-400">{{ $progress['tickets'] }} tiket</span>
                        <button type="button" @click="staffDetail = {{ (int) $progress['user']->id }}"
                                class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-[10px] font-semibold text-slate-700">
                            Detail
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-[11px] text-slate-400 py-1">Belum ada staff terdaftar.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Section: Kapasitas & Beban Kerja Staff IT (Untuk kemudahan Admin IT sebelum membagi tiket) -->
@if($staffProgress->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-4 mb-6">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Kapasitas &amp; Beban Kerja Tim Staff</h3>
                <p class="text-[11px] text-slate-500 mt-0.5">Klik tombol <strong>Detail</strong> pada salah satu staff untuk melihat tiket-tiket yang sedang dipegangnya.</p>
            </div>
            <span class="text-xs text-slate-400 font-medium">{{ $staffProgress->count() }} Teknisi Tersedia</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($staffProgress as $progress)
                <div class="p-3 rounded-xl border border-slate-200/80 bg-slate-50/60 hover:bg-white hover:border-indigo-200 hover:shadow-xs transition flex flex-col justify-between">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-slate-900 block truncate">{{ $progress['user']->name }}</span>
                            <span class="text-[10px] text-slate-500 block truncate">{{ $progress['user']->keahlian ?? 'Teknisi IT' }}</span>
                        </div>
                        <button type="button"
                                @click="staffDetail = {{ (int) $progress['user']->id }}"
                                class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 hover:bg-indigo-50 hover:text-indigo-700 text-[10px] font-bold text-slate-700 shadow-2xs transition">
                            Detail
                        </button>
                    </div>

                    <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px]">
                        <span class="text-slate-600 font-medium">{{ $progress['tickets'] }} tiket aktif</span>
                        <span class="font-bold {{ ($progress['blocked'] ?? 0) > 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                            {{ $progress['percent'] === null ? '0%' : $progress['percent'] . '%' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Section: Main Clean Data Table (Helpy Style) -->
<div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
    <!-- Toolbar -->
    <div class="p-4 border-b border-slate-100 bg-white flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <form action="{{ route('tickets.inbox') }}" method="GET" class="flex-1 flex flex-col md:flex-row items-stretch md:items-center gap-3">
            <input type="hidden" name="scope" value="{{ $scope }}">

            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari No. Tiket, Judul, atau Pemohon..."
                       class="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-50/70 border border-slate-200 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-slate-900 transition">
            </div>

            <!-- Status Filter -->
            <select name="status" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-xl bg-slate-50/70 border border-slate-200 text-xs text-slate-700 font-medium hover:bg-slate-100 focus:outline-none focus:border-slate-900 transition">
                <option value="">Status: Semua</option>
                <option value="open" {{ ($statusFilter ?? '') === 'open' ? 'selected' : '' }}>Open</option>
                <option value="in_progress" {{ ($statusFilter ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="waiting_next_approval" {{ ($statusFilter ?? '') === 'waiting_next_approval' ? 'selected' : '' }}>Waiting Approval</option>
                <option value="resolved" {{ ($statusFilter ?? '') === 'resolved' ? 'selected' : '' }}>Resolved</option>
            </select>

            <!-- Priority Filter -->
            <select name="priority" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-xl bg-slate-50/70 border border-slate-200 text-xs text-slate-700 font-medium hover:bg-slate-100 focus:outline-none focus:border-slate-900 transition">
                <option value="">Priority: Semua</option>
                <option value="urgent" {{ ($priorityFilter ?? '') === 'urgent' ? 'selected' : '' }}>🔴 Urgent (Mendesak)</option>
                <option value="high" {{ ($priorityFilter ?? '') === 'high' ? 'selected' : '' }}>🟠 High (Tinggi)</option>
                <option value="medium" {{ ($priorityFilter ?? '') === 'medium' ? 'selected' : '' }}>🟡 Medium (Normal)</option>
                <option value="low" {{ ($priorityFilter ?? '') === 'low' ? 'selected' : '' }}>🟢 Low (Rendah)</option>
            </select>

            <!-- Category Filter -->
            <select name="category_id" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-xl bg-slate-50/70 border border-slate-200 text-xs text-slate-700 font-medium hover:bg-slate-100 focus:outline-none focus:border-slate-900 transition">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (string) $categoryId === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <!-- Agent Filter -->
            @if($inboxStaff->isNotEmpty())
                <select name="assigned_user_id" onchange="this.form.submit()"
                        class="px-3 py-2 rounded-xl bg-slate-50/70 border border-slate-200 text-xs text-slate-700 font-medium hover:bg-slate-100 focus:outline-none focus:border-slate-900 transition">
                    <option value="">Semua Staff</option>
                    <option value="unassigned" {{ ($staffFilter ?? '') === 'unassigned' ? 'selected' : '' }}>Belum Ditugaskan</option>
                    @foreach($inboxStaff as $st)
                        <option value="{{ $st->id }}" {{ (string) ($staffFilter ?? '') === (string) $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            @endif

            @if($search || $categoryId || $statusFilter || !empty($priorityFilter) || !empty($staffFilter))
                <a href="{{ route('tickets.inbox', ['scope' => $scope]) }}"
                   class="px-3 py-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold hover:bg-rose-100 transition inline-flex items-center gap-1">
                    <span>✕</span> Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Clean Data Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200/80 bg-slate-50/60 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    <th class="py-3.5 px-4 min-w-[150px]">Ticket ID</th>
                    <th class="py-3.5 px-4 min-w-[240px]">Subject</th>
                    <th class="py-3.5 px-4 min-w-[150px]">Customer</th>
                    <th class="py-3.5 px-4 min-w-[180px]">Agent (Staff)</th>
                    <th class="py-3.5 px-4 min-w-[140px]">Channel</th>
                    <th class="py-3.5 px-4 min-w-[140px]">Priority</th>
                    <th class="py-3.5 px-4 min-w-[100px]">Status</th>
                    <th class="py-3.5 px-4 min-w-[100px] text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                @forelse($tickets as $ticket)
                    @php
                        $initial = strtoupper(substr($ticket->creator?->name ?? 'U', 0, 1));
                        $agentInitial = $ticket->assignedUser ? strtoupper(substr($ticket->assignedUser->name, 0, 1)) : null;
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition">
                        <!-- 1. Ticket ID -->
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
                                        ✓ {{ $ticket->done_problems_count ?? 0 }}/{{ $ticket->problems_count }} poin
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

                        <!-- 4. Agent (Staff) + DETAIL BUTTON -->
                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            @if($ticket->assignedUser)
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                                            {{ $agentInitial }}
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-medium text-slate-900 block truncate">{{ $ticket->assignedUser->name }}</span>
                                            @php $prog = $ticket->progressPercent(); @endphp
                                            @if($prog !== null)
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <div class="w-10 h-1 rounded-full bg-slate-100 overflow-hidden">
                                                        <div class="h-full bg-emerald-500" style="width: {{ $prog }}%"></div>
                                                    </div>
                                                    <span class="text-[10px] font-bold text-emerald-700">{{ $prog }}%</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <!-- Tombol Detail Staff -->
                                    <button type="button"
                                            @click="staffDetail = {{ (int) $ticket->assigned_user_id }}"
                                            class="px-2 py-0.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-[10px] font-semibold text-slate-700 transition shrink-0 shadow-2xs">
                                        Detail
                                    </button>
                                </div>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                    Belum Ditugaskan
                                </span>
                            @endif
                        </td>

                        <!-- 5. Channel -->
                        <td class="py-4 px-4 align-top">
                            <span class="inline-block text-xs text-slate-700 font-medium">
                                {{ $ticket->category?->name ?? 'Umum' }}
                            </span>
                        </td>

                        <!-- 6. Quick Inline Priority Changer (Admin IT can update priority directly!) -->
                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <div x-data="{
                                open: false,
                                priority: '{{ $ticket->priority ?? 'medium' }}',
                                label: '{{ $ticket->priorityLabel() }}',
                                loading: false,
                                async setPriority(newVal, labelText) {
                                    this.loading = true;
                                    this.open = false;
                                    try {
                                        const formData = new FormData();
                                        formData.append('_token', '{{ csrf_token() }}');
                                        formData.append('priority', newVal);
                                        const res = await fetch('{{ route('tickets.priority', $ticket) }}', {
                                            method: 'POST',
                                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                            body: formData
                                        });
                                        const data = await res.json();
                                        if (data.success) {
                                            this.priority = newVal;
                                            this.label = labelText;
                                            window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message } }));
                                        }
                                    } catch (err) {
                                        console.error(err);
                                    } finally {
                                        this.loading = false;
                                    }
                                }
                            }" class="relative inline-block text-left">
                                <button @click="open = !open" type="button"
                                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg border border-slate-200/80 bg-white hover:bg-slate-50 text-xs font-medium text-slate-700 transition shadow-2xs">
                                    <span class="w-2 h-2 rounded-full shrink-0"
                                          :class="{
                                              'bg-emerald-500': priority === 'low',
                                              'bg-amber-400': priority === 'medium',
                                              'bg-orange-500': priority === 'high',
                                              'bg-rose-600': priority === 'urgent'
                                          }"></span>
                                    <span x-text="label" class="truncate max-w-[90px]"></span>
                                    <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div x-show="open" @click.outside="open = false" x-cloak
                                     class="absolute left-0 mt-1 w-44 rounded-xl bg-white border border-slate-200 shadow-xl p-1.5 z-30">
                                    <div class="px-2 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Set Prioritas</div>
                                    <button type="button" @click="setPriority('low', 'Rendah')" class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs hover:bg-slate-50 text-slate-600">
                                        <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Low / Rendah</span>
                                        <span x-show="priority === 'low'" class="text-indigo-600 font-bold">✓</span>
                                    </button>
                                    <button type="button" @click="setPriority('medium', 'Normal')" class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs hover:bg-slate-50 text-slate-600">
                                        <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Medium / Normal</span>
                                        <span x-show="priority === 'medium'" class="text-indigo-600 font-bold">✓</span>
                                    </button>
                                    <button type="button" @click="setPriority('high', 'Tinggi')" class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs hover:bg-slate-50 text-slate-600">
                                        <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-orange-500"></span> High / Tinggi</span>
                                        <span x-show="priority === 'high'" class="text-indigo-600 font-bold">✓</span>
                                    </button>
                                    <button type="button" @click="setPriority('urgent', 'Mendesak')" class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs hover:bg-slate-50 text-slate-600">
                                        <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-rose-600"></span> Urgent / Mendesak</span>
                                        <span x-show="priority === 'urgent'" class="text-indigo-600 font-bold">✓</span>
                                    </button>
                                </div>
                            </div>
                        </td>

                        <!-- 7. Status -->
                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <span class="inline-block text-[11px] font-medium px-2.5 py-1 rounded-md border {{ $statusColors[$ticket->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $ticket->statusLabel() }}
                            </span>
                        </td>

                        <!-- 8. Action -->
                        <td class="py-4 px-4 align-top text-right whitespace-nowrap">
                            <a href="{{ route('tickets.show', $ticket) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs transition shadow-2xs">
                                <span>Proses</span>
                                <span>&rarr;</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 px-4 text-center">
                            <div class="max-w-xs mx-auto text-center">
                                <span class="w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </span>
                                <p class="text-xs font-semibold text-slate-700">Tidak ada tiket pada antrean ini</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Semua tiket telah diproses atau sesuai dengan filter pencarian Anda.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
