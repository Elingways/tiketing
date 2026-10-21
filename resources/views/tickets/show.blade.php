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
                        <span class="text-xs text-slate-500">Request date {{ $ticket->created_at->format('d M Y, H:i') }}</span>
                        <span class="text-slate-300">•</span>
                        <span class="text-xs text-slate-500">Delivered date {{ $ticket->delivered_at ? $ticket->delivered_at->format('d M Y, H:i') : 'Belum diarahkan' }}</span>
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

                <!-- Dokumen / Gambar Lampiran dengan Preview Inline, Modal & Unduh -->
                @if($ticket->attachment_path)
                    <div class="mt-4 p-3 rounded-lg bg-slate-50 border border-slate-200/80 space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-sm">{{ $ticket->isImageAttachment() ? '🖼️' : '📎' }}</span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-800 truncate">{{ $ticket->attachment_name ?? 'Lampiran Bukti / Dokumen' }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $ticket->isImageAttachment() ? 'Gambar Bukti Kendala / Request' : 'Lampiran Pengajuan Tiket' }}</p>
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
                        @if($ticket->isImageAttachment())
                            <div class="rounded-lg overflow-hidden border border-slate-200 bg-white p-2">
                                <img src="{{ route('tickets.preview', $ticket) }}"
                                     alt="{{ $ticket->attachment_name ?? 'Gambar Lampiran Tiket' }}"
                                     class="max-h-72 w-auto mx-auto rounded object-contain">
                            </div>
                        @endif
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
                    <div class="mt-2">
                        @include('tickets.partials.dates', ['ticket' => $ticket])
                    </div>
                </div>
                <span class="text-lg font-bold text-slate-900">{{ $problemPercent === null ? '—' : $problemPercent . '%' }}</span>
            </div>
            <div class="px-6 py-4">
                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: {{ $problemPercent ?? 0 }}%"></div>
                </div>
                <p class="mt-2 text-[11px] text-slate-500">
                    @if($ticket->hasWeightedProblems())
                        Progres {{ $problemPercent ?? 0 }}% dihitung dari poin pekerjaan yang dicentang
                        @if($ticket->assignedUser) · {{ $ticket->assignedUser->name }} @endif
                    @elseif($problemPercent === null)
                        Kepala belum memecah tiket ini. Persen belum bisa diisi perkiraan.
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
                        @if(!$ticket->hasWeightedProblems())
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Sudah dikerjakan (%)</label>
                                <input type="number" name="progress_percent" min="0" max="100" required
                                       value="{{ old('progress_percent', $ticket->progress_percent) }}"
                                       class="w-28 px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                            </div>
                        @else
                            <p class="text-[11px] text-slate-500">Centang poin di bawah. Persen bertambah sesuai bobot poin, bukan perkiraan.</p>
                        @endif
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

                @php
                    $totalWeightConfigured = (int) $ticket->problems->sum('weight_percent');
                    $doneWeightConfigured = (int) $ticket->problems->where('is_done', true)->sum('weight_percent');
                @endphp
                <div class="mt-4 space-y-2">
                    @foreach($ticket->problems as $problem)
                        <div class="flex items-center justify-between gap-2.5 rounded-lg border {{ $problem->is_done ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white' }} px-3 py-2.5">
                            <div class="flex items-start gap-2.5 min-w-0 flex-1">
                                @if($canToggleProblems)
                                    <form action="{{ route('tickets.problems.toggle', [$ticket, $problem]) }}" method="POST">
                                        @csrf
                                        <button type="submit" title="Klik untuk menandai selesai / batal selesai"
                                                class="mt-0.5 h-4 w-4 rounded border {{ $problem->is_done ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 bg-white text-transparent hover:border-indigo-500' }} text-[10px] leading-4 cursor-pointer transition">✓</button>
                                    </form>
                                @else
                                    <span class="mt-0.5 h-4 w-4 rounded border text-center text-[10px] leading-4 {{ $problem->is_done ? 'bg-emerald-600 border-emerald-600 text-white' : 'border-slate-300 text-transparent' }}">✓</span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium {{ $problem->is_done ? 'text-slate-400 line-through' : 'text-slate-800' }}">{{ $problem->title }}</p>
                                    @if($problem->is_done)
                                        <p class="text-[11px] text-emerald-700 mt-0.5">Selesai oleh {{ $problem->doneBy?->name ?? 'staff' }}@if($problem->done_at) · {{ $problem->done_at->format('d M H:i') }}@endif</p>
                                    @else
                                        <p class="text-[10px] text-slate-400 mt-0.5">Centang saat poin pekerjaan ini selesai dikerjakan</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if($problem->weight_percent > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $problem->is_done ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                                        +{{ $problem->weight_percent }}%
                                    </span>
                                @endif
                                @if($canEditProblems)
                                    <form action="{{ route('tickets.problems.destroy', [$ticket, $problem]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[11px] text-rose-600 hover:underline">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($totalWeightConfigured > 0)
                    <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-500 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200/70">
                        <span>Total bobot poin pekerjaan dari Supervisor: <strong>{{ $totalWeightConfigured }}%</strong></span>
                        <span>Tercapai dari centang Staff: <strong class="text-emerald-700">{{ $doneWeightConfigured }}%</strong></span>
                    </div>
                @endif

                @if($canEditProblems)
                    <form action="{{ route('tickets.problems.store', $ticket) }}" method="POST"
                          class="mt-4 pt-4 border-t border-slate-100 space-y-2.5"
                          x-data="{
                              existingWeight: {{ $totalWeightConfigured }},
                              rows: [
                                  { title: '', weight_percent: {{ max(0, min(100, 100 - $totalWeightConfigured)) ?: 30 }} }
                              ],
                              addRow() {
                                  this.rows.push({ title: '', weight_percent: 20 });
                              },
                              removeRow(idx) {
                                  if (this.rows.length > 1) this.rows.splice(idx, 1);
                              },
                              distributeEvenly() {
                                  const remaining = Math.max(0, 100 - this.existingWeight);
                                  const target = remaining > 0 ? remaining : 100;
                                  const count = this.rows.length;
                                  const base = Math.floor(target / count);
                                  let rem = target - (base * count);
                                  this.rows.forEach((r, i) => {
                                      r.weight_percent = base + (i < rem ? 1 : 0);
                                  });
                              },
                              newWeightSum() {
                                  return this.rows.reduce((sum, r) => sum + (parseInt(r.weight_percent, 10) || 0), 0);
                              }
                          }">
                        @csrf
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div>
                                <label class="block text-xs font-semibold text-slate-800">Tetapkan masalah / Poin Detail Pekerjaan &amp; Bobot (%)</label>
                                <p class="text-[11px] text-slate-500">Setiap poin yang dicentang selesai oleh staff akan menambah progres % otomatis sesuai bobotnya.</p>
                            </div>
                            <button type="button" @click="distributeEvenly()"
                                    class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-semibold transition">
                                Bagi Rata % Otomatis
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(row, idx) in rows" :key="idx">
                                <div class="flex items-center gap-2">
                                    <input type="text"
                                           :name="'items[' + idx + '][title]'"
                                           x-model="row.title"
                                           required
                                           placeholder="Contoh: Pengecekan switch & kabel jaringan ruang lab..."
                                           class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                                    <div class="relative w-24 shrink-0">
                                        <input type="number"
                                               :name="'items[' + idx + '][weight_percent]'"
                                               x-model="row.weight_percent"
                                               min="1" max="100" required
                                               class="w-full pl-2.5 pr-6 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-800 focus:outline-none focus:border-slate-900">
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">%</span>
                                    </div>
                                    <button type="button" @click="removeRow(idx)" x-show="rows.length > 1"
                                            class="px-2 py-2 rounded-lg border border-slate-200 text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs">
                                        ✕
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-1 flex-wrap">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="addRow()"
                                        class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold">
                                    + Tambah Poin Pekerjaan
                                </button>
                                <span class="text-[11px] text-slate-500">
                                    Total Bobot: <strong :class="(existingWeight + newWeightSum()) === 100 ? 'text-emerald-600' : 'text-amber-600'" x-text="(existingWeight + newWeightSum()) + '%'"></strong>
                                </span>
                            </div>
                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                                Simpan Masalah &amp; Bobot %
                            </button>
                        </div>
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
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">1. Set Prioritas Tiket</label>
                        @if($canDirect && $roleStaff->isNotEmpty())
                            <select name="priority_rank"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white font-semibold text-slate-800 focus:outline-none focus:border-slate-900">
                                @for($level = 1; $level <= 4; $level++)
                                    <option value="{{ $level }}" {{ (int) ($ticket->priority_rank ?: 2) === $level ? 'selected' : '' }}>
                                        {{ \App\Models\RolePriorityRoute::labelFor($level) }}
                                    </option>
                                @endfor
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Usulan pemohon: <strong>{{ $ticket->priority_rank ? $ticket->priorityLabel() : 'Belum dipilih' }}</strong>. Supervisor dapat menyesuaikan level prioritas.
                            </p>
                            <div x-show="actionType === 'in_progress'" x-cloak class="mt-3 space-y-3"
                                 x-data="{
                                     deliveryPoints: [],
                                     addDeliveryPoint() {
                                         this.deliveryPoints.push({ title: '', weight_percent: 50 });
                                     },
                                     removeDeliveryPoint(i) {
                                         this.deliveryPoints.splice(i, 1);
                                     }
                                 }">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">2. Arahkan ke staff (Delivery Tugas)</label>
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
                                    <p class="text-[11px] text-slate-400 mt-1">Pilih staff pelaksana yang akan mengerjakan tiket ini.</p>
                                </div>

                                <div class="p-3 rounded-lg bg-slate-50 border border-slate-200/80 space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] font-semibold text-slate-700">Tambah Poin Pekerjaan &amp; % Sebelum Delivery</span>
                                        <button type="button" @click="addDeliveryPoint()"
                                                class="px-2 py-1 rounded bg-white border border-slate-200 hover:bg-slate-100 text-[11px] font-semibold text-indigo-600">
                                            + Input Poin (%)
                                        </button>
                                    </div>
                                    <template x-if="deliveryPoints.length === 0">
                                        <p class="text-[11px] text-slate-400">
                                            @if($ticket->problems->count() > 0)
                                                Sudah ada {{ $ticket->problems->count() }} poin pekerjaan ({{ (int) $ticket->problems->sum('weight_percent') }}%). Klik <strong>+ Input Poin (%)</strong> jika ingin menambah lagi.
                                            @else
                                                Klik <strong>+ Input Poin (%)</strong> untuk langsung menambahkan rincian pekerjaan beserta bobot % sebelum diserahkan ke staff.
                                            @endif
                                        </p>
                                    </template>
                                    <template x-for="(pt, idx) in deliveryPoints" :key="idx">
                                        <div class="flex items-center gap-1.5">
                                            <input type="text"
                                                   :name="'problem_items[' + idx + '][title]'"
                                                   x-model="pt.title"
                                                   placeholder="Detail pekerjaan untuk staff..."
                                                   class="flex-1 px-2.5 py-1.5 rounded border border-slate-200 bg-white text-xs">
                                            <div class="relative w-20 shrink-0">
                                                <input type="number"
                                                       :name="'problem_items[' + idx + '][weight_percent]'"
                                                       x-model="pt.weight_percent"
                                                       min="1" max="100"
                                                       class="w-full pl-2 pr-5 py-1.5 rounded border border-slate-200 bg-white text-xs font-semibold">
                                                <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">%</span>
                                            </div>
                                            <button type="button" @click="removeDeliveryPoint(idx)" class="px-1.5 py-1 text-slate-400 hover:text-rose-600 text-xs">✕</button>
                                        </div>
                                    </template>
                                </div>
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
