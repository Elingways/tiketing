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

<style>
    @keyframes rank-rise {
        from { transform: scaleY(0); }
        to { transform: scaleY(1); }
    }
    .rank-bar {
        transform-origin: bottom;
        animation: rank-rise .6s cubic-bezier(.22,1,.36,1) both;
    }
    .rank-bar-1 { height: 96px; background: linear-gradient(180deg,#fbbf24,#f59e0b); }
    .rank-bar-2 { height: 72px; background: linear-gradient(180deg,#cbd5e1,#94a3b8); }
    .rank-bar-3 { height: 56px; background: linear-gradient(180deg,#d8a878,#b45309); }
</style>

@forelse($groups as $division => $tickets)
    <section class="mb-6">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-bold text-slate-900">{{ $division }}</h2>
            <span class="text-[11px] text-slate-500">{{ $tickets->count() }} pekerjaan</span>
        </div>
        @php
            $rows = $ranking[$division] ?? collect();
            $showFullRanking = $canSeeFullRanking ?? false;
            $mine = null;
            if (!$showFullRanking) {
                foreach ($rows as $index => $row) {
                    if ((int) ($row['id'] ?? 0) === (int) $user->id) {
                        $mine = $row;
                        $mine['place'] = $index + 1;
                        break;
                    }
                }
            }
        @endphp
        @if($showFullRanking && $rows->isNotEmpty())
            @php
                $podium = $rows->take(3)->values();
                $rest = $rows->slice(3);
                $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
                $visualOrder = [1 => 'order-2', 2 => 'order-1', 3 => 'order-3'];
            @endphp
            <section class="mb-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-4 py-2.5">
                    <h2 class="text-sm font-bold text-slate-900">Ranking achievement</h2>
                    <p class="text-[11px] text-slate-500">Staff IT, diurutkan dari poin terbanyak.</p>
                </div>
                <ol class="flex items-end justify-center gap-2 px-3 pt-5 sm:gap-3 sm:px-6">
                    @foreach($podium as $index => $row)
                        @php
                            $place = $index + 1;
                            $isMe = (int) ($row['id'] ?? 0) === (int) $user->id;
                        @endphp
                        <li class="flex min-w-0 flex-1 flex-col items-center {{ $visualOrder[$place] }}">
                            <span class="sr-only">Peringkat {{ $place }}</span>
                            <span class="relative mb-2 {{ $place === 1 ? 'h-14 w-14 text-lg' : 'h-12 w-12 text-base' }}">
                                @if($row['avatar_url'])
                                    <img src="{{ $row['avatar_url'] }}" alt="" class="h-full w-full rounded-full object-cover ring-2 ring-white shadow-md">
                                @else
                                    <span class="flex h-full w-full items-center justify-center rounded-full font-extrabold text-white ring-2 ring-white shadow-md" style="background: {{ $row['color'] }}">{{ $row['initials'] }}</span>
                                @endif
                                <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs shadow">{{ $medals[$place] }}</span>
                            </span>
                            <span class="w-full truncate px-1 text-center text-xs font-bold text-slate-900">{{ $row['name'] }}</span>
                            @if($isMe)
                                <span class="mt-0.5 rounded-full bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Kamu</span>
                            @endif
                            <span class="mb-2 text-[11px] font-extrabold text-indigo-500">{{ $row['points'] }} poin</span>
                            <div class="rank-bar rank-bar-{{ $place }} flex w-full items-start justify-center rounded-t-lg pt-2 text-lg font-black text-white/90">{{ $place }}</div>
                        </li>
                    @endforeach
                </ol>
                @if($rest->isNotEmpty())
                    <ol class="border-t border-slate-100">
                        @foreach($rest as $index => $row)
                            @php $isMe = (int) ($row['id'] ?? 0) === (int) $user->id; @endphp
                            <li class="flex items-center gap-3 border-b border-slate-100 px-4 py-2.5 last:border-b-0 {{ $isMe ? 'bg-emerald-50' : '' }}">
                                <span class="w-5 shrink-0 text-center text-[13px] font-extrabold text-slate-400">{{ $index + 1 }}</span>
                                @if($row['avatar_url'])
                                    <img src="{{ $row['avatar_url'] }}" alt="" class="h-7 w-7 shrink-0 rounded-full object-cover">
                                @else
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-extrabold text-white" style="background: {{ $row['color'] }}">{{ $row['initials'] }}</span>
                                @endif
                                <span class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-700">
                                    {{ $row['name'] }}
                                    @if($isMe)
                                        <span class="ml-1 rounded-full bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Kamu</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-[13px] font-extrabold tabular-nums text-slate-900">{{ $row['points'] }} poin</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @elseif($mine)
            <section class="mb-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                <h2 class="text-sm font-bold text-slate-900">Ranking achievement</h2>
                <p class="text-[11px] text-slate-500">Peringkat kamu.</p>
                <div class="mt-3 flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-400 text-sm font-black text-amber-950">{{ $mine['place'] }}</span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-sm font-bold text-slate-900">{{ $mine['name'] }}</span>
                            <span class="rounded-full bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Kamu</span>
                        </div>
                        <span class="text-[11px] text-slate-400">{{ $mine['tickets'] }} tiket</span>
                    </div>
                    <span class="ml-auto shrink-0 text-sm font-extrabold text-indigo-500">{{ $mine['points'] }} poin</span>
                </div>
            </section>
        @endif
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
                            @if($ticket->achievement_points && (($canSeeFullRanking ?? false) || (int) $ticket->assigned_user_id === (int) $user->id))
                                · {{ $ticket->achievement_points }} poin
                            @endif
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
