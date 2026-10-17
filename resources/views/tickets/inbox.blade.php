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
            Tetapkan prioritas tiket masuk dan proses sesuai tahapan hirarki ({{ $user->role?->parent ? 'Atasan: ' . $user->role->parent->name : 'Puncak Hirarki' }}).
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

    <!-- Tabel Tiket Masuk -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <th class="py-3 px-4 w-36">No. Tiket</th>
                    <th class="py-3 px-4 w-44">Pemohon</th>
                    <th class="py-3 px-4">Judul & Kategori</th>
                    <th class="py-3 px-4">Proses (Tahap Hirarki)</th>
                    <th class="py-3 px-4 w-40">Set Prioritas</th>
                    <th class="py-3 px-4 w-32">Status</th>
                    <th class="py-3 px-4 w-36 text-right">Aksi</th>
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
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($canSetPriority)
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
                            <a href="{{ route('tickets.show', $ticket) }}"
                               class="inline-block px-3 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-medium transition">
                                Proses →
                            </a>
                        </td>
                    </tr>
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
</div>
@endsection
