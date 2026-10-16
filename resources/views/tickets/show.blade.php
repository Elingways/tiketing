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

<!-- Visual Stepper Alur Hirarki Tiket -->
<div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs mb-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <div>
            <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-600">Pelacakan Posisi Hirarki Role</span>
            <h2 class="text-base font-extrabold text-slate-900">
                Alur Perjalanan Tiket ({{ $ticket->workflow_type === 'tiered_approval' ? 'Mode Approval Berjenjang Otomatis' : 'Mode Eskalasi & Disposisi Dinamis' }})
            </h2>
        </div>
        <span class="text-xs font-bold px-3 py-1 rounded-full border {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
            Status: {{ $ticket->statusLabel() }}
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-{{ max(count($stepper), 2) }} gap-3">
        @foreach($stepper as $idx => $step)
            @php
                $stateStyle = match($step['state']) {
                    'completed' => 'bg-emerald-50/90 border-emerald-300 text-emerald-900',
                    'active' => 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-500/25 ring-4 ring-indigo-100',
                    'rejected' => 'bg-rose-50 border-rose-300 text-rose-900',
                    default => 'bg-slate-50 border-slate-200 text-slate-500',
                };
            @endphp
            <div class="p-3.5 rounded-2xl border {{ $stateStyle }} flex items-center gap-3 relative">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center font-extrabold text-xs shrink-0 {{ $step['state'] === 'active' ? 'bg-white text-indigo-700' : ($step['state'] === 'completed' ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600') }}">
                    @if($step['state'] === 'completed')
                        ✓
                    @else
                        {{ $idx + 1 }}
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider opacity-80">{{ $step['label'] }}</p>
                    <p class="font-extrabold text-sm truncate">Role {{ $step['role']->name }}</p>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Kolom Kiri (7 Cols): Detail Tiket & Timeline Diskusi/Riwayat Hirarki -->
    <div class="lg:col-span-7 space-y-6">
        <!-- Detail Utama Tiket -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <span class="font-mono text-xs font-extrabold px-3 py-1 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200">
                    #{{ $ticket->ticket_number }}
                </span>
                <span class="text-xs text-slate-400">Dibuat {{ $ticket->created_at->format('d M Y, H:i') }}</span>
            </div>

            <h1 class="text-xl font-extrabold text-slate-900">{{ $ticket->title }}</h1>

            <div class="mt-4 p-4 rounded-2xl bg-slate-50 border border-slate-200/70 text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ $ticket->description }}</div>

            <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                <div>
                    <p class="text-slate-400 font-semibold">Pemohon Tiket</p>
                    <p class="font-bold text-slate-900 mt-0.5">{{ $ticket->creator?->name }}</p>
                    <p class="text-[11px] text-indigo-600 font-semibold">Role: {{ $ticket->creatorRole?->name }}</p>
                </div>
                <div>
                    <p class="text-slate-400 font-semibold">Posisi Hirarki Saat Ini</p>
                    <p class="font-bold text-slate-900 mt-0.5">Role {{ $ticket->currentRole?->name }}</p>
                    @if($nextSuperiorRole)
                        <p class="text-[11px] text-amber-600 font-semibold">Atasan: {{ $nextSuperiorRole->name }}</p>
                    @else
                        <p class="text-[11px] text-emerald-600 font-semibold">Puncak Hirarki</p>
                    @endif
                </div>
                <div>
                    <p class="text-slate-400 font-semibold">Kategori Layanan</p>
                    <p class="font-bold text-slate-900 mt-0.5">{{ $ticket->category?->name }}</p>
                    <p class="text-[11px] text-slate-500">Prioritas: {{ $ticket->priorityLabel() }}</p>
                </div>
            </div>
        </div>

        <!-- Timeline Riwayat Perpindahan Hirarki & Diskusi -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-extrabold text-slate-900 mb-1">Timeline Riwayat Hirarki & Ruang Diskusi</h2>
            <p class="text-xs text-slate-500 mb-6">Catatan lengkap perpindahan tiket antar role (Mahasiswa → Dosen → Kaprodi → Dekan) dan percakapan.</p>

            <div class="space-y-4 relative before:absolute before:top-3 before:bottom-3 before:left-4 before:w-0.5 before:bg-slate-200">
                @foreach($ticket->logs as $log)
                    @php
                        $badge = $log->actionBadge();
                        $badgeStyles = [
                            'indigo' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                            'sky' => 'bg-sky-100 text-sky-800 border-sky-200',
                            'amber' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'violet' => 'bg-violet-100 text-violet-800 border-violet-200',
                            'emerald' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'rose' => 'bg-rose-100 text-rose-800 border-rose-200',
                            'slate' => 'bg-slate-100 text-slate-700 border-slate-200',
                        ];
                    @endphp
                    <div class="relative pl-10">
                        <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center text-xs font-bold absolute left-0 top-0 shadow-xs">
                            {{ $log->user?->initials() ?? 'S' }}
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-bold text-xs text-slate-900">{{ $log->user?->name }}</span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-200 text-slate-700">
                                        {{ $log->user?->role?->name }}
                                    </span>
                                    <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border {{ $badgeStyles[$badge['color']] ?? $badgeStyles['slate'] }}">
                                        {{ $badge['label'] }}
                                    </span>
                                    @if($log->fromRole && $log->toRole && $log->from_role_id !== $log->to_role_id)
                                        <span class="text-[11px] font-bold text-indigo-600">
                                            ({{ $log->fromRole->name }} → {{ $log->toRole->name }})
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-400">{{ $log->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <p class="text-xs text-slate-700 leading-relaxed">{{ $log->note }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Form Tambah Komentar / Diskusi -->
            <form action="{{ route('tickets.comment', $ticket) }}" method="POST" class="mt-6 pt-5 border-t border-slate-100">
                @csrf
                <label class="block text-xs font-bold text-slate-700 mb-2">Kirim Balasan / Catatan Diskusi sebagai {{ $user->name }} ({{ $user->role?->name }})</label>
                <div class="flex gap-2">
                    <input type="text" name="note" required placeholder="Ketik pesan atau instruksi tambahan pada tiket ini..."
                           class="flex-1 px-4 py-2.5 rounded-2xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-500">
                    <button type="submit" class="px-5 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shrink-0 transition">
                        Kirim Pesan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan (5 Cols): Panel Eksekusi Hirarki (Eskalasi / Approval Berjenjang / Disposisi / Resolve) -->
    <div class="lg:col-span-5 space-y-6">
        @if($canHandle)
            <div class="bg-white rounded-3xl p-6 border-2 border-indigo-500/80 shadow-lg shadow-indigo-500/10"
                 x-data="{ actionType: '{{ $defaultAction }}' }">
                <div class="flex items-center gap-2.5 mb-2">
                    <span class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center">
                        @include('partials.icon', ['name' => 'share', 'class' => 'w-5 h-5'])
                    </span>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Panel Keputusan Hirarki</h3>
                        <p class="text-xs text-slate-500">Wewenang Saat Ini: <strong>Role {{ $ticket->currentRole?->name }}</strong></p>
                    </div>
                </div>

                <form action="{{ route('tickets.action', $ticket) }}" method="POST" class="mt-4 space-y-4">
                    @csrf

                    <div class="space-y-2">
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500">Pilih Tindakan Hirarki:</label>

                        @if($ticket->workflow_type === 'tiered_approval')
                            <label @click="actionType = 'approve_step'"
                                   class="cursor-pointer flex items-start gap-3 p-3.5 rounded-2xl border transition"
                                   :class="actionType === 'approve_step' ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-200' : 'border-slate-200 hover:bg-slate-50'">
                                <input type="radio" name="action_type" value="approve_step" x-model="actionType" class="mt-1 text-emerald-600">
                                <div>
                                    @if($ticket->isAtFinalApprovalStage())
                                        <p class="text-xs font-extrabold text-emerald-900">✓ Setujui Final (Selesaikan Approval Berjenjang)</p>
                                        <p class="text-[11px] text-emerald-700">Role {{ $ticket->currentRole?->name }} adalah tahap akhir. Tiket akan disahkan & selesai.</p>
                                    @else
                                        <p class="text-xs font-extrabold text-emerald-900">
                                            ✓ Setujui Tahap {{ $ticket->approval_step }} & Teruskan ke {{ $nextSuperiorRole?->name }}
                                        </p>
                                        <p class="text-[11px] text-emerald-700">
                                            Setujui di tingkat {{ $ticket->currentRole?->name }} dan naikkan otomatis ke atasan hirarki ({{ $nextSuperiorRole?->name }}).
                                        </p>
                                    @endif
                                </div>
                            </label>
                        @endif

                        @if($nextSuperiorRole)
                            <label @click="actionType = 'escalate'"
                                   class="cursor-pointer flex items-start gap-3 p-3.5 rounded-2xl border transition"
                                   :class="actionType === 'escalate' ? 'bg-amber-50 border-amber-500 ring-2 ring-amber-200' : 'border-slate-200 hover:bg-slate-50'">
                                <input type="radio" name="action_type" value="escalate" x-model="actionType" class="mt-1 text-amber-600">
                                <div>
                                    <p class="text-xs font-extrabold text-amber-900">
                                        ↑ Eskalasi ke Atasan Hirarki: Role {{ $nextSuperiorRole->name }}
                                    </p>
                                    <p class="text-[11px] text-amber-700">
                                        Teruskan tiket dari {{ $ticket->currentRole?->name }} ke {{ $nextSuperiorRole->name }} untuk keputusan lebih tinggi.
                                    </p>
                                </div>
                            </label>
                        @endif

                        <label @click="actionType = 'resolve'"
                               class="cursor-pointer flex items-start gap-3 p-3.5 rounded-2xl border transition"
                               :class="actionType === 'resolve' ? 'bg-indigo-50 border-indigo-500 ring-2 ring-indigo-200' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" name="action_type" value="resolve" x-model="actionType" class="mt-1 text-indigo-600">
                            <div>
                                <p class="text-xs font-extrabold text-indigo-900">✓ Selesaikan Tiket pada Level {{ $ticket->currentRole?->name }}</p>
                                <p class="text-[11px] text-indigo-700">Tandai permohonan/kendala telah dituntaskan tanpa perlu eskalasi lebih lanjut.</p>
                            </div>
                        </label>

                        @if($subordinateRoles->count() > 0)
                            <label @click="actionType = 'dispose'"
                                   class="cursor-pointer flex items-start gap-3 p-3.5 rounded-2xl border transition"
                                   :class="actionType === 'dispose' ? 'bg-violet-50 border-violet-500 ring-2 ring-violet-200' : 'border-slate-200 hover:bg-slate-50'">
                                <input type="radio" name="action_type" value="dispose" x-model="actionType" class="mt-1 text-violet-600">
                                <div class="w-full">
                                    <p class="text-xs font-extrabold text-violet-900">↓ Disposisi / Kembalikan ke Role Bawahan</p>
                                    <p class="text-[11px] text-violet-700 mb-2">Delegasikan tindak lanjut teknis ke role di bawah {{ $ticket->currentRole?->name }}.</p>
                                    <div x-show="actionType === 'dispose'" x-cloak>
                                        <select name="target_subordinate_role_id" class="w-full px-3 py-1.5 rounded-xl border border-violet-300 text-xs bg-white">
                                            @foreach($subordinateRoles as $sub)
                                                <option value="{{ $sub->id }}">Role Bawahan: {{ $sub->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </label>
                        @endif

                        <label @click="actionType = 'in_progress'"
                               class="cursor-pointer flex items-start gap-3 p-3 rounded-2xl border transition"
                               :class="actionType === 'in_progress' ? 'bg-sky-50 border-sky-500' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" name="action_type" value="in_progress" x-model="actionType" class="mt-0.5 text-sky-600">
                            <div>
                                <p class="text-xs font-bold text-slate-800">Tandai Sedang Diproses oleh {{ $ticket->currentRole?->name }}</p>
                            </div>
                        </label>

                        <label @click="actionType = 'reject'"
                               class="cursor-pointer flex items-start gap-3 p-3 rounded-2xl border transition"
                               :class="actionType === 'reject' ? 'bg-rose-50 border-rose-500' : 'border-slate-200 hover:bg-slate-50'">
                            <input type="radio" name="action_type" value="reject" x-model="actionType" class="mt-0.5 text-rose-600">
                            <div>
                                <p class="text-xs font-bold text-rose-700">✕ Tolak Permohonan Tiket</p>
                            </div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Keputusan / Alasan Eskalasi / Disposisi</label>
                        <textarea name="note" rows="3" required
                                  placeholder="Tuliskan catatan resmi dari Role {{ $ticket->currentRole?->name }}..."
                                  class="w-full px-4 py-2.5 rounded-2xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-500"></textarea>
                    </div>

                    <button type="submit"
                            class="w-full py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-extrabold text-xs shadow-lg shadow-indigo-500/25 transition">
                        Konfirmasi & Jalankan Keputusan Hirarki →
                    </button>
                </form>
            </div>
        @else
            <!-- Info Box bila sedang login dengan Role lain, lengkap dengan tombol 1-Klik Simulasi Pindah ke Role Pemegang Tiket -->
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
                <h3 class="text-sm font-extrabold text-slate-900">Status Wewenang Hirarki</h3>
                @if(in_array($ticket->status, ['resolved', 'rejected', 'closed'], true))
                    <p class="text-xs text-slate-500 mt-1">
                        Tiket ini telah berstatus <strong>{{ $ticket->statusLabel() }}</strong> dan proses hirarkinya telah selesai.
                    </p>
                @else
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Saat ini tiket sedang berada di meja <strong>Role {{ $ticket->currentRole?->name }}</strong>, sedangkan Anda sedang masuk sebagai <strong>Role {{ $user->role?->name }}</strong>.
                    </p>
                    @php
                        $holderUser = \App\Models\User::where('role_id', $ticket->current_role_id)->first();
                    @endphp
                    @if($holderUser)
                        <div class="mt-4 p-4 rounded-2xl bg-indigo-50 border border-indigo-200">
                            <p class="text-xs font-bold text-indigo-950 mb-2">
                                Ingin langsung memproses tiket ini sebagai {{ $ticket->currentRole?->name }}?
                            </p>
                            <form action="{{ route('auth.quick-switch', $holderUser) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-extrabold transition shadow-sm">
                                    Simulasi Pindah ke Role {{ $ticket->currentRole?->name }} ({{ $holderUser->name }}) →
                                </button>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
