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

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-900">Tiket Masuk — Role {{ $user->role?->name }}</h1>
        <p class="text-xs text-slate-500">
            Daftar tiket dari role bawahan untuk diproses atau diteruskan ke
            <strong>{{ $user->role?->parent?->name ?? 'Atasan' }}</strong>.
        </p>
    </div>

    <div class="flex items-center gap-1.5 bg-white p-1 rounded-lg border border-slate-200">
        <a href="{{ route('tickets.inbox', ['scope' => 'current_role']) }}"
           class="px-3 py-1.5 rounded-md text-xs font-semibold transition {{ $scope === 'current_role' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            Antrean Role ({{ $user->role?->name }})
        </a>
        <a href="{{ route('tickets.inbox', ['scope' => 'history_role']) }}"
           class="px-3 py-1.5 rounded-md text-xs font-semibold transition {{ $scope === 'history_role' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            Riwayat Diteruskan
        </a>
        @if($user->isSuperuser())
            <a href="{{ route('tickets.inbox', ['scope' => 'all_system']) }}"
               class="px-3 py-1.5 rounded-md text-xs font-semibold transition {{ $scope === 'all_system' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                Semua Tiket
            </a>
        @endif
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    @if($tickets->isEmpty())
        <div class="p-10 text-center text-xs text-slate-400">
            Tidak ada tiket pada daftar ini.
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
                        <p class="text-xs text-slate-500 mt-1">
                            Pemohon: <strong class="text-slate-700">{{ $ticket->creator?->name }}</strong> ({{ $ticket->creatorRole?->name }})
                            • Kategori: {{ $ticket->category?->name }}
                            • {{ $ticket->created_at->format('d M Y H:i') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-[11px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">
                            Di: {{ $ticket->currentRole?->name }}
                        </span>
                        <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md border {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
                            {{ $ticket->statusLabel() }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
