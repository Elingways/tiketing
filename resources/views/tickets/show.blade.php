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
    $holderUser = \App\Models\User::where('role_id', $ticket->current_role_id)->first();
@endphp

<div class="{{ $canHandle ? 'grid grid-cols-1 lg:grid-cols-12 gap-6' : 'max-w-3xl mx-auto space-y-5' }}">
    <!-- Kolom Utama: Detail Tiket + Riwayat Diskusi -->
    <div class="{{ $canHandle ? 'lg:col-span-7' : '' }} space-y-5">

        <!-- Jika sedang dilihat oleh Role non-penanggung jawab (misal Mahasiswa), tampilkan bar info posisi yang ringkas -->
        @if(!$canHandle && !in_array($ticket->status, ['resolved', 'rejected', 'closed'], true))
            <div class="bg-white rounded-xl px-4 py-3 border border-slate-200 flex items-center justify-between gap-3">
                <p class="text-xs text-slate-600">
                    Posisi tiket saat ini berada di <strong>Role {{ $ticket->currentRole?->name }}</strong>.
                </p>
                @if($holderUser)
                    <form action="{{ route('auth.quick-switch', $holderUser) }}" method="POST">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-indigo-600 hover:underline shrink-0">
                            Beralih ke {{ $ticket->currentRole?->name }} →
                        </button>
                    </form>
                @endif
            </div>
        @endif

        <!-- Kartu Utama Tiket -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <!-- Header Tiket -->
            <div class="p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs font-semibold text-indigo-600">#{{ $ticket->ticket_number }}</span>
                        <span class="text-slate-300">•</span>
                        <span class="text-xs text-slate-400">{{ $ticket->created_at->format('d M Y, H:i') }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[11px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">
                            Prioritas: {{ $ticket->priorityLabel() }}
                        </span>
                        <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md border {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
                            {{ $ticket->statusLabel() }}
                        </span>
                    </div>
                </div>

                <h1 class="text-base font-bold text-slate-900">{{ $ticket->title }}</h1>
                <p class="mt-2.5 text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $ticket->description }}</p>

                <!-- Dokumen Lampiran (Jika Ada) -->
                @if($ticket->attachment_path)
                    <div class="mt-4 p-3 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-sm">📎</span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ $ticket->attachment_name ?? 'Dokumen Lampiran' }}</p>
                                <p class="text-[10px] text-slate-400">Lampiran Pengajuan Tiket</p>
                            </div>
                        </div>
                        <a href="{{ route('tickets.attachment', $ticket) }}"
                           class="px-3 py-1.5 rounded-lg bg-white hover:bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700 shrink-0 transition">
                            Unduh Dokumen
                        </a>
                    </div>
                @endif

                <!-- Metadata Ringkas -->
                <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Pemohon</span>
                        <span class="font-semibold text-slate-800">{{ $ticket->creator?->name }}</span>
                        <span class="text-slate-400 text-[11px] block">{{ $ticket->creatorRole?->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Posisi Hirarki</span>
                        <span class="font-semibold text-slate-800">Role {{ $ticket->currentRole?->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Kategori</span>
                        <span class="font-semibold text-slate-800">{{ $ticket->category?->name }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer Stepper Alur Hirarki (Terintegrasi di dalam Kartu) -->
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center gap-2 flex-wrap text-xs">
                <span class="text-slate-400 font-medium mr-1">Progres Hirarki:</span>
                @foreach($stepper as $idx => $step)
                    @if($idx > 0)
                        <span class="text-slate-300">→</span>
                    @endif
                    <span class="px-2 py-0.5 rounded font-medium {{ $step['state'] === 'active' ? 'bg-slate-900 text-white' : ($step['state'] === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'text-slate-400') }}">
                        {{ $step['role']->name }}
                    </span>
                @endforeach
            </div>
        </div>

        <!-- Riwayat & Diskusi (Clean Divider List) -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Riwayat & Diskusi</h2>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($ticket->logs as $log)
                    @php $badge = $log->actionBadge(); @endphp
                    <div class="px-6 py-3.5 text-xs">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-semibold text-slate-900">{{ $log->user?->name }}</span>
                                <span class="text-slate-400">({{ $log->user?->role?->name }})</span>
                                <span class="text-slate-300">•</span>
                                <span class="font-medium text-indigo-600">{{ $badge['label'] }}</span>
                                @if($log->fromRole && $log->toRole && $log->from_role_id !== $log->to_role_id)
                                    <span class="text-slate-400">({{ $log->fromRole->name }} → {{ $log->toRole->name }})</span>
                                @endif
                            </div>
                            <span class="text-[11px] text-slate-400 shrink-0">{{ $log->created_at->format('d M, H:i') }}</span>
                        </div>
                        <p class="text-slate-600 leading-relaxed">{{ $log->note }}</p>
                    </div>
                @endforeach
            </div>

            <!-- Form Balasan -->
            <form action="{{ route('tickets.comment', $ticket) }}" method="POST" class="p-4 bg-slate-50/60 border-t border-slate-100 flex gap-2">
                @csrf
                <input type="text" name="note" required placeholder="Tulis balasan atau catatan..."
                       class="flex-1 px-3.5 py-2 rounded-lg bg-white border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shrink-0 transition">
                    Kirim
                </button>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan (Hanya muncul bila Role saat ini berwenang memproses tiket) -->
    @if($canHandle)
        <div class="lg:col-span-5">
            <div class="bg-white rounded-xl p-6 border border-slate-200 sticky top-20" x-data="{ actionType: '{{ $defaultAction }}' }">
                <h2 class="text-sm font-bold text-slate-900">Tindak Lanjut Role {{ $ticket->currentRole?->name }}</h2>
                <p class="text-xs text-slate-500 mt-0.5 mb-4">Tetapkan prioritas dan pilih tindakan hirarki.</p>

                <form action="{{ route('tickets.action', $ticket) }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Set Prioritas Tiket di Jenjang Penerima -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">1. Set Prioritas Tiket</label>
                        <select name="priority" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                            <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Rendah (Low)</option>
                            <option value="medium" {{ (!$ticket->priority || $ticket->priority === 'medium') ? 'selected' : '' }}>Normal (Medium)</option>
                            <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>Tinggi (High)</option>
                            <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Mendesak (Urgent)</option>
                        </select>
                    </div>

                    <!-- Pilih Keputusan Hirarki -->
                    <div class="space-y-2 text-xs">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">2. Pilih Tindakan</label>

                        @if($ticket->workflow_type === 'tiered_approval')
                            <label class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer"
                                   :class="actionType === 'approve_step' ? 'bg-slate-50 border-slate-900' : 'border-slate-200'">
                                <input type="radio" name="action_type" value="approve_step" x-model="actionType" class="mt-0.5">
                                <span class="font-medium text-slate-900">
                                    {{ $ticket->isAtFinalApprovalStage() ? 'Setujui Final (Selesai)' : 'Setujui & Teruskan ke ' . $nextSuperiorRole?->name }}
                                </span>
                            </label>
                        @endif

                        @if($nextSuperiorRole)
                            <label class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer"
                                   :class="actionType === 'escalate' ? 'bg-slate-50 border-slate-900' : 'border-slate-200'">
                                <input type="radio" name="action_type" value="escalate" x-model="actionType" class="mt-0.5">
                                <div>
                                    <span class="font-medium text-slate-900 block">Eskalasi ke {{ $nextSuperiorRole->name }}</span>
                                    <span class="text-[11px] text-slate-400">Teruskan ke atasan hirarki</span>
                                </div>
                            </label>
                        @endif

                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer"
                               :class="actionType === 'resolve' ? 'bg-slate-50 border-slate-900' : 'border-slate-200'">
                            <input type="radio" name="action_type" value="resolve" x-model="actionType" class="mt-0.5">
                            <span class="font-medium text-slate-900">Selesaikan Tiket</span>
                        </label>

                        @if($subordinateRoles->count() > 0)
                            <label class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer"
                                   :class="actionType === 'dispose' ? 'bg-slate-50 border-slate-900' : 'border-slate-200'">
                                <input type="radio" name="action_type" value="dispose" x-model="actionType" class="mt-0.5">
                                <div class="w-full">
                                    <span class="font-medium text-slate-900 block">Disposisi ke Bawahan</span>
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

                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer"
                               :class="actionType === 'in_progress' ? 'bg-slate-50 border-slate-900' : 'border-slate-200'">
                            <input type="radio" name="action_type" value="in_progress" x-model="actionType" class="mt-0.5">
                            <span class="font-medium text-slate-900">Tandai Sedang Diproses</span>
                        </label>

                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer"
                               :class="actionType === 'reject' ? 'bg-rose-50 border-rose-500' : 'border-slate-200'">
                            <input type="radio" name="action_type" value="reject" x-model="actionType" class="mt-0.5">
                            <span class="font-medium text-rose-700">Tolak Tiket</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">3. Catatan Keputusan</label>
                        <textarea name="note" rows="3" required
                                  placeholder="Tulis catatan tindak lanjut..."
                                  class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition">
                        Simpan Keputusan
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
