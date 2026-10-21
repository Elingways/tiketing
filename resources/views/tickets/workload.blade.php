@extends('layouts.app')

@section('content')
<div class="mb-5">
    <h1 class="text-lg font-bold text-slate-900">Pekerjaan Divisi</h1>
    <p class="text-xs text-slate-500">Pekerjaan yang sedang dikerjakan IT dan setiap divisi.</p>
</div>

@forelse($groups as $division => $tickets)
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900">{{ $division }}</h2>
            <span class="text-[11px] text-slate-500">{{ $tickets->count() }} pekerjaan</span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($tickets as $ticket)
                <div class="px-5 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-mono text-[11px] font-semibold text-indigo-600">{{ $ticket->ticket_number }}</span>
                        <span class="text-[11px] font-semibold {{ $ticket->work_status === 'blocked' ? 'text-rose-700' : 'text-slate-700' }}">
                            {{ $ticket->workStatusLabel() ?? $ticket->statusLabel() }}
                            · {{ $ticket->progressPercent() === null ? '—' : $ticket->progressPercent().'%' }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs font-semibold text-slate-900">{{ $ticket->title }}</p>
                    @include('tickets.partials.dates', ['ticket' => $ticket, 'format' => 'd M Y'])
                    <p class="mt-0.5 text-[11px] text-slate-500">
                        {{ $ticket->category?->name }}
                        · {{ $ticket->assignedUser?->name ?? 'Belum ada petugas' }}
                        · {{ $ticket->statusLabel() }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="bg-white rounded-xl border border-slate-200 px-5 py-8 text-center text-xs text-slate-400">
        Belum ada pekerjaan yang sedang dikerjakan.
    </div>
@endforelse
@endsection
