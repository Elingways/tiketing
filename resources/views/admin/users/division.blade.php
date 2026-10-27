@extends('layouts.app')

@section('content')
@php
    $badgeColors = [
        'indigo' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
        'violet' => 'bg-violet-50 text-violet-700 border-violet-200',
        'sky' => 'bg-sky-50 text-sky-700 border-sky-200',
        'teal' => 'bg-teal-50 text-teal-700 border-teal-200',
    ];
    $activeBadgeColor = $badgeColors[$division->badge_color] ?? $badgeColors['indigo'];
@endphp

<div x-data="{
    addUserModal: false,
    editUserModal: false,
    profileModal: false,
    resetPasswordModal: false,
    activeUser: null,
    resetUser: null,
    resetPasswordInput: '',
    editUserData: {
        id: null,
        name: '',
        email: '',
        jabatan: '',
        keahlian: '',
        status_akademik: 'Aktif'
    },
    openProfile(u) {
        this.activeUser = u;
        this.profileModal = true;
    },
    openResetPassword(u) {
        this.resetUser = u;
        this.resetPasswordInput = '';
        this.resetPasswordModal = true;
    },
    openEdit(u) {
        this.editUserData = {
            id: u.id,
            name: u.name,
            email: u.email,
            jabatan: u.jabatan || '',
            keahlian: u.keahlian || '',
            status_akademik: u.status_akademik || 'Aktif'
        };
        this.editUserModal = true;
    }
}">

    <!-- Top Header: Division Info, Switcher & Quick Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                    @include('partials.icon', ['name' => 'building-office', 'class' => 'w-5 h-5'])
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-bold text-slate-900">{{ $division->name }}</h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $activeBadgeColor }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            Divisi Kampus
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $division->description ?: 'Kelola seluruh personel, akun pengguna, dan hak akses penanganan tiket pada divisi ini.' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.roles') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition">
                <span>Manajemen Divisi</span>
            </a>

            <button type="button" @click="addUserModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition cursor-pointer">
                <span>+ Tambah Pengguna</span>
            </button>
        </div>
    </div>

    <!-- Quick Division Tabs (Navigasi Antar Divisi) -->
    @if($allDivisions->count() > 1)
        <div class="mb-5 overflow-x-auto pb-1">
            <div class="inline-flex p-1 rounded-xl bg-white border border-slate-200 shadow-2xs gap-1">
                @foreach($allDivisions as $divItem)
                    <a href="{{ route('admin.users.division', $divItem->slug) }}"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-2 whitespace-nowrap {{ $divItem->id === $division->id ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        <span>{{ $divItem->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($divisionRoles->isEmpty())
        <div class="mb-5 p-4 rounded-2xl bg-amber-50/90 border border-amber-200 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    @include('partials.icon', ['name' => 'information-circle', 'class' => 'w-5 h-5'])
                </div>
                <div>
                    <h4 class="text-xs font-bold text-amber-900">Divisi {{ $division->name }} Belum Memiliki Role / Jabatan</h4>
                    <p class="text-[11px] text-amber-700 mt-0.5">Tambahkan role (Kepala, SPV, atau Staf) terlebih dahulu di Manajemen Divisi agar akun dapat dihubungkan ke role tersebut.</p>
                </div>
            </div>
            <a href="{{ route('admin.roles') }}" class="px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs transition shrink-0 whitespace-nowrap text-center">
                + Buat Role di Manajemen Divisi
            </a>
        </div>
    @endif

    <!-- 3 Quick KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Personel Divisi</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $totalUsers }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Pengguna terdaftar di {{ $division->name }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kepala Divisi</p>
            <p class="text-base font-bold text-slate-900 mt-2 truncate">{{ $headUser?->name ?? 'Belum ditentukan' }}</p>
            <p class="text-[11px] text-slate-400 truncate">{{ $headUser?->email ?? '—' }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-2xs">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Personel Aktif</p>
            <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $totalActiveStaff }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Status akun aktif operasional</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-4 mb-5">
        <form method="GET" action="{{ route('admin.users.division', $division->slug) }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-5 relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, email, jabatan, atau keahlian..."
                       class="w-full pl-9 pr-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <div class="sm:col-span-3">
                <select name="role_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                    <option value="">Semua Role Divisi</option>
                    @foreach($divisionRoles as $r)
                        <option value="{{ $r->id }}" {{ (string) $roleFilter === (string) $r->id ? 'selected' : '' }}>
                            {{ $r->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                    <option value="">Semua Status</option>
                    <option value="Aktif" {{ $statusFilter === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="Nonaktif" {{ $statusFilter === 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                    Filter
                </button>
                @if($search || $statusFilter || $roleFilter)
                    <a href="{{ route('admin.users.division', $division->slug) }}" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500 text-xs font-semibold" title="Reset">
                        &times;
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Table Pengguna -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-5">Nama & Identitas</th>
                        <th class="py-3 px-4">Role / Posisi</th>
                        <th class="py-3 px-4">Status Akun</th>
                        <th class="py-3 px-4">Tiket Ditugaskan</th>
                        <th class="py-3 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50/60 transition align-middle">
                            <!-- Nama & Identitas -->
                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-xs text-slate-700 shrink-0">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-900">{{ $u->name }}</span>
                                            @if($headUser && $u->id === $headUser->id)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">Kepala</span>
                                            @elseif($spvUser && $u->id === $spvUser->id)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-800 border border-sky-200">SPV</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Role / Posisi -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $u->role?->name ?? 'Belum ada role' }}
                                </span>
                                @if($u->keahlian)
                                    <span class="block text-[10px] text-slate-400 mt-0.5">{{ $u->keahlian }}</span>
                                @endif
                            </td>

                            <!-- Status Akun -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold {{ $u->status_akademik === 'Aktif' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $u->status_akademik ?? 'Aktif' }}
                                </span>
                            </td>

                            <!-- Tiket Ditugaskan -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-semibold text-slate-800">{{ $u->active_tickets_count }}</span>
                                <span class="text-slate-400 text-[11px]">aktif</span>
                                <span class="text-slate-300 mx-1">/</span>
                                <span class="text-slate-500 text-[11px]">{{ $u->completed_tickets_count }} selesai</span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" @click="openProfile({{ Js::from($u) }})"
                                            class="px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition cursor-pointer">
                                        Profil
                                    </button>
                                    <button type="button" @click="openEdit({{ Js::from($u) }})"
                                            class="px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-indigo-50 hover:text-indigo-700 border border-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer">
                                        Edit
                                    </button>
                                    <button type="button" @click="openResetPassword({{ Js::from($u) }})"
                                            class="px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-amber-50 hover:text-amber-800 border border-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer">
                                        Reset
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-xs text-slate-400">
                                @if($divisionRoles->isEmpty())
                                    Divisi ini belum memiliki role/jabatan terdaftar. Silakan <a href="{{ route('admin.roles') }}" class="text-indigo-600 font-semibold underline">buat role di Manajemen Divisi</a> terlebih dahulu.
                                @else
                                    Belum ada personel terdaftar pada divisi ini. Klik <strong>+ Tambah Pengguna</strong> untuk mendaftarkan akun.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL: Tambah Pengguna ke Divisi --}}
    <div x-show="addUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="addUserModal = false" class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900">Tambah Pengguna ke {{ $division->name }}</h3>
                <button type="button" @click="addUserModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="department" value="{{ $division->name }}">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Nama lengkap staf"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" required placeholder="email@kampus.ac.id"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password Awal</label>
                    <input type="text" name="password" value="password" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Role di Divisi <span class="text-rose-500">*</span></label>
                    @if($divisionRoles->isEmpty())
                        <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-2">
                            <p class="font-medium text-amber-900">Divisi <strong>{{ $division->name }}</strong> belum memiliki role/jabatan terdaftar.</p>
                            <p class="text-[11px] text-amber-700">Buat role pimpinan (Kepala/SPV) atau role staf di Manajemen Divisi terlebih dahulu agar akun pengguna dapat dihubungkan ke role tersebut.</p>
                            <a href="{{ route('admin.roles') }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs transition">
                                <span>+ Buka Manajemen Divisi</span>
                            </a>
                        </div>
                    @else
                        <select name="role_id" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                            <option value="">Pilih Role</option>
                            @foreach($divisionRoles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Keahlian / Spesialisasi (Opsional)</label>
                    <input type="text" name="keahlian" placeholder="Contoh: Jaringan, Database, Hardware"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addUserModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Batal</button>
                    <button type="submit" @if($divisionRoles->isEmpty()) disabled @endif
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-semibold transition">
                        Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Edit Pengguna --}}
    <div x-show="editUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="editUserModal = false" class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900">Edit Akun Pengguna</h3>
                <button type="button" @click="editUserModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <form :action="'/admin/users/' + editUserData.id" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" x-model="editUserData.name" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" x-model="editUserData.email" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Keahlian</label>
                    <input type="text" name="keahlian" x-model="editUserData.keahlian" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status Akun</label>
                    <select name="status_akademik" x-model="editUserData.status_akademik" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editUserModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Reset Password --}}
    <div x-show="resetPasswordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="resetPasswordModal = false" class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-sm w-full p-6 space-y-4">
            <h3 class="text-sm font-bold text-slate-900">Reset Password</h3>
            <p class="text-xs text-slate-500" x-text="'Reset password untuk pengguna ' + (resetUser ? resetUser.name : '')"></p>
            <form :action="'/admin/users/' + (resetUser ? resetUser.id : '') + '/reset-password'" method="POST" class="space-y-3">
                @csrf
                <input type="password" name="password" x-model="resetPasswordInput" placeholder="Password baru (min. 6 karakter)" required class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="resetPasswordModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 text-white text-xs font-semibold">Reset Password</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: Profil Singkat --}}
    <div x-show="profileModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="profileModal = false" class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-bold text-slate-900">Detail Personel</h3>
                <button type="button" @click="profileModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <div class="space-y-2 text-xs" x-show="activeUser">
                <div><span class="font-semibold text-slate-500">Nama:</span> <span class="font-bold text-slate-900" x-text="activeUser?.name"></span></div>
                <div><span class="font-semibold text-slate-500">Email:</span> <span class="text-slate-900" x-text="activeUser?.email"></span></div>
                <div><span class="font-semibold text-slate-500">Role:</span> <span class="text-slate-900" x-text="activeUser?.role?.name || '-'"></span></div>
                <div><span class="font-semibold text-slate-500">Keahlian:</span> <span class="text-slate-900" x-text="activeUser?.keahlian || '-'"></span></div>
                <div><span class="font-semibold text-slate-500">Status:</span> <span class="text-slate-900" x-text="activeUser?.status_akademik || 'Aktif'"></span></div>
            </div>
            <div class="pt-2 flex justify-end">
                <button type="button" @click="profileModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-xs font-semibold text-slate-700">Tutup</button>
            </div>
        </div>
    </div>

</div>
@endsection
