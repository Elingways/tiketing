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
    $priorityColors = [
        'low' => 'bg-slate-100 text-slate-600',
        'medium' => 'bg-sky-50 text-sky-700',
        'high' => 'bg-amber-50 text-amber-700',
        'urgent' => 'bg-rose-50 text-rose-700 font-semibold',
    ];
@endphp

<!-- Header & Tab Scope -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-900">Tiket Masuk — Role {{ $user->role?->name }}</h1>
        <p class="text-xs text-slate-500">
            @if($isHead && $scope === 'current_role')
                Pantau nama staff, progres pengerjaan, dan jumlah tiket yang sedang dikerjakan.
            @else
                Tetapkan prioritas tiket masuk dan proses sesuai tahapan hirarki ({{ $user->role?->parent ? 'Atasan: ' . $user->role->parent->name : 'Puncak Hirarki' }}).
            @endif
        </p>
    </div>

    <div class="flex items-center gap-1 bg-white p-1 rounded-lg border border-slate-200 self-start sm:self-auto">
        <a href="{{ route('tickets.inbox', ['scope' => 'current_role', 'category_id' => $categoryId, 'search' => $search]) }}"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition {{ $scope === 'current_role' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
            Antrean {{ $user->role?->name }}
        </a>
        <a href="{{ route('tickets.inbox', ['scope' => 'history_role', 'category_id' => $categoryId, 'search' => $search]) }}"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition {{ $scope === 'history_role' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
            Riwayat Diteruskan
        </a>
        @if($user->isSuperuser())
            <a href="{{ route('tickets.inbox', ['scope' => 'all_system', 'category_id' => $categoryId, 'search' => $search]) }}"
               class="px-3 py-1.5 rounded-md text-xs font-medium transition {{ $scope === 'all_system' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Semua Tiket
            </a>
        @endif
    </div>
</div>

<div x-data="{ staffDetail: null }">
<!-- Card Utama: Toolbar Search/Dropdown + Tabel Tiket Masuk -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
    <!-- Toolbar Pencarian & Filter Kategori -->
    <form action="{{ route('tickets.inbox') }}" method="GET" class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <input type="hidden" name="scope" value="{{ $scope }}">

        <div class="relative flex-1 max-w-md">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Cari No. Tiket, Judul, atau Nama Pemohon..."
                   class="w-full pl-9 pr-3 py-2 rounded-lg bg-white border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
        </div>

        <div class="flex items-center gap-2">
            <select name="category_id" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-slate-900">
                <option value="">Semua Kategori Layanan</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (string) $categoryId === (string) $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="px-3.5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition">
                Filter
            </button>

            @if($search || $categoryId || $statusFilter)
                <a href="{{ route('tickets.inbox', ['scope' => $scope]) }}"
                   class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-600 hover:bg-slate-100">
                    Reset
                </a>
            @endif
        </div>
    </form>

    @if($isHead && $scope === 'current_role')
        @php
            $activeStaff = $staffProgress->filter(fn ($progress) => $progress['tickets'] > 0)->sortByDesc('tickets')->values();
            $staffIds = $staffProgress->map(fn ($progress) => (int) $progress['user']->id);
            $waitingTickets = $tickets->filter(fn ($ticket) => ! $staffIds->contains((int) $ticket->assigned_user_id))->values();
        @endphp
        <div class="divide-y divide-slate-100">
            @forelse($activeStaff as $progress)
                <div class="px-5 py-4 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900">{{ $progress['user']->name }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            {{ $progress['tickets'] }} tiket sedang dikerjakan
                            @if(($progress['blocked'] ?? 0) > 0)
                                <span class="font-semibold text-rose-700">· {{ $progress['blocked'] }} blocked</span>
                            @endif
                        </p>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="h-1.5 w-28 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full bg-emerald-500" style="width: {{ $progress['percent'] ?? 0 }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-emerald-700">{{ $progress['percent'] === null ? '—' : $progress['percent'] . '%' }}</span>
                        </div>
                    </div>
                    <button type="button"
                            @click="staffDetail = {{ (int) $progress['user']->id }}"
                            class="shrink-0 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-xs font-medium text-slate-700">
                        Detail
                    </button>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-xs text-slate-400">Belum ada staff yang sedang mengerjakan tiket.</p>
            @endforelse
        </div>
        @if($waitingTickets->isNotEmpty())
            <div class="border-t border-slate-200">
                <div class="px-5 py-3 bg-amber-50/70 border-b border-amber-100 flex items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-800">Tiket Baru Masuk — Belum Dibagikan ke Staff ({{ $waitingTickets->count() }})</p>
                    <span class="text-[11px] text-amber-700">Klik Proses untuk set prioritas, input poin pekerjaan (%), &amp; deliver ke staff</span>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($waitingTickets as $ticket)
                        <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-slate-50/70 transition">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-[11px] font-semibold text-indigo-600">{{ $ticket->ticket_number }}</span>
                                    @if($ticket->category)
                                        <span class="px-2 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-200 text-[10px] font-semibold">{{ $ticket->category->name }}</span>
                                    @endif
                                    @if($ticket->attachment_path)
                                        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[10px] font-medium">
                                            {{ $ticket->isImageAttachment() ? '🖼️ Gambar Bukti' : '📎 Lampiran' }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs font-semibold text-slate-900 truncate mt-1">{{ $ticket->title }}</p>
                                <p class="text-[11px] text-slate-500">{{ $ticket->creator?->name }} ({{ $ticket->creatorRole?->name }}) · {{ $ticket->priorityLabel() }}</p>
                            </div>
                            <a href="{{ route('tickets.show', $ticket) }}"
                               class="shrink-0 px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium">
                                Proses →
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
    <!-- Tabel Tiket Masuk -->
    <div class="overflow-x-auto">
        <table class="w-full min-w-[1080px] text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <th class="py-3 px-4 w-36">No. Tiket</th>
                    <th class="py-3 px-4 w-44">Pemohon</th>
                    <th class="py-3 px-4 min-w-[240px]">Judul & Kategori</th>
                    <th class="py-3 px-4 w-44">Proses (Tahap Hirarki)</th>
                    <th class="py-3 px-4 w-44">Set Prioritas</th>
                    <th class="py-3 px-4 w-28">Status</th>
                    <th class="py-3 px-4 w-28 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                @forelse($tickets as $ticket)
                    @php
                        $canSetPriority = $ticket->canBeHandledBy($user);
                        $stepper = $ticket->getHierarchyStepper();
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition align-top">
                        <!-- 1. No. Tiket -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="font-mono font-semibold text-indigo-600 block">{{ $ticket->ticket_number }}</span>
                            <span class="text-[11px] text-slate-400">{{ $ticket->created_at->format('d M Y') }}</span>
                        </td>

                        <!-- 2. Pemohon -->
                        <td class="py-3.5 px-4">
                            <span class="font-semibold text-slate-900 block">{{ $ticket->creator?->name }}</span>
                            <span class="text-[11px] text-slate-500">Role: {{ $ticket->creatorRole?->name }}</span>
                        </td>

                        <!-- 3. Judul & Kategori -->
                        <td class="py-3.5 px-4">
                            <a href="{{ route('tickets.show', $ticket) }}" class="font-semibold text-slate-900 hover:text-indigo-600 block">
                                {{ $ticket->title }}
                            </a>
                            <span class="text-[11px] text-slate-500 block mt-0.5">{{ $ticket->category?->name }}</span>
                            @php $percent = $ticket->progressPercent(); @endphp
                            @if($percent !== null)
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-emerald-500" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <span class="text-[11px] font-semibold text-emerald-700">{{ $percent }}%</span>
                                    @if($ticket->assignedUser)
                                        <span class="text-[11px] text-slate-400">{{ $ticket->assignedUser->name }}</span>
                                    @endif
                                </div>
                            @endif
                            @if($ticket->attachment_path)
                                <button type="button"
                                        @click="openDocPreview('{{ route('tickets.preview', $ticket) }}', '{{ route('tickets.attachment', $ticket) }}', '{{ addslashes($ticket->attachment_name ?? 'Dokumen Lampiran') }}')"
                                        class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-medium">
                                    📎 Preview Dokumen
                                </button>
                            @endif
                        </td>

                        <!-- 4. Kolom Proses (Tahap Hirarki) -->
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-1 flex-wrap mb-1">
                                @foreach($stepper as $idx => $step)
                                    @if($idx > 0)
                                        <span class="text-slate-300 text-[10px]">→</span>
                                    @endif
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $step['state'] === 'active' ? 'bg-slate-900 text-white' : ($step['state'] === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-400') }}">
                                        {{ $step['role']->name }}
                                    </span>
                                @endforeach
                            </div>
                            <span class="text-[11px] text-slate-500">
                                Tahap {{ $ticket->approval_step }} • Posisi: <strong class="text-slate-700">{{ $ticket->currentRole?->name }}</strong>
                            </span>
                        </td>

                        <!-- 5. Set Prioritas -->
                        <td class="py-3.5 px-4 align-top">
                            @php
                                $canPickStaff = $ticket->canBeDirectedBy($user)
                                    && $inboxStaff->isNotEmpty()
                                    && (int) $ticket->current_role_id === (int) $user->role_id;
                                $approveOnlyRow = (bool) $ticket->nextSuperiorRole()
                                    && ! $ticket->currentRole?->head_user_id
                                    && ! $user->isSuperuser();
                            @endphp
                            @if($canPickStaff)
                                <form action="{{ route('tickets.priority', $ticket) }}" method="POST" class="space-y-1.5 w-40">
                                    @csrf
                                    @if($ticket->priority_rank)
                                        <p class="text-[11px] font-medium text-slate-700">{{ $ticket->priorityLabel() }}</p>
                                        <input type="hidden" name="priority_rank" value="{{ $ticket->priority_rank }}">
                                    @else
                                        <select name="priority_rank" required
                                                class="w-full px-2.5 py-1 rounded-lg border border-amber-300 bg-amber-50 text-xs text-amber-800 focus:outline-none">
                                            <option value="" disabled selected>Prioritas pemohon...</option>
                                            @for($level = 1; $level <= 4; $level++)
                                                <option value="{{ $level }}">{{ \App\Models\RolePriorityRoute::labelFor($level) }}</option>
                                            @endfor
                                        </select>
                                    @endif
                                    <select name="assigned_user_id" required
                                            class="w-full px-2.5 py-1 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none">
                                        <option value="" disabled {{ $ticket->assigned_user_id ? '' : 'selected' }}>Arahkan ke staff...</option>
                                        @foreach($inboxStaff as $staff)
                                            @continue((int) $staff->id === (int) $user->id)
                                            <option value="{{ $staff->id }}" {{ (int) $ticket->assigned_user_id === (int) $staff->id ? 'selected' : '' }}>
                                                {{ $staff->name }}@if($staff->keahlian) — {{ $staff->keahlian }}@endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="w-full px-2 py-1 rounded-lg bg-slate-900 text-white text-[11px] font-semibold">
                                        Sesuai & bagikan
                                    </button>
                                    <p class="text-[10px] leading-snug text-slate-400">Hanya jika level pemohon sesuai isi permintaan. Kalau tidak, buka tiket lalu pilih tolak.</p>
                                </form>
                            @elseif($canSetPriority && !$ticket->currentRole?->head_user_id && !$approveOnlyRow)
                                <form action="{{ route('tickets.priority', $ticket) }}" method="POST">
                                    @csrf
                                    <select name="priority" onchange="this.form.submit()"
                                            class="w-full px-2.5 py-1 rounded-lg border text-xs font-medium cursor-pointer focus:outline-none {{ $ticket->priority ? 'border-slate-200 bg-white text-slate-700' : 'border-amber-300 bg-amber-50 text-amber-800' }}">
                                        <option value="" disabled {{ !$ticket->priority ? 'selected' : '' }}>Pilih Prioritas...</option>
                                        <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Rendah</option>
                                        <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Normal</option>
                                        <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>Tinggi</option>
                                        <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Mendesak</option>
                                    </select>
                                </form>
                            @else
                                <span class="text-[11px] px-2 py-0.5 rounded {{ $priorityColors[$ticket->priority] ?? 'bg-slate-100 text-slate-500' }}">
                                    {{ $ticket->priorityLabel() }}
                                </span>
                                @if($ticket->priority_rank && $ticket->assignedUser)
                                    <span class="mt-1 block text-[11px] text-slate-500">Ke {{ $ticket->assignedUser->name }}</span>
                                @endif
                            @endif
                        </td>

                        <!-- 6. Status -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="inline-block text-[11px] font-medium px-2.5 py-0.5 rounded-md border {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
                                {{ $ticket->statusLabel() }}
                            </span>
                        </td>

                        <!-- 7. Aksi -->
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="inline-flex items-center justify-end gap-1.5">
                                <a href="{{ route('tickets.show', $ticket) }}"
                                   class="inline-block px-3 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-medium transition">
                                    {{ $approveOnlyRow ? 'Setuju →' : 'Proses →' }}
                                </a>
                                @if($ticket->assignedUser && $staffProgress->contains(fn ($progress) => (int) $progress['user']->id === (int) $ticket->assigned_user_id))
                                    <button type="button"
                                            @click="staffDetail = {{ (int) $ticket->assigned_user_id }}"
                                            class="inline-block px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-medium transition">
                                        Detail
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if($ticket->progress_note)
                        <tr class="bg-slate-50/60">
                            <td colspan="7" class="px-4 pb-3 pt-0">
                                <p class="text-[11px] text-slate-600 leading-relaxed">
                                    <span class="font-semibold text-slate-700">Catatan {{ $ticket->assignedUser?->name ?? 'staff' }}:</span>
                                    {{ $ticket->progress_note }}
                                </p>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="py-10 px-4 text-center text-xs text-slate-400">
                            Tidak ada tiket masuk pada filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>

<div x-show="staffDetail" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50"
     @keydown.escape.window="staffDetail = null">
    <div @click.outside="staffDetail = null" class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full max-h-[80vh] flex flex-col overflow-hidden">
        @foreach($staffProgress as $progress)
            <div x-show="staffDetail === {{ (int) $progress['user']->id }}" class="flex flex-col min-h-0">
                <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Tiket {{ $progress['user']->name }}@if($progress['user']->keahlian) — {{ $progress['user']->keahlian }}@endif</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            {{ $progress['tickets'] }} tiket sedang dikerjakan
                            @if($progress['percent'] !== null) · rata-rata {{ $progress['percent'] }}% @endif
                        </p>
                    </div>
                    <button type="button" @click="staffDetail = null" class="text-slate-400 hover:text-slate-700">✕</button>
                </div>
                <div class="overflow-y-auto divide-y divide-slate-100">
                    @forelse($progress['open_tickets'] as $openTicket)
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <a href="{{ route('tickets.show', $openTicket) }}" class="font-mono text-[11px] font-semibold text-indigo-600 hover:underline">{{ $openTicket->ticket_number }}</a>
                                <span class="text-[11px] font-semibold {{ $openTicket->work_status === 'blocked' ? 'text-rose-700' : 'text-slate-700' }}">
                                    @if($openTicket->workStatusLabel()){{ $openTicket->workStatusLabel() }} · @endif{{ $openTicket->progressPercent() === null ? '—' : $openTicket->progressPercent() . '%' }}
                                </span>
                            </div>
                            <a href="{{ route('tickets.show', $openTicket) }}" class="mt-1 block text-xs font-semibold text-slate-900 hover:text-indigo-600">{{ $openTicket->title }}</a>
                            <p class="mt-0.5 text-[11px] text-slate-500">
                                {{ $openTicket->statusLabel() }}
                                @if(($openTicket->problems_count ?? 0) > 0)
                                    · <span class="font-semibold text-emerald-700">{{ $openTicket->done_problems_count ?? 0 }}/{{ $openTicket->problems_count }} poin selesai</span>
                                @endif
                                @if($openTicket->priority_rank)
                                    · Usulan pemohon: {{ $openTicket->priorityLabel() }}
                                @endif
                            </p>
                            <form action="{{ route('tickets.priority', $openTicket) }}" method="POST" class="mt-2">
                                @csrf
                                <p class="text-[11px] font-medium text-slate-500 mb-1.5">
                                    Nilai prioritas
                                    @if(!$openTicket->assessed_priority_rank)
                                        <span class="text-amber-700">· belum dinilai</span>
                                    @endif
                                </p>
                                <div class="space-y-1.5">
                                    @for($level = 1; $level <= 4; $level++)
                                        <button type="submit" name="assessed_priority_rank" value="{{ $level }}"
                                                class="px-2 py-1.5 rounded-lg border text-left text-[11px] font-medium {{ (int) $openTicket->assessed_priority_rank === $level ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                                            {{ \App\Models\RolePriorityRoute::labelFor($level) }}
                                        </button>
                                    @endfor
                                </div>
                            </form>
                            @if($openTicket->progress_note)
                                <p class="mt-2 text-[11px] text-slate-600">{{ $openTicket->progress_note }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-6 text-xs text-slate-400">Belum ada tiket yang sedang dikerjakan.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
</div>
@endsection
