@extends('layouts.app')

@section('content')
<div x-data="{
    addStudentModal: false,
    editStudentModal: false,
    editData: {
        id: null,
        name: '',
        email: '',
        identity_number: '',
        department: '',
        angkatan: '',
        semester: '',
        status_akademik: 'Aktif',
        supervisor_id: '',
        role_id: '{{ $mahasiswaRole?->id }}'
    },
    openEdit(student) {
        this.editData = {
            id: student.id,
            name: student.name,
            email: student.email,
            identity_number: student.identity_number || '',
            department: student.department || '',
            angkatan: student.angkatan || 2024,
            semester: student.semester || 1,
            status_akademik: student.status_akademik || 'Aktif',
            supervisor_id: student.supervisor_id || '',
            role_id: student.role_id || '{{ $mahasiswaRole?->id }}'
        };
        this.editStudentModal = true;
    }
}">

    <!-- Sub-Navigation Tab Header & Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
        <div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-lg font-bold text-slate-900">Management Mahasiswa</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    REST API SIAKAD Aktif
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
                Direktori lengkap mahasiswa Universitas Sugeng Hartono yang disinkronkan dari endpoint <code class="text-indigo-600 font-mono">GET /api/external/students</code>.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <!-- Switcher Sub-Menu Mahasiswa / Pengajar / Karyawan -->
            <div class="inline-flex p-1 rounded-xl bg-white border border-slate-200">
                <a href="{{ route('admin.users.students') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 text-white">
                    Mahasiswa ({{ number_format($totalStudents) }})
                </a>
                <a href="{{ route('admin.users.lecturers') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900">
                    Pengajar
                </a>
            </div>

            <!-- Tombol Tarik Data REST API SIAKAD -->
            <form action="{{ route('admin.users.sync-siakad') }}" method="POST"
                  onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Menarik Data SIAKAD...';">
                @csrf
                <input type="hidden" name="type" value="students">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition shadow-2xs cursor-pointer">
                    <span>🔄 Sinkronisasi SIAKAD API</span>
                </button>
            </form>

            <button type="button" @click="addStudentModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition cursor-pointer">
                <span>+ Tambah Mahasiswa</span>
            </button>
        </div>
    </div>

    @if($autoSyncError)
        <div class="mb-4 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
            <strong>Info Koneksi SIAKAD API:</strong> {{ $autoSyncError }}
        </div>
    @endif

    <!-- 4 KPI Summary Cards (Gaya SuperApps USH) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm shrink-0">
                MHS
            </div>
            <div>
                <p class="text-xl font-bold text-slate-900 leading-tight">{{ number_format($totalStudents) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Total Mahasiswa</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm shrink-0">
                AKT
            </div>
            <div>
                <p class="text-xl font-bold text-emerald-600 leading-tight">{{ number_format($activeStudentCount) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Mahasiswa Aktif</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm shrink-0">
                PA
            </div>
            <div>
                <p class="text-xl font-bold text-slate-900 leading-tight">{{ number_format($withAdvisorCount) }}</p>
                <p class="text-[11px] font-medium text-slate-500">Terhubung Dosen PA</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-sm shrink-0">
                API
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900 truncate">{{ $programOptions->count() }} Program Studi</p>
                <p class="text-[11px] text-slate-400 truncate">
                    Sync: {{ $lastSyncedAt ? \Illuminate\Support\Carbon::parse($lastSyncedAt)->diffForHumans() : 'Siap tarik API' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Card Utama: Filter & Tabel Management Mahasiswa -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
        <!-- Info Bar Integrasi REST API SIAKAD -->
        <div class="px-5 py-3 bg-gradient-to-r from-amber-50/80 via-orange-50/40 to-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2 text-slate-700">
                <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-mono font-semibold text-[10px]">SIAKAD REST API</span>
                <span>Data mahasiswa, NIM, Program Studi, dan Dosen PA ditarik otomatis menggunakan <code class="font-mono text-[11px] text-slate-900">X-SIAKAD-API-KEY</code>.</span>
            </div>
            <span class="text-[11px] text-slate-400 font-mono">{{ config('services.siakad.api_url') }}/api/external/students</span>
        </div>

        <!-- Toolbar Search & Filter (Gaya SuperApps USH) -->
        <form method="GET" action="{{ route('admin.users.students') }}" class="p-4 border-b border-slate-100 bg-slate-50/50 grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
            <div class="sm:col-span-4 relative">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Cari nama, NIM, email, atau prodi..."
                       class="w-full pl-9 pr-3 py-2 rounded-lg bg-white border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
            </div>

            <div class="sm:col-span-3">
                <select name="program_studi" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-slate-900">
                    <option value="">Semua Program Studi</option>
                    @foreach($programOptions as $prodi)
                        <option value="{{ $prodi }}" {{ $programStudi === $prodi ? 'selected' : '' }}>{{ $prodi }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <select name="angkatan" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-slate-900">
                    <option value="">Semua Angkatan</option>
                    @foreach($angkatanOptions as $thn)
                        <option value="{{ $thn }}" {{ (string) $angkatan === (string) $thn ? 'selected' : '' }}>Angkatan {{ $thn }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <select name="status_akademik" onchange="this.form.submit()"
                        class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-slate-900">
                    <option value="">Semua Status</option>
                    @foreach(['Aktif', 'Mahasiswa baru', 'Cuti', 'Lulus', 'Mengundurkan Diri'] as $st)
                        <option value="{{ $st }}" {{ $statusAkademik === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-1 flex items-center gap-1.5 justify-end">
                <button type="submit" class="w-full sm:w-auto px-3 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium transition">
                    Cari
                </button>
                @if($search || $programStudi || $angkatan || $statusAkademik)
                    <a href="{{ route('admin.users.students') }}" class="px-2.5 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 text-xs">
                        ✕
                    </a>
                @endif
            </div>
        </form>

        <!-- Tabel Data Mahasiswa (Gaya SuperApps USH) -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4 w-12">No</th>
                        <th class="py-3 px-4">Nama Mahasiswa</th>
                        <th class="py-3 px-4 w-32">NIM</th>
                        <th class="py-3 px-4 w-28">Angkatan</th>
                        <th class="py-3 px-4">Program Studi</th>
                        <th class="py-3 px-4">Dosen PA (Tujuan Tiket)</th>
                        <th class="py-3 px-4 w-28">Status</th>
                        <th class="py-3 px-4 w-20 text-center">Tiket</th>
                        <th class="py-3 px-4 w-44 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($students as $student)
                        @php
                            $stLower = strtolower($student->status_akademik ?? 'aktif');
                            $stBadge = str_contains($stLower, 'aktif') || str_contains($stLower, 'baru')
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                : (str_contains($stLower, 'cuti') ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200');
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition align-middle">
                            <td class="py-3 px-4 text-slate-400 font-medium">
                                {{ $loop->iteration + ($students->currentPage() - 1) * $students->perPage() }}
                            </td>

                            <!-- Nama & Email -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold text-[11px] flex items-center justify-center shrink-0">
                                        {{ $student->initials() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900 truncate">{{ $student->name }}</p>
                                        <p class="text-[11px] text-slate-400 truncate">{{ $student->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- NIM -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-mono font-semibold text-slate-800 bg-slate-100 px-2 py-0.5 rounded">
                                    {{ $student->identity_number ?: '-' }}
                                </span>
                            </td>

                            <!-- Angkatan & Semester -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                    {{ $student->angkatan ?: '-' }}
                                </span>
                                @if($student->semester)
                                    <span class="text-[10px] text-slate-400 ml-1">Smt {{ $student->semester }}</span>
                                @endif
                            </td>

                            <!-- Program Studi -->
                            <td class="py-3 px-4">
                                <span class="inline-block px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-indigo-50 text-indigo-700">
                                    {{ $student->department ?: '-' }}
                                </span>
                            </td>

                            <!-- Dosen PA / Pembimbing -->
                            <td class="py-3 px-4">
                                @if($student->supervisor)
                                    <span class="font-medium text-slate-800 block">{{ $student->supervisor->name }}</span>
                                    <span class="text-[10px] text-slate-400">Role: {{ $student->supervisor->role?->name ?? 'Dosen' }}</span>
                                @else
                                    <span class="text-slate-400 italic">Belum ditentukan</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $stBadge }}">
                                    {{ $student->status_akademik ?: 'Aktif' }}
                                </span>
                            </td>

                            <!-- Jumlah Tiket -->
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-semibold text-[11px]">
                                    {{ $student->created_tickets_count ?? 0 }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button"
                                            @click="openEdit({{ Js::from([
                                                'id' => $student->id,
                                                'name' => $student->name,
                                                'email' => $student->email,
                                                'identity_number' => $student->identity_number,
                                                'department' => $student->department,
                                                'angkatan' => $student->angkatan,
                                                'semester' => $student->semester,
                                                'status_akademik' => $student->status_akademik,
                                                'supervisor_id' => $student->supervisor_id,
                                                'role_id' => $student->role_id,
                                            ]) }})"
                                            class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold text-[11px] transition">
                                        Edit
                                    </button>

                                    <form action="{{ route('auth.quick-switch', $student) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Simulasikan Login sebagai Mahasiswa ini"
                                                class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-[11px] transition">
                                            Login
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.users.destroy', $student) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus data mahasiswa {{ addslashes($student->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-semibold text-[11px] transition">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 px-4 text-center text-xs text-slate-400">
                                Tidak ada data mahasiswa yang cocok. Klik tombol <strong>🔄 Sinkronisasi SIAKAD API</strong> di kanan atas untuk menarik data dari SIAKAD.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($students->hasPages())
            <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/40">
                {{ $students->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: Tambah Mahasiswa Baru -->
    <div x-show="addStudentModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="addStudentModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Tambah Mahasiswa</h3>
                <button type="button" @click="addStudentModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-3.5 text-xs">
                @csrf
                <input type="hidden" name="user_type" value="mahasiswa">
                <input type="hidden" name="role_id" value="{{ $mahasiswaRole?->id }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required placeholder="Nama Mahasiswa"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">NIM</label>
                        <input type="text" name="identity_number" required placeholder="Contoh: 062201001"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" required placeholder="nim@mhs.sugenghartono.ac.id"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Program Studi</label>
                        <input type="text" name="department" required placeholder="Contoh: Informatika"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Angkatan</label>
                        <input type="number" name="angkatan" value="2025"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Semester</label>
                        <input type="number" name="semester" value="1" min="1" max="14"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status_akademik" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white">
                            <option value="Aktif">Aktif</option>
                            <option value="Cuti">Cuti</option>
                            <option value="Lulus">Lulus</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Dosen PA / Pembimbing Akademik</label>
                    <select name="supervisor_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white">
                        <option value="">— Pilih Dosen PA —</option>
                        @foreach($lecturers as $lec)
                            <option value="{{ $lec->id }}">{{ $lec->name }} ({{ $lec->department ?? $lec->role?->name }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addStudentModal = false" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white font-semibold">Simpan Mahasiswa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Mahasiswa & Dosen PA -->
    <div x-show="editStudentModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="editStudentModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Edit Data Mahasiswa & Dosen PA</h3>
                <button type="button" @click="editStudentModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form :action="'{{ url('/admin/users') }}/' + editData.id" method="POST" class="p-6 space-y-3.5 text-xs">
                @csrf
                @method('PUT')
                <input type="hidden" name="role_id" :value="editData.role_id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" x-model="editData.name" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">NIM</label>
                        <input type="text" name="identity_number" x-model="editData.identity_number" required
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
                        <label class="block font-semibold text-slate-700 mb-1">Program Studi</label>
                        <input type="text" name="department" x-model="editData.department"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Angkatan</label>
                        <input type="number" name="angkatan" x-model="editData.angkatan"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Semester</label>
                        <input type="number" name="semester" x-model="editData.semester"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status_akademik" x-model="editData.status_akademik" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white">
                            <option value="Aktif">Aktif</option>
                            <option value="Mahasiswa baru">Mahasiswa baru</option>
                            <option value="Cuti">Cuti</option>
                            <option value="Lulus">Lulus</option>
                            <option value="Mengundurkan Diri">Mengundurkan Diri</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Dosen PA / Pembimbing Akademik (Penerima Tiket Awal)</label>
                    <select name="supervisor_id" x-model="editData.supervisor_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white">
                        <option value="">— Pilih Dosen PA —</option>
                        @foreach($lecturers as $lec)
                            <option value="{{ $lec->id }}">{{ $lec->name }} ({{ $lec->department ?? $lec->role?->name }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editStudentModal = false" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white font-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
