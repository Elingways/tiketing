@extends('layouts.app')

@section('content')
@php
    $roleBadgeColors = [
        'dekan' => 'bg-violet-50 text-violet-700 border-violet-200',
        'kaprodi' => 'bg-amber-50 text-amber-800 border-amber-200',
        'dosen' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'superuser' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
@endphp

<div x-data="{
    addLecturerModal: false,
    editLecturerModal: false,
    editData: {
        id: null,
        name: '',
        email: '',
        identity_number: '',
        department: '',
        jabatan: '',
        role_id: '',
        supervisor_id: ''
    },
    openEdit(lecturer) {
        this.editData = {
            id: lecturer.id,
            name: lecturer.name,
            email: lecturer.email,
            identity_number: lecturer.identity_number || '',
            department: lecturer.department || '',
            jabatan: lecturer.jabatan || '',
            role_id: lecturer.role_id || '',
            supervisor_id: lecturer.supervisor_id || ''
        };
        this.editLecturerModal = true;
    }
}">

    <!-- Sub-Navigation Tab Header & Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
        <div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-lg font-bold text-slate-900">Management Pengajar (Dosen, Kaprodi & Dekan)</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    REST API SIAKAD Aktif
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
                Kelola data pengajar dari SIAKAD (<code class="text-indigo-600 font-mono">GET /api/external/lecturers</code>) beserta penempatan Role Hirarki (Dosen, Kaprodi, Dekan).
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Switcher Sub-Menu Mahasiswa / Pengajar -->
            <div class="inline-flex p-1 rounded-xl bg-white border border-slate-200">
                <a href="{{ route('admin.users.students') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900">
                    Mahasiswa
                </a>
                <a href="{{ route('admin.users.lecturers') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 text-white">
                    Pengajar ({{ number_format($totalLecturers) }})
                </a>
            </div>

            <!-- Tombol Tarik Data REST API SIAKAD -->
            <form action="{{ route('admin.users.sync-siakad') }}" method="POST"
                  onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Menarik Data SIAKAD...';">
                @csrf
                <input type="hidden" name="type" value="lecturers">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition shadow-2xs cursor-pointer">
                    <span>🔄 Sinkronisasi SIAKAD API</span>
                </button>
            </form>

            <button type="button" @click="addLecturerModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition cursor-pointer">
                <span>+ Tambah Pengajar</span>
            </button>
        </div>
    </div>

    @if($autoSyncError)
        <div class="mb-4 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
            <strong>Info Koneksi SIAKAD API:</strong> {{ $autoSyncError }}
        </div>
    @endif

    <!-- 4 KPI Summary Cards Pengajar -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm shrink-0">
                ALL
            </div>
            <div>
                <p class="text-xl font-bold text-slate-900 leading-tight">{{ number_format($totalLecturers) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Total Pengajar</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm shrink-0">
                DSN
            </div>
            <div>
                <p class="text-xl font-bold text-emerald-600 leading-tight">{{ number_format($dosenCount) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Role Dosen</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-sm shrink-0">
                KPR
            </div>
            <div>
                <p class="text-xl font-bold text-amber-600 leading-tight">{{ number_format($kaprodiCount) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Role Kaprodi</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center font-bold text-sm shrink-0">
                DKN
            </div>
            <div>
                <p class="text-xl font-bold text-violet-600 leading-tight">{{ number_format($dekanCount) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Role Dekan / Fakultas</p>
            </div>
        </div>
    </div>

    <!-- Card Utama: Filter & Tabel Management Pengajar -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
        <!-- Info Bar Integrasi REST API SIAKAD -->
        <div class="px-5 py-3 bg-gradient-to-r from-indigo-50/70 via-slate-50 to-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2 text-slate-700">
                <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-800 font-mono font-semibold text-[10px]">HIRARKI PENGAJAR</span>
                <span>Setiap pengajar dapat ditetapkan sebagai <strong>Dosen</strong>, <strong>Kaprodi</strong>, atau <strong>Dekan</strong> sesuai alur eskalasi & approval tiket.</span>
            </div>
            <span class="text-[11px] text-slate-400 font-mono">{{ config('services.siakad.api_url') }}/api/external/lecturers</span>
        </div>

        <!-- Toolbar Search & Filter -->
        <form method="GET" action="{{ route('admin.users.lecturers') }}" class="p-4 border-b border-slate-100 bg-slate-50/50 grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
            <div class="sm:col-span-5 relative">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama pengajar, NIDN/NIP, email, atau program studi..."
                       class="w-full pl-9 pr-3 py-2 rounded-lg bg-white border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
            </div>

            <div class="sm:col-span-3">
                <select name="program_studi" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-slate-900">
                    <option value="">Semua Program Studi / Unit</option>
                    @foreach($programOptions as $prodi)
                        <option value="{{ $prodi }}" {{ $programStudi === $prodi ? 'selected' : '' }}>{{ $prodi }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-3">
                <select name="role_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-slate-900">
                    <option value="">Semua Role (Dosen / Kaprodi / Dekan)</option>
                    @foreach($lecturerRoles as $r)
                        <option value="{{ $r->id }}" {{ (string) $roleFilter === (string) $r->id ? 'selected' : '' }}>
                            Role: {{ $r->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-1 flex items-center gap-1.5 justify-end">
                <button type="submit" class="w-full sm:w-auto px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition">
                    Cari
                </button>
                @if($search || $programStudi || $roleFilter)
                    <a href="{{ route('admin.users.lecturers') }}" class="px-2.5 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 text-xs">
                        ✕
                    </a>
                @endif
            </div>
        </form>

        <!-- Tabel Data Pengajar (Gaya SuperApps USH + Hirarki Role) -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4">Nama Pengajar & Email</th>
                        <th class="py-3 px-4 w-32">NIDN / NIP</th>
                        <th class="py-3 px-4">Program Studi / Unit</th>
                        <th class="py-3 px-4 w-44">Role Hirarki</th>
                        <th class="py-3 px-4">Atasan Hirarki (Eskalasi)</th>
                        <th class="py-3 px-4 w-28 text-center">Mhs Bimbingan</th>
                        <th class="py-3 px-4 w-48 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($lecturers as $lecturer)
                        @php
                            $rSlug = $lecturer->role?->slug ?? 'dosen';
                            $rBadge = $roleBadgeColors[$rSlug] ?? 'bg-indigo-50 text-indigo-700 border-indigo-200';
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition align-middle">
                            <td class="py-3 px-4 text-slate-400 font-medium">
                                {{ $loop->iteration + ($lecturers->currentPage() - 1) * $lecturers->perPage() }}
                            </td>

                            <!-- Nama & Email -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-800 font-bold text-[11px] flex items-center justify-center shrink-0">
                                        {{ $lecturer->initials() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900 truncate">{{ $lecturer->name }}</p>
                                        <p class="text-[11px] text-slate-400 truncate">{{ $lecturer->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- NIDN / NIP -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-mono text-[11px] text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                    {{ $lecturer->identity_number ?: '-' }}
                                </span>
                            </td>

                            <!-- Program Studi / Fakultas -->
                            <td class="py-3 px-4">
                                <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700">
                                    {{ $lecturer->department ?: 'Semua Prodi' }}
                                </span>
                                @if($lecturer->jabatan)
                                    <span class="block text-[10px] text-slate-400 mt-0.5">{{ $lecturer->jabatan }}</span>
                                @endif
                            </td>

                            <!-- Role Hirarki (Dosen / Kaprodi / Dekan) -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $rBadge }}">
                                    <span>{{ $lecturer->role?->name ?? 'Dosen' }}</span>
                                </span>
                                @if($lecturer->role?->parent)
                                    <span class="block text-[10px] text-slate-400 mt-0.5">
                                        Hirarki: → {{ $lecturer->role->parent->name }}
                                    </span>
                                @endif
                            </td>

                            <!-- Atasan Langsung (Supervisor) -->
                            <td class="py-3 px-4">
                                @if($lecturer->supervisor)
                                    <span class="font-medium text-slate-800 block">{{ $lecturer->supervisor->name }}</span>
                                    <span class="text-[10px] text-slate-400">Role: {{ $lecturer->supervisor->role?->name }}</span>
                                @else
                                    <span class="text-[11px] text-slate-400">Puncak / Sesuai Role ({{ $lecturer->role?->parent?->name ?? 'Root' }})</span>
                                @endif
                            </td>

                            <!-- Total Mahasiswa Bimbingan -->
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold text-[11px]">
                                    {{ $lecturer->students_count ?? 0 }} Mhs
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button"
                                            @click="openEdit({{ Js::from([
                                                'id' => $lecturer->id,
                                                'name' => $lecturer->name,
                                                'email' => $lecturer->email,
                                                'identity_number' => $lecturer->identity_number,
                                                'department' => $lecturer->department,
                                                'jabatan' => $lecturer->jabatan,
                                                'role_id' => $lecturer->role_id,
                                                'supervisor_id' => $lecturer->supervisor_id,
                                            ]) }})"
                                            class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold text-[11px] transition">
                                        Ubah Role
                                    </button>

                                    <form action="{{ route('auth.quick-switch', $lecturer) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Simulasikan Login sebagai Pengajar ini"
                                                class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-[11px] transition">
                                            Login
                                        </button>
                                    </form>

                                    @if($lecturer->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $lecturer) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Hapus pengajar {{ addslashes($lecturer->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-semibold text-[11px] transition">
                                                ✕
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 px-4 text-center text-xs text-slate-400">
                                Tidak ada data pengajar. Klik tombol <strong>🔄 Sinkronisasi SIAKAD API</strong> untuk menarik data Dosen, Kaprodi, dan Dekan dari SIAKAD.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($lecturers->hasPages())
            <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/40">
                {{ $lecturers->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: Tambah Pengajar Baru -->
    <div x-show="addLecturerModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="addLecturerModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Tambah Pengajar (Dosen / Kaprodi / Dekan)</h3>
                <button type="button" @click="addLecturerModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-3.5 text-xs">
                @csrf
                <input type="hidden" name="user_type" value="pengajar">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap & Gelar</label>
                        <input type="text" name="name" required placeholder="Contoh: Dr. Budi Santoso, M.Kom."
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">NIDN / NIP</label>
                        <input type="text" name="identity_number" placeholder="Contoh: 0626049701"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" required placeholder="dosen@sugenghartono.ac.id"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Program Studi / Fakultas</label>
                        <input type="text" name="department" required placeholder="Contoh: Informatika"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Role Hirarki (Dosen / Kaprodi / Dekan)</label>
                        <select name="role_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white">
                            @foreach($lecturerRoles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} (Hirarki ke: {{ $r->parent?->name ?? 'Puncak' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jabatan Akademik</label>
                        <input type="text" name="jabatan" placeholder="Contoh: Kaprodi Informatika / Dosen Tetap"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Atasan Langsung (Supervisor Eskalasi Tiket)</label>
                    <select name="supervisor_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white">
                        <option value="">— Ikuti Hirarki Role Secara Otomatis —</option>
                        @foreach($allPotentialSupervisors as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }} — Role {{ $sup->role?->name }} ({{ $sup->department }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addLecturerModal = false" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white font-semibold">Simpan Pengajar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Role Pengajar (Dosen / Kaprodi / Dekan) & Atasan Hirarki -->
    <div x-show="editLecturerModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="editLecturerModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Atur Role Pengajar (Dosen / Kaprodi / Dekan)</h3>
                <button type="button" @click="editLecturerModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form :action="'{{ url('/admin/users') }}/' + editData.id" method="POST" class="p-6 space-y-3.5 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap & Gelar</label>
                        <input type="text" name="name" x-model="editData.name" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">NIDN / NIP</label>
                        <input type="text" name="identity_number" x-model="editData.identity_number"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" x-model="editData.email" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Program Studi / Unit</label>
                        <input type="text" name="department" x-model="editData.department"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Role Hirarki (Dosen / Kaprodi / Dekan)</label>
                        <select name="role_id" x-model="editData.role_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white font-semibold text-slate-900">
                            @foreach($lecturerRoles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} (Atasan: {{ $r->parent?->name ?? 'Puncak' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jabatan Struktural / Fungsional</label>
                        <input type="text" name="jabatan" x-model="editData.jabatan"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Atasan Langsung (Kaprodi / Dekan Tujuan Eskalasi)</label>
                    <select name="supervisor_id" x-model="editData.supervisor_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white">
                        <option value="">— Ikuti Rantai Role Hirarki —</option>
                        @foreach($allPotentialSupervisors as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }} — Role {{ $sup->role?->name }} ({{ $sup->department }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editLecturerModal = false" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white font-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
