@extends('layouts.app')

@section('content')
@php
    $roleBadgeColors = [
        'supervisor-it' => 'bg-violet-50 text-violet-700 border-violet-200',
        'admin-it' => 'bg-sky-50 text-sky-700 border-sky-200',
        'staff' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'staff-it' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'akademik' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'yayasan' => 'bg-teal-50 text-teal-700 border-teal-200',
        'superuser' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
@endphp

<div x-data="{
    addEmployeeModal: false,
    editEmployeeModal: false,
    editData: {
        id: null,
        name: '',
        email: '',
        identity_number: '',
        department: '',
        jabatan: '',
        keahlian: '',
        role_id: '',
        supervisor_id: '',
        is_role_head: false
    },
    openEdit(emp) {
        this.editData = {
            id: emp.id,
            name: emp.name,
            email: emp.email,
            identity_number: emp.identity_number || '',
            department: emp.department || '',
            jabatan: emp.jabatan || '',
            keahlian: emp.keahlian || '',
            role_id: emp.role_id || '',
            supervisor_id: emp.supervisor_id || '',
            is_role_head: !!emp.is_role_head
        };
        this.editEmployeeModal = true;
    }
}">

    <!-- Sub-Navigation Tab Header & Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
        <div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-lg font-bold text-slate-900">Management Karyawan (Admin IT, Staff & Supervisor IT)</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                    Unit Operasional & IT
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
                Kelola data karyawan, teknisi, serta alur hirarki penanganan tiket untuk role <strong>Staff</strong>, <strong>Admin IT</strong>, dan <strong>Supervisor IT</strong>.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Switcher Sub-Menu Mahasiswa / Pengajar / Karyawan -->
            <div class="inline-flex p-1 rounded-xl bg-white border border-slate-200">
                <a href="{{ route('admin.users.students') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900">
                    Mahasiswa
                </a>
                <a href="{{ route('admin.users.lecturers') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900">
                    Pengajar
                </a>
                <a href="{{ route('admin.users.employees') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 text-white">
                    Karyawan ({{ number_format($totalEmployees) }})
                </a>
            </div>

            <button type="button" @click="addEmployeeModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition cursor-pointer">
                <span>+ Tambah Karyawan</span>
            </button>
        </div>
    </div>

    <!-- 4 KPI Summary Cards Karyawan -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm shrink-0">
                ALL
            </div>
            <div>
                <p class="text-xl font-bold text-slate-900 leading-tight">{{ number_format($totalEmployees) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Total Karyawan</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center font-bold text-sm shrink-0">
                SPV
            </div>
            <div>
                <p class="text-xl font-bold text-violet-600 leading-tight">{{ number_format($supervisorItCount) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Role Supervisor IT</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-sm shrink-0">
                AIT
            </div>
            <div>
                <p class="text-xl font-bold text-sky-600 leading-tight">{{ number_format($adminItCount) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Role Admin IT</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm shrink-0">
                STF
            </div>
            <div>
                <p class="text-xl font-bold text-emerald-600 leading-tight">{{ number_format($staffCount) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Role Staff</p>
            </div>
        </div>
    </div>

    <!-- Card Utama: Filter & Tabel Management Karyawan -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
        <!-- Info Bar Hirarki Karyawan IT -->
        <div class="px-5 py-3 bg-gradient-to-r from-sky-50/70 via-slate-50 to-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2 text-slate-700 flex-wrap">
                <span class="px-2 py-0.5 rounded bg-sky-100 text-sky-800 font-mono font-semibold text-[10px]">ALUR HIRARKI IT</span>
                <span><strong>Staff</strong> → <strong>Admin IT</strong> (Koordinator & Teknisi Spesialis) → <strong>Supervisor IT</strong>.</span>
            </div>
            <span class="text-[11px] text-slate-400">Mendukung pembagian tugas berdasarkan keahlian & pelaporan progres (%)</span>
        </div>

        <!-- Toolbar Search & Filter -->
        <form method="GET" action="{{ route('admin.users.employees') }}" class="p-4 border-b border-slate-100 bg-slate-50/50 grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
            <div class="sm:col-span-5 relative">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama karyawan, ID/NIP, keahlian (Jaringan/Hardware), atau unit..."
                       class="w-full pl-9 pr-3 py-2 rounded-lg bg-white border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
            </div>

            <div class="sm:col-span-3">
                <select name="department" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-slate-900">
                    <option value="">Semua Unit Kerja / Divisi</option>
                    @foreach($unitOptions as $unit)
                        <option value="{{ $unit }}" {{ $unitKerja === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-3">
                <select name="role_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-slate-900">
                    <option value="">Semua Role (Supervisor IT / Admin IT / Staff)</option>
                    @foreach($employeeRoles as $r)
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
                @if($search || $unitKerja || $roleFilter)
                    <a href="{{ route('admin.users.employees') }}" class="px-2.5 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 text-xs">
                        ✕
                    </a>
                @endif
            </div>
        </form>

        <!-- Tabel Data Karyawan -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4">Nama Karyawan & Email</th>
                        <th class="py-3 px-4 w-32">ID / NIP</th>
                        <th class="py-3 px-4">Unit Kerja & Jabatan</th>
                        <th class="py-3 px-4">Keahlian / Spesialisasi</th>
                        <th class="py-3 px-4 w-44">Role Hirarki</th>
                        <th class="py-3 px-4">Atasan Hirarki</th>
                        <th class="py-3 px-4 w-24 text-center">Tiket Aktif</th>
                        <th class="py-3 px-4 w-48 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($employees as $emp)
                        @php
                            $rSlug = $emp->role?->slug ?? 'staff';
                            $rBadge = $roleBadgeColors[$rSlug] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            $isRoleHead = $emp->role && (int) $emp->role->head_user_id === (int) $emp->id;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition align-middle">
                            <td class="py-3 px-4 text-slate-400 font-medium">
                                {{ $loop->iteration + ($employees->currentPage() - 1) * $employees->perPage() }}
                            </td>

                            <!-- Nama & Email -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-sky-100 text-sky-800 font-bold text-[11px] flex items-center justify-center shrink-0">
                                        {{ $emp->initials() }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <p class="font-semibold text-slate-900 truncate">{{ $emp->name }}</p>
                                            @if($isRoleHead)
                                                <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-semibold">
                                                    Kepala Role
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-slate-400 truncate">{{ $emp->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- NIP / ID Karyawan -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-mono text-[11px] text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                    {{ $emp->identity_number ?: '-' }}
                                </span>
                            </td>

                            <!-- Unit Kerja & Jabatan -->
                            <td class="py-3 px-4">
                                <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $emp->department ?: 'Unit Umum / IT' }}
                                </span>
                                @if($emp->jabatan)
                                    <span class="block text-[10px] text-slate-400 mt-0.5">{{ $emp->jabatan }}</span>
                                @endif
                            </td>

                            <!-- Keahlian / Spesialisasi -->
                            <td class="py-3 px-4">
                                @if($emp->keahlian)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ $emp->keahlian }}
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- Role Hirarki -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $rBadge }}">
                                    <span>{{ $emp->role?->name ?? 'Staff' }}</span>
                                </span>
                                @if($emp->role?->parent)
                                    <span class="block text-[10px] text-slate-400 mt-0.5">
                                        Hirarki: → {{ $emp->role->parent->name }}
                                    </span>
                                @endif
                            </td>

                            <!-- Atasan Langsung (Supervisor) -->
                            <td class="py-3 px-4">
                                @if($emp->supervisor)
                                    <span class="font-medium text-slate-800 block">{{ $emp->supervisor->name }}</span>
                                    <span class="text-[10px] text-slate-400">Role: {{ $emp->supervisor->role?->name }}</span>
                                @else
                                    <span class="text-[11px] text-slate-400">Puncak / Sesuai Role ({{ $emp->role?->parent?->name ?? 'Root' }})</span>
                                @endif
                            </td>

                            <!-- Tiket Aktif -->
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full {{ ($emp->active_tickets_count ?? 0) > 0 ? 'bg-amber-50 text-amber-700 font-bold' : 'bg-slate-100 text-slate-500 font-medium' }} text-[11px]">
                                    {{ $emp->active_tickets_count ?? 0 }} Tiket
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button"
                                            @click="openEdit({{ Js::from([
                                                'id' => $emp->id,
                                                'name' => $emp->name,
                                                'email' => $emp->email,
                                                'identity_number' => $emp->identity_number,
                                                'department' => $emp->department,
                                                'jabatan' => $emp->jabatan,
                                                'keahlian' => $emp->keahlian,
                                                'role_id' => $emp->role_id,
                                                'supervisor_id' => $emp->supervisor_id,
                                                'is_role_head' => $isRoleHead,
                                            ]) }})"
                                            class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold text-[11px] transition">
                                        Edit
                                    </button>

                                    <form action="{{ route('auth.quick-switch', $emp) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Simulasikan Login sebagai Karyawan ini"
                                                class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-[11px] transition">
                                            Login
                                        </button>
                                    </form>

                                    @if($emp->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $emp) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Hapus karyawan {{ addslashes($emp->name) }}?')">
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
                            <td colspan="9" class="py-12 px-4 text-center text-xs text-slate-400">
                                Belum ada data karyawan pada filter ini. Klik tombol <strong>+ Tambah Karyawan</strong> untuk menambahkan Admin IT, Staff, atau Supervisor IT.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($employees->hasPages())
            <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/40">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: Tambah Karyawan Baru -->
    <div x-show="addEmployeeModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="addEmployeeModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Tambah Karyawan (Admin IT / Staff / Supervisor IT)</h3>
                <button type="button" @click="addEmployeeModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-3.5 text-xs">
                @csrf
                <input type="hidden" name="user_type" value="karyawan">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required placeholder="Contoh: Fajar Nugroho, S.T."
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">ID Karyawan / NIP</label>
                        <input type="text" name="identity_number" placeholder="Contoh: KRY-IT-008"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" required placeholder="karyawan.it@kampus.ac.id"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Unit Kerja / Divisi</label>
                        <input type="text" name="department" required placeholder="Contoh: Direktorat Teknologi Informasi (DTI)"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Role Hirarki (Supervisor IT / Admin IT / Staff)</label>
                        <select name="role_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white">
                            @foreach($employeeRoles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} (Hirarki ke: {{ $r->parent?->name ?? 'Puncak' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jabatan Operasional</label>
                        <input type="text" name="jabatan" placeholder="Contoh: Staff IT Jaringan / Supervisor IT"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Spesialisasi / Keahlian Penanganan Tiket</label>
                    <input type="text" name="keahlian" placeholder="Contoh: Jaringan & Server / Perangkat keras / Aplikasi & SIAKAD"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200">
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

                <label class="flex items-center gap-2 pt-1 cursor-pointer">
                    <input type="checkbox" name="is_role_head" value="1" class="rounded border-slate-300 text-slate-900">
                    <span class="text-slate-700 font-medium">Jadikan Kepala / Koordinator Role ini (menerima tiket masuk pertama untuk didistribusikan ke staff)</span>
                </label>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addEmployeeModal = false" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white font-semibold">Simpan Karyawan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Karyawan & Role -->
    <div x-show="editEmployeeModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="editEmployeeModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Atur Data & Role Karyawan</h3>
                <button type="button" @click="editEmployeeModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form :action="'{{ url('/admin/users') }}/' + editData.id" method="POST" class="p-6 space-y-3.5 text-xs">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_role_head" value="0">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" x-model="editData.name" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">ID Karyawan / NIP</label>
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
                        <label class="block font-semibold text-slate-700 mb-1">Unit Kerja / Divisi</label>
                        <input type="text" name="department" x-model="editData.department"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Role Hirarki</label>
                        <select name="role_id" x-model="editData.role_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white font-semibold text-slate-900">
                            @foreach($employeeRoles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} (Atasan: {{ $r->parent?->name ?? 'Puncak' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Jabatan Operasional</label>
                        <input type="text" name="jabatan" x-model="editData.jabatan"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Spesialisasi / Keahlian Penanganan Tiket</label>
                    <input type="text" name="keahlian" x-model="editData.keahlian"
                           placeholder="Contoh: Jaringan & Server / Perangkat keras / Aplikasi & SIAKAD"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200">
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Atasan Langsung</label>
                    <select name="supervisor_id" x-model="editData.supervisor_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white">
                        <option value="">— Ikuti Rantai Role Hirarki —</option>
                        @foreach($allPotentialSupervisors as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }} — Role {{ $sup->role?->name }} ({{ $sup->department }})</option>
                        @endforeach
                    </select>
                </div>

                <label class="flex items-center gap-2 pt-1 cursor-pointer">
                    <input type="checkbox" name="is_role_head" value="1" x-model="editData.is_role_head" class="rounded border-slate-300 text-slate-900">
                    <span class="text-slate-700 font-medium">Kepala / Koordinator Role ini (menerima tiket masuk pertama untuk didistribusikan ke staff)</span>
                </label>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editEmployeeModal = false" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white font-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
