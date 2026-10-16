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

<!-- Header & Tab Filter (Default: Sudah Di-Approve) -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-base font-bold text-slate-900">Riwayat Tiket Saya</h1>
        <p class="text-xs text-slate-500">Daftar riwayat tiket yang sudah di-approve beserta aksi preview dokumen yang diunggah.</p>
    </div>

    <div class="flex items-center gap-2">
        <div class="flex items-center gap-1 bg-white p-1 rounded-lg border border-slate-200">
            <a href="{{ route('tickets.my', ['tab' => 'approved', 'category_id' => $categoryId, 'search' => $search]) }}"
               class="px-3 py-1.5 rounded-md text-xs font-medium transition {{ $tab === 'approved' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Sudah Di-Approve ({{ $approvedCount }})
            </a>
            <a href="{{ route('tickets.my', ['tab' => 'active', 'category_id' => $categoryId, 'search' => $search]) }}"
               class="px-3 py-1.5 rounded-md text-xs font-medium transition {{ $tab === 'active' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Sedang Diproses
            </a>
            <a href="{{ route('tickets.my', ['tab' => 'all', 'category_id' => $categoryId, 'search' => $search]) }}"
               class="px-3 py-1.5 rounded-md text-xs font-medium transition {{ $tab === 'all' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
                Semua ({{ $allCount }})
            </a>
        </div>

        <button type="button" @click="createTicketModal = true"
                class="px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition shrink-0">
            + Buat Tiket
        </button>
    </div>
</div>

<!-- Search & Filter Kategori -->
<form action="{{ route('tickets.my') }}" method="GET" class="bg-white rounded-xl p-3 border border-slate-200 mb-4 grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
    <input type="hidden" name="tab" value="{{ $tab }}">

    <div class="sm:col-span-5">
        <input type="text" name="search" value="{{ $search }}"
               placeholder="Cari nomor atau judul tiket..."
               class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
    </div>
    <div class="sm:col-span-4">
        <select name="category_id" onchange="this.form.submit()"
                class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
            <option value="">Semua Kategori</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string) $categoryId === (string) $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-3 flex items-center gap-1.5">
        <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium">
            Filter
        </button>
        @if($search || $categoryId)
            <a href="{{ route('tickets.my', ['tab' => $tab]) }}" class="py-1.5 px-2.5 rounded-lg border border-slate-200 text-xs text-slate-500 hover:bg-slate-50">
                Reset
            </a>
        @endif
    </div>
</form>

<!-- Daftar Riwayat Tiket -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    @if($tickets->isEmpty())
        <div class="p-10 text-center text-xs text-slate-400">
            @if($tab === 'approved')
                Belum ada riwayat tiket yang sudah di-approve pada filter ini.
            @else
                Belum ada tiket yang ditemukan.
            @endif
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach($tickets as $ticket)
                @php
                    $lastApprovalLog = $ticket->logs->whereIn('action', ['approved_step', 'final_approved', 'resolved'])->last();
                @endphp
                <div class="p-4 hover:bg-slate-50/70 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-xs font-semibold text-indigo-600">{{ $ticket->ticket_number }}</span>
                            <a href="{{ route('tickets.show', $ticket) }}" class="text-sm font-semibold text-slate-900 hover:text-indigo-600 truncate">
                                {{ $ticket->title }}
                            </a>
                            <span class="text-[11px] font-medium px-2 py-0.5 rounded-md border {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
                                {{ $ticket->statusLabel() }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 mt-1">
                            {{ $ticket->category?->name }}
                            • Posisi Hirarki: <strong class="text-slate-700">Role {{ $ticket->currentRole?->name }}</strong>
                            @if($lastApprovalLog)
                                • <span class="text-emerald-700 font-medium">Di-approve oleh {{ $lastApprovalLog->user?->name }} ({{ $lastApprovalLog->user?->role?->name }})</span>
                            @endif
                        </p>
                    </div>

                    <!-- Aksi Preview Dokumen & Detail -->
                    <div class="flex items-center gap-2 shrink-0">
                        @if($ticket->attachment_path)
                            <button type="button"
                                    @click="openDocPreview('{{ route('tickets.preview', $ticket) }}', '{{ route('tickets.attachment', $ticket) }}', '{{ addslashes($ticket->attachment_name ?? 'Dokumen Lampiran') }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-semibold transition">
                                <span>📎 Preview Dokumen</span>
                            </button>
                        @endif

                        <a href="{{ route('tickets.show', $ticket) }}"
                           class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-medium transition">
                            Detail →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
