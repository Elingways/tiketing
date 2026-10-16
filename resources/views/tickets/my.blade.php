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

<div class="flex items-center justify-between gap-4 mb-4">
    <div>
        <h1 class="text-base font-bold text-slate-900">Tiket Saya</h1>
        <p class="text-xs text-slate-500">Daftar tiket yang Anda ajukan beserta posisi hirarkinya.</p>
    </div>
    <a href="{{ route('tickets.create') }}"
       class="px-3.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
        + Buat Tiket
    </a>
</div>

<!-- Search & Filter Kategori -->
<form action="{{ route('tickets.my') }}" method="GET" class="bg-white rounded-xl p-3 border border-slate-200 mb-4 grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
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
            <a href="{{ route('tickets.my') }}" class="py-1.5 px-2.5 rounded-lg border border-slate-200 text-xs text-slate-500 hover:bg-slate-50">
                Reset
            </a>
        @endif
    </div>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    @if($tickets->isEmpty())
        <div class="p-10 text-center text-xs text-slate-400">
            Belum ada tiket yang ditemukan.
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach($tickets as $ticket)
                <a href="{{ route('tickets.show', $ticket) }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-3.5 hover:bg-slate-50 transition">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-semibold text-indigo-600">{{ $ticket->ticket_number }}</span>
                            <span class="text-sm font-semibold text-slate-900 truncate">{{ $ticket->title }}</span>
                            @if($ticket->attachment_path)
                                <span class="text-[11px] text-slate-400">📎</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ $ticket->category?->name }}
                            • Posisi Saat Ini: <strong class="text-slate-700">{{ $ticket->currentRole?->name }}</strong>
                            • Prioritas: {{ $ticket->priorityLabel() }}
                        </p>
                    </div>

                    <span class="text-[11px] font-medium px-2.5 py-0.5 rounded-md border shrink-0 {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
                        {{ $ticket->statusLabel() }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
