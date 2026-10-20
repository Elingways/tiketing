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
    $isWorkDesk = ($canDirect ?? false) && $roleStaff->isNotEmpty();
    $approveOnly = (bool) $nextSuperiorRole
        && ! $isWorkDesk
        && ! $user->isSuperuser()
        && ! $ticket->currentRole?->head_user_id;
    $showActionPanel = $canHandle && (
        $user->isSuperuser()
        || ($canDirect ?? false)
        || ! $ticket->currentRole?->head_user_id
    );
@endphp

<div class="{{ $showActionPanel ? 'grid grid-cols-1 lg:grid-cols-12 gap-6' : 'max-w-3xl mx-auto space-y-5' }}">
    <!-- Kolom Utama: Detail Tiket + Riwayat Diskusi -->
    <div class="{{ $showActionPanel ? 'lg:col-span-7' : '' }} space-y-5">

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

                <!-- Dokumen Lampiran dengan Tombol Preview Modal & Unduh -->
                @if($ticket->attachment_path)
                    <div class="mt-4 p-3 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-sm">📎</span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ $ticket->attachment_name ?? 'Dokumen Lampiran' }}</p>
                                <p class="text-[10px] text-slate-400">Lampiran Pengajuan Tiket</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button"
                                    @click="openDocPreview('{{ route('tickets.preview', $ticket) }}', '{{ route('tickets.attachment', $ticket) }}', '{{ addslashes($ticket->attachment_name ?? 'Dokumen Lampiran') }}')"
                                    class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition">
                                Preview Dokumen
                            </button>
                            <a href="{{ route('tickets.attachment', $ticket) }}"
                               class="px-3 py-1.5 rounded-lg bg-white hover:bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700 transition">
                                Unduh
                            </a>
                        </div>
                    </div>
                @endif

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

            <!-- Footer Stepper Alur Hirarki -->
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

        @if($ticket->canBeRevisedBy($user))
            <div id="revisi" class="bg-white rounded-xl border border-amber-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-amber-100">
                    <h2 class="text-sm font-bold text-slate-900">Edit tiket selesai</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Gunakan ini jika hasil pengerjaan belum sesuai. Tiket kembali ke staff yang ditugaskan.</p>
                </div>
                <form action="{{ route('tickets.reopen', $ticket) }}" method="POST" class="px-6 py-4 space-y-3">
                    @csrf
                    <textarea name="note" rows="3" required minlength="3" placeholder="Tuliskan bagian yang belum sesuai..."
                              class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">{{ old('note') }}</textarea>
                    <button type="submit" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold">Edit dan kembalikan</button>
                </form>
            </div>
        @endif

        @php
            $problemTotal = $ticket->problems->count();
            $problemDone = $ticket->problems->where('is_done', true)->count();
            $problemPercent = $ticket->progressPercent();
        @endphp
        @if(!($approveOnly && $problemTotal === 0 && $problemPercent === null))
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            @php
                $canEditProblems = $canDirect ?? $ticket->canBeDirectedBy($user);
                $canToggleProblems = $ticket->canUpdateProblemProgress($user);
                $canReportProgress = $ticket->canReportProgress($user);
            @endphp
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Masalah &amp; Progres</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Kepala menetapkan masalah. Staff mengisi sendiri sudah berapa persen yang dikerjakan.</p>
                </div>
                <span class="text-lg font-bold text-slate-900">{{ $problemPercent === null ? '—' : $problemPercent . '%' }}</span>
            </div>
            <div class="px-6 py-4">
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: {{ $problemPercent ?? 0 }}%"></div>
                </div>
                <p class="mt-2 text-[11px] text-slate-500">
                    @if($problemPercent === null)
                        Staff belum mengisi persentase pengerjaan.
                    @else
                        Staff melaporkan {{ $problemPercent }}% selesai
                        @if($ticket->assignedUser) · {{ $ticket->assignedUser->name }} @endif
                    @endif
                    @if($problemTotal > 0)
                        · {{ $problemDone }} dari {{ $problemTotal }} masalah dicentang
                    @endif
                </p>

                @if($ticket->progress_note)
                    <p class="mt-2 text-xs text-slate-700">{{ $ticket->progress_note }}</p>
                @endif

                @if($ticket->workStatusLabel())
                    <p class="mt-2 text-xs font-semibold {{ $ticket->work_status === 'blocked' ? 'text-rose-700' : 'text-slate-800' }}">
                        {{ $ticket->workStatusLabel() }}
                        @if($ticket->work_status === 'blocked')
                            <span class="font-normal text-rose-600">· ada masalah saat pengerjaan</span>
                        @endif
                    </p>
                @endif

                @if($canReportProgress)
                    <form action="{{ route('tickets.progress', $ticket) }}" method="POST" class="mt-3 space-y-2">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Sudah dikerjakan (%)</label>
                            <input type="number" name="progress_percent" min="0" max="100" required
                                   value="{{ old('progress_percent', $ticket->progress_percent) }}"
                                   class="w-28 px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-700 mb-1">Status pengerjaan</p>
                            <div class="space-y-1.5">
                                @foreach(['delivered' => 'Delivered', 'in_progress' => 'In progress', 'blocked' => 'Blocked', 'finished' => 'Finished'] as $value => $label)
                                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs">
                                        <input type="radio" name="work_status" value="{{ $value }}" class="mt-0.5"
                                               {{ old('work_status', $ticket->work_status) === $value ? 'checked' : '' }}>
                                        <span>
                                            <span class="font-medium text-slate-900">{{ $label }}</span>
                                            @if($value === 'blocked')
                                                <span class="block text-[11px] text-rose-600">Ada masalah saat pengerjaan. Catatan wajib diisi.</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan</label>
                            <textarea name="note" rows="2" maxlength="500"
                                      placeholder="Kalau Blocked, tuliskan masalah yang menghambat. Selain itu catatan boleh dikosongkan."
                                      class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">{{ old('note', $ticket->progress_note) }}</textarea>
                        </div>
                        <button type="submit" class="px-3 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold">Simpan Progres</button>
                    </form>
                @endif

                <div class="mt-4 space-y-2">
                    @foreach($ticket->problems as $problem)
                        <div class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2">
                            @if($canToggleProblems)
                                <form action="{{ route('tickets.problems.toggle', [$ticket, $problem]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="mt-0.5 h-4 w-4 rounded border {{ $problem->is_done ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 bg-white text-transparent' }} text-[10px] leading-4">✓</button>
                                </form>
                            @else
                                <span class="mt-0.5 h-4 w-4 rounded border text-center text-[10px] leading-4 {{ $problem->is_done ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 text-transparent' }}">✓</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-xs {{ $problem->is_done ? 'text-slate-400 line-through' : 'text-slate-800' }}">{{ $problem->title }}</p>
                                @if($problem->is_done)
                                    <p class="text-[11px] text-slate-400">Selesai oleh {{ $problem->doneBy?->name ?? 'staff' }}</p>
                                @endif
                            </div>
                            @if($canEditProblems)
                                <form action="{{ route('tickets.problems.destroy', [$ticket, $problem]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] text-rose-600 hover:underline">Hapus</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($canEditProblems)
                    <form action="{{ route('tickets.problems.store', $ticket) }}" method="POST" class="mt-4 space-y-2">
                        @csrf
                        <label class="block text-xs font-semibold text-slate-700">Tetapkan masalah</label>
                        <textarea name="title" rows="3" required placeholder="Satu masalah per baris. Contoh:&#10;Login SIAKAD gagal&#10;Nilai tidak muncul"
                                  class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900"></textarea>
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold">Simpan Masalah</button>
                    </form>
                @endif
            </div>
        </div>
        @endif

        <!-- Riwayat & Diskusi -->
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

    <!-- Kolom Kanan: persetujuan singkat, atau keputusan kepala untuk role pengerja. -->
    @if($showActionPanel && $approveOnly)
        <div class="lg:col-span-5">
            <div class="bg-white rounded-xl p-6 border border-slate-200 sticky top-20">
                <h2 class="text-sm font-bold text-slate-900">Persetujuan {{ $ticket->currentRole?->name }}</h2>
                <p class="text-xs text-slate-500 mt-0.5 mb-4">Klik setuju untuk meneruskan tiket ke {{ $nextSuperiorRole->name }}.</p>

                <form action="{{ route('tickets.action', $ticket) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan (opsional)</label>
                        <textarea name="note" rows="3" maxlength="1000" placeholder="Tambahkan catatan bila perlu..."
                                  class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900"></textarea>
                    </div>
                    <button type="submit" name="action_type" value="approve_step"
                            class="w-full py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition">
                        Setuju, lanjut ke {{ $nextSuperiorRole->name }}
                    </button>
                    <button type="submit" name="action_type" value="reject"
                            class="w-full py-2 rounded-lg border border-rose-200 text-rose-700 text-xs font-semibold hover:bg-rose-50">
                        Tolak
                    </button>
                    <p class="text-[11px] text-slate-400">Tolak wajib diisi catatannya.</p>
                </form>
            </div>
        </div>
    @elseif($showActionPanel)
        <div class="lg:col-span-5">
            <div class="bg-white rounded-xl p-6 border border-slate-200 sticky top-20" x-data="{ actionType: '{{ $isWorkDesk ? 'in_progress' : $defaultAction }}' }">
                <h2 class="text-sm font-bold text-slate-900">Tindak Lanjut Role {{ $ticket->currentRole?->name }}</h2>
                <p class="text-xs text-slate-500 mt-0.5 mb-4">
                    @if($canDirect && $roleStaff->isNotEmpty())
                        Cek apakah isi permintaan sesuai level yang dipilih pemohon. Kalau sesuai, proses dan bagikan ke staff. Kalau tidak, tolak.
                    @else
                        Tetapkan prioritas dan pilih tindakan hirarki.
                    @endif
                </p>

                <form action="{{ route('tickets.action', $ticket) }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">1. Prioritas pemohon</label>
                        @if($canDirect && $roleStaff->isNotEmpty())
                            <p class="text-xs font-semibold text-slate-800">{{ $ticket->priority_rank ? $ticket->priorityLabel() : 'Pemohon belum memilih prioritas.' }}</p>
                            @if($ticket->priority_rank)
                                <input type="hidden" name="priority_rank" value="{{ $ticket->priority_rank }}">
                            @endif
                            <div x-show="actionType === 'in_progress'" x-cloak class="mt-3">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">2. Arahkan ke staff</label>
                                <select name="assigned_user_id"
                                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                                    <option value="">Pilih staff sesuai keahlian...</option>
                                    @foreach($roleStaff as $staff)
                                        @continue((int) $staff->id === (int) $ticket->currentRole?->head_user_id)
                                        <option value="{{ $staff->id }}" {{ (int) $ticket->assigned_user_id === (int) $staff->id ? 'selected' : '' }}>
                                            {{ $staff->name }}@if($staff->keahlian) — {{ $staff->keahlian }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">Dipakai hanya jika level pemohon sudah sesuai dengan isi permintaan.</p>
                            </div>
                        @elseif($ticket->currentRole?->head_user_id)
                            <p class="text-xs text-slate-600">Prioritas: {{ $ticket->priorityLabel() }}</p>
                            @if($ticket->assignedUser)
                                <p class="text-[11px] text-slate-500 mt-1">Dikerjakan oleh {{ $ticket->assignedUser->name }}@if($ticket->assignedUser->keahlian) — {{ $ticket->assignedUser->keahlian }}@endif.</p>
                            @endif
                        @else
                            <select name="priority" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                                <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Rendah (Low)</option>
                                <option value="medium" {{ (!$ticket->priority || $ticket->priority === 'medium') ? 'selected' : '' }}>Normal (Medium)</option>
                                <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>Tinggi (High)</option>
                                <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Mendesak (Urgent)</option>
                            </select>
                        @endif
                    </div>

                    <div class="space-y-2 text-xs">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">{{ $roleStaff->isNotEmpty() ? '3. Pilih Tindakan' : '2. Pilih Tindakan' }}</label>

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
                            <div>
                                <span class="font-medium text-slate-900 block">{{ ($canDirect ?? false) && $roleStaff->isNotEmpty() ? 'Sesuai, proses dan bagikan ke staff' : 'Tandai Sedang Diproses' }}</span>
                                @if(($canDirect ?? false) && $roleStaff->isNotEmpty())
                                    <span class="text-[11px] text-slate-400">Level pemohon cocok dengan isi permintaan</span>
                                @endif
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 p-2.5 rounded-lg border cursor-pointer"
                               :class="actionType === 'reject' ? 'bg-rose-50 border-rose-500' : 'border-slate-200'">
                            <input type="radio" name="action_type" value="reject" x-model="actionType" class="mt-0.5">
                            <div>
                                <span class="font-medium text-rose-700 block">{{ ($canDirect ?? false) && $roleStaff->isNotEmpty() ? 'Tidak sesuai, tolak' : 'Tolak Tiket' }}</span>
                                @if(($canDirect ?? false) && $roleStaff->isNotEmpty())
                                    <span class="text-[11px] text-rose-400">Isi permintaan tidak cocok dengan level yang dipilih pemohon</span>
                                @endif
                            </div>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">3. Catatan Keputusan</label>
                        <textarea name="note" rows="3" required
                                  :placeholder="actionType === 'reject' ? 'Jelaskan kenapa level pemohon tidak sesuai...' : 'Tulis catatan tindak lanjut...'"
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
