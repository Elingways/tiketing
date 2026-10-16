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
@endphp

<!-- Header Dashboard -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-lg font-bold text-slate-900">Beranda — Role {{ $role?->name }}</h1>
        <p class="text-xs text-slate-500">
            Ringkasan tiket masuk, progres tahapan hirarki, dan struktur organisasi role.
        </p>
    </div>
</div>

<!-- 4 Metrik Utama -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="p-4 rounded-xl bg-white border border-slate-200">
        <p class="text-xs text-slate-500">Tiket Masuk ({{ $role?->name }})</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['role_inbox_active'] }}</p>
    </div>
    <div class="p-4 rounded-xl bg-white border border-slate-200">
        <p class="text-xs text-slate-500">Diteruskan ke Atasan</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['escalated_or_forwarded'] }}</p>
    </div>
    <div class="p-4 rounded-xl bg-white border border-slate-200">
        <p class="text-xs text-slate-500">Total Tiket Saya</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['my_tickets_total'] }}</p>
    </div>
    <div class="p-4 rounded-xl bg-white border border-slate-200">
        <p class="text-xs text-slate-500">Sudah Di-Approve</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['my_tickets_resolved'] }}</p>
    </div>
</div>

<!-- Grid Utama: Tabel Tiket & Struktur Hirarki -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-8 space-y-6">
        @if($role?->can_receive_ticket || $user->isSuperuser())
            <!-- Tabel Tiket Masuk Role -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900">Tiket Masuk — Role {{ $role?->name }}</h2>
                    <a href="{{ route('tickets.inbox') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Buka Semua →</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <th class="py-2.5 px-4">No. Tiket</th>
                                <th class="py-2.5 px-4">Pemohon</th>
                                <th class="py-2.5 px-4">Judul Tiket</th>
                                <th class="py-2.5 px-4">Proses</th>
                                <th class="py-2.5 px-4">Status</th>
                                <th class="py-2.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @forelse($incomingTickets as $t)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 px-4 font-mono font-semibold text-indigo-600 whitespace-nowrap">{{ $t->ticket_number }}</td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-slate-800 block">{{ $t->creator?->name }}</span>
                                        <span class="text-[11px] text-slate-400">{{ $t->creatorRole?->name }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-semibold text-slate-900 block">{{ $t->title }}</span>
                                        <span class="text-[11px] text-slate-400">{{ $t->category?->name }}</span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="text-[11px] text-slate-600">Tahap {{ $t->approval_step }} ({{ $t->currentRole?->name }})</span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="text-[11px] font-medium px-2 py-0.5 rounded border {{ $statusColors[$t->status] ?? 'bg-slate-100' }}">
                                            {{ $t->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <a href="{{ route('tickets.show', $t) }}" class="text-xs font-semibold text-indigo-600 hover:underline">Proses</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-slate-400">Belum ada tiket masuk di antrean Role {{ $role?->name }}.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Tabel Riwayat Tiket Saya -->
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Tiket Terbaru Saya</h2>
                <a href="{{ route('tickets.my') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Lihat Riwayat →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-2.5 px-4">No. Tiket</th>
                            <th class="py-2.5 px-4">Judul & Kategori</th>
                            <th class="py-2.5 px-4">Proses (Tahap)</th>
                            <th class="py-2.5 px-4">Status</th>
                            <th class="py-2.5 px-4">Dokumen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($myCreatedTickets as $mt)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-mono font-semibold text-indigo-600 whitespace-nowrap">
                                    <a href="{{ route('tickets.show', $mt) }}" class="hover:underline">{{ $mt->ticket_number }}</a>
                                </td>
                                <td class="py-3 px-4">
                                    <a href="{{ route('tickets.show', $mt) }}" class="font-semibold text-slate-900 hover:text-indigo-600 block">{{ $mt->title }}</a>
                                    <span class="text-[11px] text-slate-400">{{ $mt->category?->name }}</span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="text-[11px] text-slate-600">
                                        Tahap {{ $mt->approval_step }}: <strong>Role {{ $mt->currentRole?->name }}</strong>
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="text-[11px] font-medium px-2 py-0.5 rounded border {{ $statusColors[$mt->status] ?? 'bg-slate-100' }}">
                                        {{ $mt->statusLabel() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($mt->attachment_path)
                                        <button type="button"
                                                @click="openDocPreview('{{ route('tickets.preview', $mt) }}', '{{ route('tickets.attachment', $mt) }}', '{{ addslashes($mt->attachment_name ?? 'Dokumen') }}')"
                                                class="text-[11px] font-semibold text-indigo-600 hover:underline">
                                            📎 Preview
                                        </button>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-xs text-slate-400">Belum ada tiket yang diajukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Struktur Hirarki Role -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-slate-900">Struktur Hirarki Role</h2>
                @if($user->isSuperuser())
                    <a href="{{ route('admin.hierarchy') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Edit</a>
                @endif
            </div>
            <div class="space-y-2">
                @foreach($rootRoles as $rootNode)
                    @include('partials.hierarchy-node', ['node' => $rootNode, 'highlightRoleId' => $role?->id])
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
