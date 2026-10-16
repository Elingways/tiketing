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
    $priorityColors = [
        'low' => 'bg-slate-100 text-slate-600',
        'medium' => 'bg-sky-100 text-sky-700',
        'high' => 'bg-amber-100 text-amber-800',
        'urgent' => 'bg-rose-100 text-rose-700 font-extrabold',
    ];
@endphp

<div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-100">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 font-extrabold text-xs border border-indigo-200">
                    Inbox Hirarki Role: {{ $user->role?->name }}
                </span>
                @if($user->role?->parent)
                    <span class="text-xs text-slate-500">
                        Atasan Eskalasi: <strong class="text-slate-800">{{ $user->role->parent->name }}</strong>
                    </span>
                @endif
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2">
                Daftar Tiket Masuk Berdasarkan Hirarki
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Kelola tiket yang masuk dari role bawahan ({{ $user->role?->children->pluck('name')->join(', ') ?: 'Pemohon' }}), berikan persetujuan berjenjang, atau eskalasikan ke atasan.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('tickets.inbox', ['scope' => 'current_role']) }}"
               class="px-4 py-2 rounded-2xl text-xs font-bold transition {{ $scope === 'current_role' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Di Meja Role {{ $user->role?->name }}
            </a>
            <a href="{{ route('tickets.inbox', ['scope' => 'history_role']) }}"
               class="px-4 py-2 rounded-2xl text-xs font-bold transition {{ $scope === 'history_role' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Riwayat Diteruskan / Eskalasi
            </a>
            @if($user->isSuperuser())
                <a href="{{ route('tickets.inbox', ['scope' => 'all_system']) }}"
                   class="px-4 py-2 rounded-2xl text-xs font-bold transition {{ $scope === 'all_system' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' }}">
                    Semua Tiket Lintas Role (Superuser)
                </a>
            @endif
        </div>
    </div>

    <!-- Daftar Tiket -->
    <div class="mt-6">
        @if($tickets->isEmpty())
            <div class="p-12 rounded-3xl bg-slate-50 border border-dashed border-slate-200 text-center">
                <p class="text-base font-bold text-slate-700">Tidak ada tiket pada tampilan ini</p>
                <p class="text-xs text-slate-500 mt-1">Gunakan tombol Simulasi Pindah Role di kanan atas untuk mencoba membuat tiket sebagai Mahasiswa atau memproses sebagai Dosen/Kaprodi.</p>
            </div>
        @else
            <div class="space-y-3.5">
                @foreach($tickets as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}"
                       class="block p-5 rounded-2xl border border-slate-200/80 hover:border-indigo-400 bg-white hover:shadow-md transition">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-extrabold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">
                                    {{ $ticket->ticket_number }}
                                </span>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full border {{ $statusColors[$ticket->status] ?? 'bg-slate-100' }}">
                                    {{ $ticket->statusLabel() }}
                                </span>
                                <span class="text-[11px] px-2.5 py-0.5 rounded-full font-semibold {{ $priorityColors[$ticket->priority] ?? 'bg-slate-100' }}">
                                    Prioritas: {{ $ticket->priorityLabel() }}
                                </span>
                                <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full {{ $ticket->workflow_type === 'tiered_approval' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    {{ $ticket->workflow_type === 'tiered_approval' ? 'Approval Berjenjang (Tahap '.$ticket->approval_step.')' : 'Mode Eskalasi / Disposisi' }}
                                </span>
                            </div>
                            <span class="text-xs text-slate-400">{{ $ticket->created_at->format('d M Y H:i') }}</span>
                        </div>

                        <h2 class="text-base font-extrabold text-slate-900">{{ $ticket->title }}</h2>
                        <p class="text-xs text-slate-600 mt-1 line-clamp-2">{{ $ticket->description }}</p>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-slate-500">
                                    Pemohon: <strong class="text-slate-900">{{ $ticket->creator?->name }}</strong>
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">{{ $ticket->creatorRole?->name }}</span>
                                </span>
                                <span class="text-slate-300">•</span>
                                <span class="text-slate-500">
                                    Kategori: <strong class="text-slate-800">{{ $ticket->category?->name }}</strong>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-xl bg-slate-900 text-white text-[11px] font-bold">
                                    Posisi Saat Ini: Role {{ $ticket->currentRole?->name }}
                                </span>
                                <span class="px-3 py-1 rounded-xl bg-indigo-600 text-white text-[11px] font-bold">
                                    Buka & Proses →
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
