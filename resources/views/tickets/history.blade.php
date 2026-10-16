@extends('layouts.app')

@section('content')
<!-- Header Halaman Riwayat Tiket (Khusus Status Approved / Resolved) -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-900">Riwayat Tiket (Sudah Di-Approve)</h1>
        <p class="text-xs text-slate-500">
            Daftar riwayat tiket Anda yang telah disetujui / selesai diproses beserta preview dokumen lampirannya.
        </p>
    </div>

    <a href="{{ route('tickets.my') }}"
       class="px-3.5 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition self-start sm:self-auto">
        ← Kembali ke Tiket Saya
    </a>
</div>

<!-- Card Utama: Toolbar Search/Dropdown + Tabel Riwayat Approve -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
    <!-- Toolbar Pencarian & Dropdown Kategori -->
    <form action="{{ route('tickets.history') }}" method="GET" class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="relative flex-1 max-w-md">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Cari No. Tiket atau Judul Tiket yang sudah di-approve..."
                   class="w-full pl-9 pr-3 py-2 rounded-lg bg-white border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
        </div>

        <div class="flex items-center gap-2">
            <select name="category_id" onchange="this.form.submit()"
                    class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-slate-900">
                <option value="">Semua Kategori Layanan</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (string) $categoryId === (string) $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="px-3.5 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition">
                Filter
            </button>

            @if($search || $categoryId)
                <a href="{{ route('tickets.history') }}"
                   class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-600 hover:bg-slate-100">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Tabel Riwayat Tiket yang Sudah Di-Approve -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <th class="py-3 px-4 w-36">No. Tiket</th>
                    <th class="py-3 px-4">Judul & Kategori Tiket</th>
                    <th class="py-3 px-4">Proses Approval (Hirarki)</th>
                    <th class="py-3 px-4 w-36">Status Akhir</th>
                    <th class="py-3 px-4 w-40">Dokumen Lampiran</th>
                    <th class="py-3 px-4 w-24 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                @forelse($tickets as $ticket)
                    @php
                        $stepper = $ticket->getHierarchyStepper();
                        $lastApprovalLog = $ticket->logs->whereIn('action', ['approved_step', 'final_approved', 'resolved'])->last();
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition align-top">
                        <!-- 1. No. Tiket -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="font-mono font-semibold text-emerald-700 block">{{ $ticket->ticket_number }}</span>
                            <span class="text-[11px] text-slate-400">
                                Selesai {{ ($ticket->resolved_at ?? $ticket->updated_at)->format('d M Y') }}
                            </span>
                        </td>

                        <!-- 2. Judul & Kategori -->
                        <td class="py-3.5 px-4">
                            <a href="{{ route('tickets.show', $ticket) }}" class="font-semibold text-slate-900 hover:text-indigo-600 block">
                                {{ $ticket->title }}
                            </a>
                            <span class="text-[11px] text-slate-500 block mt-0.5">{{ $ticket->category?->name }}</span>
                        </td>

                        <!-- 3. Proses Approval (Hirarki) -->
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-1 flex-wrap mb-1">
                                @foreach($stepper as $idx => $step)
                                    @if($idx > 0)
                                        <span class="text-slate-300 text-[10px]">→</span>
                                    @endif
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        ✓ {{ $step['role']->name }}
                                    </span>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-emerald-700 font-medium">
                                Disetujui tuntas di Role {{ $ticket->currentRole?->name }}
                                @if($lastApprovalLog && $lastApprovalLog->user)
                                    oleh {{ $lastApprovalLog->user->name }}
                                @endif
                            </p>
                        </td>

                        <!-- 4. Status Akhir -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <span class="inline-block text-[11px] font-medium px-2.5 py-0.5 rounded-md border bg-emerald-50 text-emerald-700 border-emerald-200">
                                ✓ Approved / Selesai
                            </span>
                        </td>

                        <!-- 5. Dokumen Lampiran (Preview Modal) -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if($ticket->attachment_path)
                                <button type="button"
                                        @click="openDocPreview('{{ route('tickets.preview', $ticket) }}', '{{ route('tickets.attachment', $ticket) }}', '{{ addslashes($ticket->attachment_name ?? 'Dokumen Lampiran') }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[11px] font-semibold transition">
                                    <span>📎 Preview Dokumen</span>
                                </button>
                            @else
                                <span class="text-slate-400 text-[11px]">Tidak ada lampiran</span>
                            @endif
                        </td>

                        <!-- 6. Aksi -->
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <a href="{{ route('tickets.show', $ticket) }}"
                               class="inline-block px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-medium transition">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 px-4 text-center text-xs text-slate-400">
                            Belum ada riwayat tiket dengan status <strong>Approved / Selesai</strong>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
