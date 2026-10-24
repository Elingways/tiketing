<!-- Modal Detail Progress Staff (Clean Modern & Dynamic) -->
<div x-show="staffDetail" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs"
     @keydown.escape.window="staffDetail = null">
    <div @click.outside="staffDetail = null"
         class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-xl w-full max-h-[85vh] flex flex-col overflow-hidden">
        @foreach($staffProgress as $progress)
            <div x-show="staffDetail === {{ (int) $progress['user']->id }}" class="flex flex-col min-h-0">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 bg-slate-50/80">
                    <div class="flex items-center gap-3 min-w-0">
                        @if($progress['user']->avatar_url)
                            <img src="{{ $progress['user']->avatar_url }}" alt="{{ $progress['user']->name }}"
                                 class="w-10 h-10 rounded-xl object-cover ring-2 ring-white shadow-sm shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white flex items-center justify-center text-sm font-bold shadow-sm shadow-indigo-500/25 shrink-0">
                                {{ strtoupper(substr($progress['user']->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-sm font-bold text-slate-900 truncate">{{ $progress['user']->name }}</h2>
                                @if($progress['user']->keahlian)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ $progress['user']->keahlian }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-2">
                                <span class="font-semibold text-slate-700">{{ $progress['tickets'] }} tiket berjalan</span>
                                @if($progress['percent'] !== null)
                                    <span>· Progres {{ $progress['percent'] }}%</span>
                                @endif
                                @if(($progress['blocked'] ?? 0) > 0)
                                    <span class="font-bold text-rose-600">· {{ $progress['blocked'] }} terkendala</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <button type="button" @click="staffDetail = null"
                            class="w-8 h-8 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-50 transition shadow-2xs cursor-pointer">
                        ✕
                    </button>
                </div>

                <!-- Modal Body: Ticket List -->
                <div class="overflow-y-auto divide-y divide-slate-100 p-4 space-y-3">
                    @forelse($progress['open_tickets'] as $openTicket)
                        <div class="p-4 rounded-xl bg-slate-50/60 border border-slate-200/70 hover:border-indigo-200 transition">
                            <div class="flex items-center justify-between gap-3">
                                <a href="{{ route('tickets.show', $openTicket) }}" class="font-mono text-xs font-bold text-indigo-600 hover:underline">
                                    {{ $openTicket->ticket_number }}
                                </a>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-lg border {{ $openTicket->work_status === 'blocked' ? 'text-rose-700 bg-rose-50 border-rose-200' : 'text-emerald-700 bg-emerald-50 border-emerald-200' }}">
                                    @if($openTicket->workStatusLabel()){{ $openTicket->workStatusLabel() }} · @endif{{ $openTicket->progressPercent() === null ? '0%' : $openTicket->progressPercent() . '%' }}
                                </span>
                            </div>

                            <a href="{{ route('tickets.show', $openTicket) }}" class="mt-1.5 block text-xs font-bold text-slate-900 hover:text-indigo-600 leading-snug">
                                {{ $openTicket->title }}
                            </a>

                            <div class="mt-1.5 flex items-center gap-2 text-[11px] text-slate-500 flex-wrap">
                                <span>{{ $openTicket->category?->name ?? 'Layanan' }}</span>
                                <span>•</span>
                                <span>Pemohon: {{ $openTicket->creator?->name }}</span>
                                @if(($openTicket->problems_count ?? 0) > 0)
                                    <span>•</span>
                                    <span class="font-semibold text-emerald-700">{{ $openTicket->done_problems_count ?? 0 }}/{{ $openTicket->problems_count }} checklist</span>
                                @endif
                                @if($openTicket->priority_rank)
                                    <span>•</span>
                                    <span>Usulan pemohon: <strong class="text-slate-700">{{ $openTicket->priorityLabel() }}</strong></span>
                                @endif
                            </div>

                            <div class="mt-1 text-[11px] text-slate-400">
                                @include('tickets.partials.dates', ['ticket' => $openTicket, 'format' => 'd M Y'])
                            </div>

                            @if($isHead || $isAdmin || $user->isSuperuser())
                                <form action="{{ route('tickets.priority', $openTicket) }}" method="POST" class="mt-3 bg-white p-3 rounded-xl border border-slate-200 shadow-2xs">
                                    @csrf
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-[11px] font-bold text-slate-700">
                                            Nilai prioritas
                                            @if(!$openTicket->assessed_priority_rank)
                                                <span class="text-amber-700 font-normal">· belum dinilai</span>
                                            @else
                                                <span class="text-[10px] font-medium text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">Aktif: {{ \App\Models\RolePriorityRoute::labelFor($openTicket->assessed_priority_rank) }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        @for($level = 1; $level <= 4; $level++)
                                            <button type="submit" name="assessed_priority_rank" value="{{ $level }}"
                                                    class="px-2.5 py-1.5 rounded-lg border text-left text-[11px] font-semibold transition cursor-pointer {{ (int) $openTicket->assessed_priority_rank === $level ? 'bg-indigo-600 border-indigo-600 text-white shadow-2xs' : 'bg-slate-50 border-slate-200/80 text-slate-700 hover:bg-slate-100' }}">
                                                {{ \App\Models\RolePriorityRoute::labelFor($level) }}
                                            </button>
                                        @endfor
                                    </div>
                                </form>
                            @endif

                            @if($openTicket->progress_note)
                                <div class="mt-2.5 text-[11px] text-slate-700 bg-amber-50/80 p-2.5 rounded-xl border border-amber-200/80 leading-relaxed">
                                    <span class="font-bold text-amber-900 block mb-0.5">Catatan Progres Staf:</span>
                                    {{ $openTicket->progress_note }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs text-slate-400">
                            Tidak ada tiket yang sedang dikerjakan staf ini saat ini.
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
