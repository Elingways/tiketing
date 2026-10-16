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
    $defaultAction = $ticket->workflow_type === 'tiered_approval'
        ? 'approve_step'
        : ($nextSuperiorRole ? 'escalate' : 'resolve');
@endphp

<!-- Compact Stepper Hirarki -->
<div class="bg-white rounded-xl p-4 border border-slate-200 mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs font-semibold text-slate-400 mr-1">Jalur Hirarki:</span>
        @foreach($stepper as $idx => $step)
            @if($idx > 0)
                <span class="text-slate-300 text-xs">→</span>
            @endif
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold {{ $step['state'] === 'active' ? 'bg-indigo-600 text-white' : ($step['state'] === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500') }}">
                <span>{{ $step['role']->name }}</span>
                <span class="text-[10px] opacity-75">({{ $step['label'] }})</span>
            </div>
        @endforeach
    </div>

    <span class="text-xs font-semibold px-2.5 py-1 rounded-md border {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
        {{ $ticket->statusLabel() }}
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Kiri: Detail Tiket & Riwayat/Diskusi -->
    <div class="lg:col-span-7 space-y-5">
        <div class="bg-white rounded-xl p-6 border border-slate-200">
            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                <span class="font-mono font-semibold text-indigo-600">#{{ $ticket->ticket_number }}</span>
                <span>{{ $ticket->created_at->format('d M Y, H:i') }}</span>
            </div>

            <h1 class="text-lg font-bold text-slate-900">{{ $ticket->title }}</h1>
            <p class="mt-3 text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $ticket->description }}</p>

            <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-3 gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block">Pemohon</span>
                    <strong class="text-slate-800">{{ $ticket->creator?->name }}</strong>
                    <span class="text-slate-500 block">({{ $ticket->creatorRole?->name }})</span>
                </div>
                <div>
                    <span class="text-slate-400 block">Posisi Saat Ini</span>
                    <strong class="text-indigo-600">Role {{ $ticket->currentRole?->name }}</strong>
                </div>
                <div>
                    <span class="text-slate-400 block">Kategori</span>
                    <strong class="text-slate-800">{{ $ticket->category?->name }}</strong>
                </div>
            </div>
        </div>

        <!-- Riwayat & Diskusi -->
        <div class="bg-white rounded-xl p-6 border border-slate-200">
            <h2 class="text-sm font-bold text-slate-900 mb-4">Riwayat Hirarki & Diskusi</h2>

            <div class="space-y-3">
                @foreach($ticket->logs as $log)
                    @php $badge = $log->actionBadge(); @endphp
                    <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200/80 text-xs">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <strong class="text-slate-900">{{ $log->user?->name }}</strong>
                                <span class="text-slate-400">({{ $log->user?->role?->name }})</span>
                                <span class="px-2 py-0.5 rounded bg-white border border-slate-200 font-semibold text-slate-700">
                                    {{ $badge['label'] }}
                                </span>
                                @if($log->fromRole && $log->toRole && $log->from_role_id !== $log->to_role_id)
                                    <span class="text-indigo-600 font-semibold">
                                        {{ $log->fromRole->name }} → {{ $log->toRole->name }}
                                    </span>
                                @endif
                            </div>
                            <span class="text-[11px] text-slate-400">{{ $log->created_at->format('d M H:i') }}</span>
                        </div>
                        <p class="text-slate-700">{{ $log->note }}</p>
                    </div>
                @endforeach
            </div>

            <form action="{{ route('tickets.comment', $ticket) }}" method="POST" class="mt-4 pt-4 border-t border-slate-100 flex gap-2">
                @csrf
                <input type="text" name="note" required placeholder="Tulis balasan atau catatan..."
                       class="flex-1 px-3.5 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-indigo-600">
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shrink-0">
                    Kirim
                </button>
            </form>
        </div>
    </div>

    <!-- Kanan: Panel Tindakan Hirarki -->
    <div class="lg:col-span-5">
        @if($canHandle)
            <div class="bg-white rounded-xl p-6 border border-slate-200" x-data="{ actionType: '{{ $defaultAction }}' }">
                <h2 class="text-sm font-bold text-slate-900">Tindakan Role {{ $ticket->currentRole?->name }}</h2>
                <p class="text-xs text-slate-500 mb-4">Pilih keputusan untuk tiket ini:</p>

                <form action="{{ route('tickets.action', $ticket) }}" method="POST" class="space-y-3">
                    @csrf

                    <div class="space-y-2 text-xs">
                        @if($ticket->workflow_type === 'tiered_approval')
                            <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer"
                                   :class="actionType === 'approve_step' ? 'bg-indigo-50/60 border-indigo-500' : 'border-slate-200'">
                                <input type="radio" name="action_type" value="approve_step" x-model="actionType" class="mt-0.5">
                                <div>
                                    @if($ticket->isAtFinalApprovalStage())
                                        <span class="font-semibold text-slate-900 block">Setujui Final (Selesai)</span>
                                    @else
                                        <span class="font-semibold text-slate-900 block">Setujui & Teruskan ke {{ $nextSuperiorRole?->name }}</span>
                                    @endif
                                </div>
                            </label>
                        @endif

                        @if($nextSuperiorRole)
                            <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer"
                                   :class="actionType === 'escalate' ? 'bg-indigo-50/60 border-indigo-500' : 'border-slate-200'">
                                <input type="radio" name="action_type" value="escalate" x-model="actionType" class="mt-0.5">
                                <div>
                                    <span class="font-semibold text-slate-900 block">Eskalasi ke Atasan ({{ $nextSuperiorRole->name }})</span>
                                    <span class="text-slate-500">Teruskan dari {{ $ticket->currentRole?->name }} ke {{ $nextSuperiorRole->name }}</span>
                                </div>
                            </label>
                        @endif

                        <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer"
                               :class="actionType === 'resolve' ? 'bg-indigo-50/60 border-indigo-500' : 'border-slate-200'">
                            <input type="radio" name="action_type" value="resolve" x-model="actionType" class="mt-0.5">
                            <span class="font-semibold text-slate-900">Selesaikan Tiket</span>
                        </label>

                        @if($subordinateRoles->count() > 0)
                            <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer"
                                   :class="actionType === 'dispose' ? 'bg-indigo-50/60 border-indigo-500' : 'border-slate-200'">
                                <input type="radio" name="action_type" value="dispose" x-model="actionType" class="mt-0.5">
                                <div class="w-full">
                                    <span class="font-semibold text-slate-900 block">Disposisi ke Bawahan</span>
                                    <div x-show="actionType === 'dispose'" x-cloak class="mt-2">
                                        <select name="target_subordinate_role_id" class="w-full px-2.5 py-1.5 rounded border border-slate-200 text-xs bg-white">
                                            @foreach($subordinateRoles as $sub)
                                                <option value="{{ $sub->id }}">Role {{ $sub->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </label>
                        @endif

                        <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer"
                               :class="actionType === 'in_progress' ? 'bg-indigo-50/60 border-indigo-500' : 'border-slate-200'">
                            <input type="radio" name="action_type" value="in_progress" x-model="actionType" class="mt-0.5">
                            <span class="font-semibold text-slate-900">Tandai Sedang Diproses</span>
                        </label>

                        <label class="flex items-start gap-2.5 p-3 rounded-lg border cursor-pointer"
                               :class="actionType === 'reject' ? 'bg-rose-50/60 border-rose-500' : 'border-slate-200'">
                            <input type="radio" name="action_type" value="reject" x-model="actionType" class="mt-0.5">
                            <span class="font-semibold text-rose-700">Tolak Tiket</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan</label>
                        <textarea name="note" rows="3" required
                                  placeholder="Tulis catatan tindakan..."
                                  class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-indigo-600"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition">
                        Simpan Keputusan
                    </button>
                </form>
            </div>
        @else
            <div class="bg-white rounded-xl p-6 border border-slate-200">
                <h2 class="text-sm font-bold text-slate-900">Info Posisi Hirarki</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Tiket ini sedang berada pada <strong>Role {{ $ticket->currentRole?->name }}</strong>.
                </p>
                @php
                    $holderUser = \App\Models\User::where('role_id', $ticket->current_role_id)->first();
                @endphp
                @if($holderUser && !in_array($ticket->status, ['resolved', 'rejected', 'closed'], true))
                    <form action="{{ route('auth.quick-switch', $holderUser) }}" method="POST" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full py-2 px-3 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                            Pindah ke Role {{ $ticket->currentRole?->name }} ({{ $holderUser->name }}) →
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
