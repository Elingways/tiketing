<!-- Modal Detail Progress Staff (Reusable untuk Kepala Staff, Admin IT, dan Staff) -->
<div x-show="staffDetail" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
     @keydown.escape.window="staffDetail = null">
    <div @click.outside="staffDetail = null" class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full max-h-[85vh] flex flex-col overflow-hidden">
        @foreach($staffProgress as $progress)
            <div x-show="staffDetail === {{ (int) $progress['user']->id }}" class="flex flex-col min-h-0">
                <div class="px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-3 bg-slate-50/70">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs font-bold">
                                {{ strtoupper(substr($progress['user']->name, 0, 1)) }}
                            </span>
                            <h2 class="text-sm font-bold text-slate-900">Tiket {{ $progress['user']->name }}@if($progress['user']->keahlian) — {{ $progress['user']->keahlian }}@endif</h2>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1 pl-9">
                            <strong class="text-slate-700">{{ $progress['tickets'] }} tiket sedang dikerjakan</strong>
                            @if($progress['percent'] !== null) · rata-rata {{ $progress['percent'] }}% @endif
                            @if(($progress['blocked'] ?? 0) > 0)
                                · <span class="font-bold text-rose-700">{{ $progress['blocked'] }} blocked</span>
                            @endif
                        </p>
                    </div>
                    <button type="button" @click="staffDetail = null" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-slate-700 transition">✕</button>
                </div>
                <div class="overflow-y-auto divide-y divide-slate-100 p-2">
                    @forelse($progress['open_tickets'] as $openTicket)
                        <div class="p-4 rounded-xl hover:bg-slate-50 transition">
                            <div class="flex items-center justify-between gap-3">
                                <a href="{{ route('tickets.show', $openTicket) }}" class="font-mono text-xs font-bold text-indigo-600 hover:underline">{{ $openTicket->ticket_number }}</a>
                                <span class="text-xs font-bold {{ $openTicket->work_status === 'blocked' ? 'text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200' : 'text-slate-700' }}">
                                    @if($openTicket->workStatusLabel()){{ $openTicket->workStatusLabel() }} · @endif{{ $openTicket->progressPercent() === null ? '—' : $openTicket->progressPercent() . '%' }}
                                </span>
                            </div>
                            <a href="{{ route('tickets.show', $openTicket) }}" class="mt-1.5 block text-xs font-semibold text-slate-900 hover:text-indigo-600">{{ $openTicket->title }}</a>
                            <div class="mt-1">
                                @include('tickets.partials.dates', ['ticket' => $openTicket, 'format' => 'd M Y'])
                            </div>
                            <div class="mt-1 text-[11px] text-slate-500 flex items-center gap-1.5 flex-wrap">
                                <span>{{ $openTicket->statusLabel() }}</span>
                                @if(($openTicket->problems_count ?? 0) > 0)
                                    <span>· <strong class="text-emerald-700">{{ $openTicket->done_problems_count ?? 0 }}/{{ $openTicket->problems_count }} poin selesai</strong></span>
                                @endif
                                @if($openTicket->priority_rank)
                                    <span>· Usulan pemohon: {{ $openTicket->priorityLabel() }}</span>
                                @endif
                            </div>

                            @if($isHead || $isAdmin || $user->isSuperuser())
                                <form action="{{ route('tickets.priority', $openTicket) }}" method="POST" class="mt-3 bg-slate-50 p-3 rounded-xl border border-slate-200/70">
                                    @csrf
                                    <p class="text-[11px] font-semibold text-slate-700 mb-2">
                                        Nilai prioritas
                                        @if(!$openTicket->assessed_priority_rank)
                                            <span class="text-amber-700 font-normal">· belum dinilai</span>
                                        @endif
                                    </p>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        @for($level = 1; $level <= 4; $level++)
                                            <button type="submit" name="assessed_priority_rank" value="{{ $level }}"
                                                    class="px-2.5 py-1.5 rounded-lg border text-left text-[11px] font-medium transition {{ (int) $openTicket->assessed_priority_rank === $level ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100' }}">
                                                {{ \App\Models\RolePriorityRoute::labelFor($level) }}
                                            </button>
                                        @endfor
                                    </div>
                                </form>
                            @endif

                            @if($openTicket->progress_note)
                                <p class="mt-2 text-[11px] text-slate-600 bg-amber-50/70 p-2.5 rounded-lg border border-amber-200/60 leading-relaxed">
                                    <span class="font-semibold text-amber-900">Catatan Staf:</span> {{ $openTicket->progress_note }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-xs text-slate-400">Belum ada tiket yang sedang dikerjakan.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
