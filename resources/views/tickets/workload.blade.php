@extends('layouts.app')

@section('content')
@php
    $statusColor = [
        'blocked' => 'text-rose-700',
        'in_progress' => 'text-sky-700',
        'finished' => 'text-emerald-700',
        'delivered' => 'text-indigo-700',
    ];
@endphp

<div class="mb-5">
    <h1 class="text-lg font-bold text-slate-900">Pekerjaan Divisi</h1>
    <p class="text-xs text-slate-500">Pekerjaan yang sedang dikerjakan IT dan setiap divisi.</p>
</div>

@forelse($groups as $division => $tickets)
    <section class="mb-6">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900">{{ $division }}</h2>
            <span class="text-[11px] text-slate-500">{{ $tickets->count() }} pekerjaan</span>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach($tickets as $ticket)
                @php
                    $percent = $ticket->progressPercent();
                    $finished = $ticket->work_status === 'finished';
                @endphp
                <article class="relative overflow-hidden rounded-xl border px-4 py-3 {{ $finished ? 'border-slate-200 bg-slate-100' : 'border-slate-200 bg-white' }}">
                    <div class="{{ $finished ? 'opacity-40' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-sm font-semibold leading-snug {{ $finished ? 'text-slate-500' : 'text-slate-900' }}">{{ $ticket->title }}</h3>
                            <span class="shrink-0 text-[11px] font-semibold {{ $finished ? 'text-slate-400' : ($statusColor[$ticket->work_status] ?? 'text-slate-600') }}">
                                {{ $ticket->workStatusLabel() ?? $ticket->statusLabel() }}
                                · {{ $percent === null ? '—' : $percent.'%' }}
                            </span>
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-500">
                            {{ $ticket->ticket_number }}
                            · {{ $ticket->assignedUser?->name ?? 'Belum ada petugas' }}
                            @if($ticket->category)
                                · {{ $ticket->category->name }}
                            @endif
                        </p>
                        <p class="mt-1 text-[11px] text-slate-400">
                            Request date {{ $ticket->created_at->format('d M Y') }}
                            · Delivered date {{ $ticket->delivered_at ? $ticket->delivered_at->format('d M Y') : 'Belum diarahkan' }}
                        </p>
                    </div>
                    @if($finished)
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="rounded-md bg-slate-200 px-3 py-1 text-sm font-semibold tracking-wide text-slate-500">Selesai</span>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@empty
    <div class="rounded-xl border border-slate-200 bg-white px-5 py-8 text-center text-xs text-slate-400">
        Belum ada pekerjaan yang sedang dikerjakan.
    </div>
@endforelse
@endsection
