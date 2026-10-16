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

<div class="flex items-center justify-between gap-4 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-900">Tiket Saya</h1>
        <p class="text-xs text-slate-500">Pantau posisi hirarki tiket yang Anda ajukan.</p>
    </div>
    <a href="{{ route('tickets.create') }}"
       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition">
        + Buat Tiket
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    @if($tickets->isEmpty())
        <div class="p-10 text-center text-xs text-slate-400">
            Anda belum pernah mengajukan tiket.
        </div>
    @else
        <div class="divide-y divide-slate-100">
            @foreach($tickets as $ticket)
                <a href="{{ route('tickets.show', $ticket) }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 hover:bg-slate-50 transition">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-semibold text-indigo-600">{{ $ticket->ticket_number }}</span>
                            <span class="text-sm font-semibold text-slate-900 truncate">{{ $ticket->title }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 mt-1.5 text-xs text-slate-500">
                            <span>Alur:</span>
                            @foreach($ticket->getHierarchyStepper() as $idx => $step)
                                @if($idx > 0)
                                    <span class="text-slate-300">→</span>
                                @endif
                                <span class="px-1.5 py-0.5 rounded text-[11px] {{ $step['state'] === 'active' ? 'bg-indigo-600 text-white font-semibold' : ($step['state'] === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500') }}">
                                    {{ $step['role']->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md border shrink-0 {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
                        {{ $ticket->statusLabel() }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
