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

<div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">Tiket Saya & Tracking Hirarki</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Daftar seluruh tiket yang Anda ajukan beserta progres tahapan rolenya saat ini.
            </p>
        </div>
        <a href="{{ route('tickets.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-extrabold shadow-md shadow-indigo-500/20 transition">
            @include('partials.icon', ['name' => 'plus-circle', 'class' => 'w-4 h-4'])
            Ajukan Tiket Baru
        </a>
    </div>

    <div class="mt-6">
        @if($tickets->isEmpty())
            <div class="p-10 rounded-2xl bg-slate-50 border border-dashed border-slate-200 text-center">
                <p class="text-sm font-bold text-slate-700">Anda belum pernah mengajukan tiket</p>
                <p class="text-xs text-slate-500 mt-1">Klik tombol Ajukan Tiket Baru di kanan atas untuk mengirim tiket ke role atasan Anda.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($tickets as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}"
                       class="block p-5 rounded-2xl border border-slate-200/80 hover:border-indigo-400 transition bg-slate-50/40 hover:bg-white">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-extrabold text-indigo-600">{{ $ticket->ticket_number }}</span>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
                                    {{ $ticket->statusLabel() }}
                                </span>
                            </div>
                            <span class="text-xs text-slate-400">{{ $ticket->created_at->diffForHumans() }}</span>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900">{{ $ticket->title }}</h3>
                        <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $ticket->description }}</p>

                        <!-- Mini Visual Stepper -->
                        <div class="mt-4 pt-3 border-t border-slate-200/70 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[11px] text-slate-400 font-semibold mr-1">Jalur Hirarki:</span>
                                @foreach($ticket->getHierarchyStepper() as $idx => $step)
                                    @if($idx > 0)
                                        <span class="text-slate-300 text-xs">→</span>
                                    @endif
                                    <span class="text-[11px] px-2.5 py-0.5 rounded-lg font-bold {{ $step['state'] === 'active' ? 'bg-indigo-600 text-white ring-2 ring-indigo-200' : ($step['state'] === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500') }}">
                                        {{ $step['role']->name }}
                                    </span>
                                @endforeach
                            </div>
                            <span class="text-xs font-bold text-indigo-600">Lihat Detail & Timeline →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
