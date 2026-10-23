<!-- General Role Inbox (Dosen, Dekan, Kaprodi, Yayasan, Civitas) -->
<div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-white flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Antrean Tiket Masuk</h2>
            <p class="text-xs text-slate-500 mt-0.5">Tinjau tiket masuk dan proses persetujuan sesuai hirarki layanan.</p>
        </div>

        <form action="{{ route('tickets.inbox') }}" method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="hidden" name="scope" value="{{ $scope }}">
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Cari No. Tiket, Judul..."
                   class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-slate-900">
            <select name="category_id" onchange="this.form.submit()"
                    class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 font-medium focus:outline-none">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (string) $categoryId === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-medium hover:bg-slate-800 transition">Filter</button>
            @if($search || $categoryId || $statusFilter)
                <a href="{{ route('tickets.inbox', ['scope' => $scope]) }}" class="px-2.5 py-1.5 rounded-xl bg-rose-50 text-rose-700 text-xs font-semibold hover:bg-rose-100 transition">Reset</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200/80 bg-slate-50/60 text-[11px] font-semibold text-slate-500 uppercase tracking-wider">
                    <th class="py-3.5 px-4 min-w-[150px]">Ticket ID</th>
                    <th class="py-3.5 px-4 min-w-[240px]">Subject</th>
                    <th class="py-3.5 px-4 min-w-[150px]">Customer</th>
                    <th class="py-3.5 px-4 min-w-[140px]">Channel</th>
                    <th class="py-3.5 px-4 min-w-[120px]">Priority</th>
                    <th class="py-3.5 px-4 min-w-[100px]">Status</th>
                    <th class="py-3.5 px-4 min-w-[100px] text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                @forelse($tickets as $ticket)
                    @php
                        $initial = strtoupper(substr($ticket->creator?->name ?? 'U', 0, 1));
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <a href="{{ route('tickets.show', $ticket) }}" class="font-mono font-bold text-indigo-600 hover:text-indigo-800 transition">
                                {{ $ticket->ticket_number }}
                            </a>
                            <div class="mt-1">
                                @include('tickets.partials.dates', ['ticket' => $ticket, 'format' => 'd M Y'])
                            </div>
                        </td>

                        <td class="py-4 px-4 align-top">
                            <a href="{{ route('tickets.show', $ticket) }}" class="font-semibold text-slate-900 hover:text-indigo-600 block leading-snug">
                                {{ $ticket->title }}
                            </a>
                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                @if($ticket->attachment_path)
                                    <button type="button"
                                            @click="openDocPreview('{{ route('tickets.preview', $ticket) }}', '{{ route('tickets.attachment', $ticket) }}', '{{ addslashes($ticket->attachment_name ?? 'Dokumen Lampiran') }}')"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50/70 hover:bg-indigo-100 text-indigo-700 text-[10px] font-medium border border-indigo-100 transition">
                                        {{ $ticket->isImageAttachment() ? '🖼️ Gambar Bukti' : '📎 Preview Dokumen' }}
                                    </button>
                                @endif
                            </div>
                        </td>

                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ $initial }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-semibold text-slate-900 block truncate">{{ $ticket->creator?->name ?? 'Pemohon' }}</span>
                                    <span class="text-[11px] text-slate-400 block truncate">{{ $ticket->creatorRole?->name }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="py-4 px-4 align-top">
                            <span class="inline-block text-xs text-slate-700 font-medium">
                                {{ $ticket->category?->name ?? 'Umum' }}
                            </span>
                        </td>

                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 text-xs font-medium border border-slate-200">
                                <span class="w-2 h-2 rounded-full {{ $ticket->priority === 'urgent' ? 'bg-rose-600' : ($ticket->priority === 'high' ? 'bg-orange-500' : ($ticket->priority === 'low' ? 'bg-emerald-500' : 'bg-amber-400')) }}"></span>
                                <span>{{ $ticket->priorityLabel() }}</span>
                            </div>
                        </td>

                        <td class="py-4 px-4 align-top whitespace-nowrap">
                            <span class="inline-block text-[11px] font-medium px-2.5 py-1 rounded-md border {{ $statusColors[$ticket->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $ticket->statusLabel() }}
                            </span>
                        </td>

                        <td class="py-4 px-4 align-top text-right whitespace-nowrap">
                            <a href="{{ route('tickets.show', $ticket) }}"
                               class="inline-flex items-center gap-1 px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-medium text-xs transition shadow-2xs">
                                <span>Proses</span>
                                <span>&rarr;</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 px-4 text-center">
                            <div class="max-w-xs mx-auto text-center">
                                <span class="w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-2">✓</span>
                                <p class="text-xs font-semibold text-slate-700">Tidak ada tiket dalam antrean saat ini</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Semua tiket telah diproses.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
