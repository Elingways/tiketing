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
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-base font-bold text-slate-900">Tiket Masuk — Role {{ $user->role?->name }}</h1>
        <p class="text-xs text-slate-500">
            Atur prioritas tiket masuk dan tindak lanjuti sesuai hirarki ({{ $user->role?->parent ? 'Atasan: ' . $user->role->parent->name : 'Puncak Hirarki' }}).
        </p>
    </div>

    <div class="flex items-center gap-1 bg-white p-1 rounded-lg border border-slate-200">
        <a href="{{ route('tickets.inbox', ['scope' => 'current_role', 'category_id' => $categoryId, 'search' => $search]) }}"
           class="px-3 py-1.5 rounded-md text-xs font-medium transition {{ $scope === 'current_role' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
            Di Meja {{ $user->role?->name }}
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

<!-- Bar Pencarian & Filter Kategori -->
<form action="{{ route('tickets.inbox') }}" method="GET" class="bg-white rounded-xl p-3 border border-slate-200 mb-4 grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
    <input type="hidden" name="scope" value="{{ $scope }}">

    <div class="sm:col-span-5">
        <input type="text" name="search" value="{{ $search }}"
               placeholder="Cari nomor tiket, judul, atau nama pemohon..."
               class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
    </div>

    <div class="sm:col-span-4">
        <select name="category_id" onchange="this.form.submit()"
                class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
            <option value="">Semua Kategori Layanan</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string) $categoryId === (string) $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="sm:col-span-3 flex items-center gap-1.5">
        <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition">
            Cari / Filter
        </button>
        @if($search || $categoryId || $statusFilter)
            <a href="{{ route('tickets.inbox', ['scope' => $scope]) }}"
               class="py-1.5 px-2.5 rounded-lg border border-slate-200 text-xs text-slate-500 hover:bg-slate-50">
                Reset
            </a>
        @endif
    </div>
</form>

<!-- Daftar Tiket Masuk dengan Aksi Set Prioritas & Preview Dokumen -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    @if($tickets->isEmpty())
        <div class="p-10 text-center text-xs text-slate-400">
            Tidak ada tiket yang sesuai dengan filter pencarian.
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach($tickets as $ticket)
                @php
                    $canSetPriority = $ticket->canBeHandledBy($user);
                @endphp
                <div class="p-4 hover:bg-slate-50/70 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-xs font-semibold text-indigo-600">{{ $ticket->ticket_number }}</span>
                            <a href="{{ route('tickets.show', $ticket) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-600 truncate">
                                {{ $ticket->title }}
                            </a>
                            @if($ticket->attachment_path)
                                <button type="button"
                                        @click="openDocPreview('{{ route('tickets.preview', $ticket) }}', '{{ route('tickets.attachment', $ticket) }}', '{{ addslashes($ticket->attachment_name ?? 'Dokumen Lampiran') }}')"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-50 hover:bg-indigo-100 text-[11px] text-indigo-700 font-medium">
                                    📎 Preview Dokumen
                                </button>
                            @endif
                        </div>

                        <p class="text-xs text-slate-500 mt-1">
                            Pemohon: <strong class="text-slate-700">{{ $ticket->creator?->name }}</strong> ({{ $ticket->creatorRole?->name }})
                            • {{ $ticket->category?->name }}
                            • {{ $ticket->created_at->format('d M Y, H:i') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        @if($canSetPriority)
                            <form action="{{ route('tickets.priority', $ticket) }}" method="POST" class="flex items-center">
                                @csrf
                                <select name="priority" onchange="this.form.submit()"
                                        class="px-2.5 py-1 rounded-lg border text-xs font-medium cursor-pointer focus:outline-none {{ $ticket->priority ? 'border-slate-200 bg-white text-slate-700' : 'border-amber-300 bg-amber-50 text-amber-800' }}">
                                    <option value="" disabled {{ !$ticket->priority ? 'selected' : '' }}>Set Prioritas...</option>
                                    <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Prioritas: Rendah</option>
                                    <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Prioritas: Normal</option>
                                    <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>Prioritas: Tinggi</option>
                                    <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Prioritas: Mendesak</option>
                                </select>
                            </form>
                        @else
                            <span class="text-[11px] px-2 py-1 rounded-md {{ $priorityColors[$ticket->priority] ?? 'bg-slate-100 text-slate-500' }}">
                                Prioritas: {{ $ticket->priorityLabel() }}
                            </span>
                        @endif

                        <span class="text-[11px] font-medium px-2.5 py-1 rounded-md border {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
                            {{ $ticket->statusLabel() }}
                        </span>

                        <a href="{{ route('tickets.show', $ticket) }}"
                           class="px-3 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition">
                            Detail →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
