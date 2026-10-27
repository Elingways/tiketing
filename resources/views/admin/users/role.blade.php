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
        'fuchsia' => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
    ];
    $activeBadgeColor = $badgeColors[$role->badge_color] ?? $badgeColors['indigo'];
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
        status_akademik: 'Aktif',
        is_role_head: false
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
            status_akademik: u.status_akademik || 'Aktif',
            is_role_head: {{ $role->head_user_id ? 'u.id === ' . $role->head_user_id : 'false' }}
        };
        this.editUserModal = true;
    }
}">

    <!-- Top Header: Role Details, Switcher & Quick Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5">
        <div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="p-2 rounded-xl bg-white border border-slate-200 shadow-2xs text-indigo-600">
                    @include('partials.icon', ['name' => $role->icon ?: 'user-group', 'class' => 'w-5 h-5'])
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-bold text-slate-900">{{ $role->name }}</h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $activeBadgeColor }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            Unit Divisi
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ $role->description ?: 'Kelola seluruh personel, Kepala Unit, status akun, dan reset kredensial password untuk role ini.' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.roles') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Kelola di Manajemen Divisi</span>
            </a>

            <button type="button" @click="addUserModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition cursor-pointer">
                <span>+ Tambah Pengguna</span>
            </button>
        </div>
    </div>

    <!-- Quick Role Pill Tabs (Navigasi Antar Role Cepat) -->
    @if($allRoles->count() > 1)
        <div class="mb-5 overflow-x-auto pb-1">
            <div class="inline-flex p-1 rounded-xl bg-white border border-slate-200/90 shadow-2xs gap-1">
                @foreach($allRoles as $r)
                    @php
                        $isCurrent = $r->id === $role->id;
                    @endphp
                    <a href="{{ route('admin.users.role', $r->slug) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition {{ $isCurrent ? 'bg-slate-900 text-white font-semibold shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                        <span>{{ $r->name }}</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded-full font-bold {{ $isCurrent ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                            {{ $r->users_count }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 3 KPI Mini Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <div class="bg-white rounded-xl p-4 border border-slate-200/90 shadow-2xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0 border border-indigo-100">
                {{ $totalUsers }}
            </div>
            <div>
                <p class="text-lg font-bold text-slate-900 leading-tight">{{ $totalUsers }} Personel</p>
                <p class="text-[11px] font-medium text-slate-500">Total Pengguna Terdaftar</p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200/90 shadow-2xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-sm shrink-0 border border-amber-100">
                👑
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900 truncate">
                    {{ $headUser ? $headUser->name : 'Belum Ditentukan' }}
                </p>
                <p class="text-[11px] font-medium text-slate-500 truncate">
                    {{ $headUser ? $headUser->email : 'Kepala Unit Belum Dipilih' }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200/90 shadow-2xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-sm shrink-0 border border-emerald-100">
                {{ $totalActiveStaff }}
            </div>
            <div>
                <p class="text-lg font-bold text-slate-900 leading-tight">{{ $totalActiveStaff }} Aktif</p>
                <p class="text-[11px] font-medium text-slate-500">Status Akun Aktif</p>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-3.5 mb-4">
        <form method="GET" action="{{ route('admin.users.role', $role->slug) }}" class="flex flex-col md:flex-row items-center justify-between gap-3">
            <div class="w-full md:w-80 relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, email, jabatan, atau keahlian..."
                       class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto flex-wrap">
                <select name="position" onchange="this.form.submit()"
                        class="px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    <option value="">Semua Posisi</option>
                    <option value="head" {{ $positionFilter === 'head' ? 'selected' : '' }}>👑 Kepala Unit Saja</option>
                    <option value="staff" {{ $positionFilter === 'staff' ? 'selected' : '' }}>Staf Pelaksana Saja</option>
                </select>

                <select name="status" onchange="this.form.submit()"
                        class="px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    <option value="">Semua Status Akun</option>
                    <option value="Aktif" {{ $statusFilter === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="Nonaktif" {{ $statusFilter === 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>

                @if($search || $statusFilter || $positionFilter)
                    <a href="{{ route('admin.users.role', $role->slug) }}"
                       class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">
                        Reset Filter
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Main Table: Users in this Role -->
    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                        <th class="py-3 px-4">Pengguna</th>
                        <th class="py-3 px-4">Posisi & Jabatan</th>
                        <th class="py-3 px-4">Keahlian Teknis</th>
                        <th class="py-3 px-4">Beban Kerja Tiket</th>
                        <th class="py-3 px-4">Status Akun</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        @php
                            $isHead = (int) $role->head_user_id === (int) $user->id;
                            $userPayload = [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'jabatan' => $user->jabatan,
                                'keahlian' => $user->keahlian,
                                'status_akademik' => $user->status_akademik,
                                'initials' => $user->initials(),
                                'avatar_url' => $user->avatar_url,
                                'is_head' => $isHead,
                                'active_tickets_count' => $user->active_tickets_count,
                                'completed_tickets_count' => $user->completed_tickets_count,
                                'created_tickets_count' => $user->created_tickets_count,
                                'created_at_formatted' => $user->created_at ? $user->created_at->format('d M Y') : '-',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Column 1: Pengguna (Avatar, Name, Email) -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative shrink-0">
                                        @if($user->avatar_url)
                                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-xl object-cover ring-1 ring-slate-200">
                                        @else
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white font-bold text-xs flex items-center justify-center shadow-2xs">
                                                {{ $user->initials() }}
                                            </div>
                                        @endif
                                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white {{ $user->status_akademik === 'Aktif' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-900 truncate">{{ $user->name }}</p>
                                        <p class="text-[11px] text-slate-500 font-mono truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Column 2: Posisi & Jabatan -->
                            <td class="py-3 px-4">
                                <div class="space-y-1">
                                    @if($isHead)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                                            <span>👑</span>
                                            <span>Kepala Unit</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            <span>Staf Pelaksana</span>
                                        </span>
                                    @endif
                                    <p class="text-[11px] text-slate-600 font-medium">{{ $user->jabatan ?: 'Staf ' . $role->name }}</p>
                                </div>
                            </td>

                            <!-- Column 3: Keahlian Teknis -->
                            <td class="py-3 px-4">
                                @if($user->keahlian)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(explode(',', $user->keahlian) as $skill)
                                            @if(trim($skill) !== '')
                                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                    {{ trim($skill) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Belum diisi</span>
                                @endif
                            </td>

                            <!-- Column 4: Beban Tiket -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold {{ $user->active_tickets_count > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-50 text-slate-600 border border-slate-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $user->active_tickets_count > 0 ? 'bg-amber-500' : 'bg-slate-400' }}"></span>
                                        {{ $user->active_tickets_count }} aktif
                                    </span>
                                    <span class="text-[11px] text-slate-400 font-medium">({{ $user->completed_tickets_count }} selesai)</span>
                                </div>
                            </td>

                            <!-- Column 5: Status Akun -->
                            <td class="py-3 px-4">
                                @if($user->status_akademik === 'Aktif')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        {{ $user->status_akademik ?: 'Nonaktif' }}
                                    </span>
                                @endif
                            </td>

                            <!-- Column 6: Aksi (Detail Profil, Reset Password, Edit, Hapus) -->
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <!-- Tombol Detail Profil -->
                                    <button type="button"
                                            @click="openProfile({{ Js::from($userPayload) }})"
                                            title="Lihat Detail Profil"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <!-- Tombol Reset Password -->
                                    <button type="button"
                                            @click="openResetPassword({{ Js::from($userPayload) }})"
                                            title="Reset Password Akun Ini"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 border border-slate-200 hover:border-amber-200 transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                        </svg>
                                    </button>

                                    <!-- Tombol Edit -->
                                    <button type="button"
                                            @click="openEdit({{ Js::from($userPayload) }})"
                                            title="Edit Data Pengguna"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna \'{{ $user->name }}\'? Akun ini tidak dapat login lagi.')"
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus Akun Pengguna"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 transition cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="max-w-xs mx-auto text-center space-y-2">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                                        👥
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">Belum Ada Pengguna di Role Ini</p>
                                    <p class="text-xs text-slate-500">Klik tombol "Tambah Pengguna" di atas untuk mendaftarkan Kepala Unit atau staf pelaksana.</p>
                                    <button type="button" @click="addUserModal = true"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition cursor-pointer">
                                        <span>+ Tambah Pengguna Sekarang</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: Detail Profil Pengguna -->
    <div x-show="profileModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div @click.away="profileModal = false" class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header with Gradient Banner -->
            <div class="h-24 bg-gradient-to-r from-indigo-600 via-indigo-700 to-indigo-800 relative p-4 flex items-start justify-end">
                <button type="button" @click="profileModal = false"
                        class="p-1 rounded-lg bg-black/20 hover:bg-black/40 text-white/90 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Profile Content -->
            <div class="px-5 pb-5 pt-0 -mt-10 relative space-y-4">
                <div class="flex items-end justify-between">
                    <div class="relative">
                        <template x-if="activeUser && activeUser.avatar_url">
                            <img :src="activeUser.avatar_url" :alt="activeUser.name" class="w-20 h-20 rounded-2xl object-cover ring-4 ring-white shadow-md bg-white">
                        </template>
                        <template x-if="activeUser && !activeUser.avatar_url">
                            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white font-bold text-2xl flex items-center justify-center ring-4 ring-white shadow-md"
                                 x-text="activeUser ? activeUser.initials : 'U'">
                            </div>
                        </template>
                        <span class="absolute bottom-1 right-1 w-3.5 h-3.5 rounded-full border-2 border-white bg-emerald-500" title="Aktif"></span>
                    </div>

                    <template x-if="activeUser && activeUser.is_head">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                            <span>👑</span>
                            <span>Kepala Unit</span>
                        </span>
                    </template>
                    <template x-if="activeUser && !activeUser.is_head">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            <span>Staf Pelaksana</span>
                        </span>
                    </template>
                </div>

                <div>
                    <h3 class="text-base font-bold text-slate-900" x-text="activeUser ? activeUser.name : ''"></h3>
                    <p class="text-xs text-slate-500 font-mono" x-text="activeUser ? activeUser.email : ''"></p>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                    <div>
                        <span class="text-[11px] text-slate-400 block font-medium">Role & Divisi:</span>
                        <span class="font-bold text-slate-800">{{ $role->name }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] text-slate-400 block font-medium">Jabatan:</span>
                        <span class="font-bold text-slate-800" x-text="activeUser ? (activeUser.jabatan || 'Staf') : '-'"></span>
                    </div>
                    <div>
                        <span class="text-[11px] text-slate-400 block font-medium">Status Akun:</span>
                        <span class="font-bold text-emerald-600" x-text="activeUser ? (activeUser.status_akademik || 'Aktif') : 'Aktif'"></span>
                    </div>
                    <div>
                        <span class="text-[11px] text-slate-400 block font-medium">Terdaftar Sejak:</span>
                        <span class="font-bold text-slate-800" x-text="activeUser ? activeUser.created_at_formatted : '-'"></span>
                    </div>
                </div>

                <!-- Keahlian -->
                <div>
                    <span class="text-[11px] font-bold text-slate-700 block mb-1">Spesialisasi & Keahlian:</span>
                    <p class="text-xs text-slate-600 p-2.5 rounded-lg bg-slate-50 border border-slate-100"
                       x-text="activeUser && activeUser.keahlian ? activeUser.keahlian : 'Belum ada spesialisasi keahlian yang tercatat.'"></p>
                </div>

                <!-- Statistik Beban Kerja -->
                <div>
                    <span class="text-[11px] font-bold text-slate-700 block mb-1.5">Ringkasan Beban Kerja Tiket:</span>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-100">
                            <p class="text-base font-bold text-amber-700" x-text="activeUser ? activeUser.active_tickets_count : '0'"></p>
                            <p class="text-[10px] text-amber-600 font-medium">Tiket Aktif</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-100">
                            <p class="text-base font-bold text-emerald-700" x-text="activeUser ? activeUser.completed_tickets_count : '0'"></p>
                            <p class="text-[10px] text-emerald-600 font-medium">Selesai</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-indigo-50/70 border border-indigo-100">
                            <p class="text-base font-bold text-indigo-700" x-text="activeUser ? activeUser.created_tickets_count : '0'"></p>
                            <p class="text-[10px] text-indigo-600 font-medium">Dibuat</p>
                        </div>
                    </div>
                </div>

                <!-- Action inside Profile Modal -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <button type="button"
                            @click="profileModal = false; openResetPassword(activeUser)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-semibold border border-amber-200 transition cursor-pointer">
                        <span>🔑</span>
                        <span>Reset Password</span>
                    </button>

                    <button type="button" @click="profileModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Reset Password Pengguna -->
    <div x-show="resetPasswordModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div @click.away="resetPasswordModal = false" class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm border border-amber-200">
                        🔑
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Reset Password Pengguna</h3>
                        <p class="text-[11px] text-slate-500">Atur ulang kata sandi login pengguna terpilih</p>
                    </div>
                </div>
                <button type="button" @click="resetPasswordModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="resetUser ? `{{ url('admin/users') }}/${resetUser.id}/reset-password` : '#'" method="POST" class="p-5 space-y-4">
                @csrf
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-3">
                    <template x-if="resetUser && resetUser.avatar_url">
                        <img :src="resetUser.avatar_url" class="w-10 h-10 rounded-lg object-cover ring-1 ring-slate-200">
                    </template>
                    <template x-if="resetUser && !resetUser.avatar_url">
                        <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white font-bold text-xs flex items-center justify-center"
                             x-text="resetUser ? resetUser.initials : 'U'"></div>
                    </template>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-900 truncate" x-text="resetUser ? resetUser.name : ''"></p>
                        <p class="text-[11px] text-slate-500 font-mono truncate" x-text="resetUser ? resetUser.email : ''"></p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-700">Password Baru</label>
                    <input type="text" name="password" x-model="resetPasswordInput" placeholder="Kosongkan untuk default: password"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-600 font-mono">
                    <p class="text-[11px] text-slate-500">
                        💡 Kosongkan kolom jika ingin mereset ke password standar bawaan: <code class="font-bold bg-slate-100 px-1 py-0.5 rounded text-amber-800">password</code>.
                    </p>
                </div>

                <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-[11px] text-amber-900 flex items-start gap-2">
                    <span class="text-amber-600 font-bold shrink-0">⚠️</span>
                    <span>Pengguna dapat langsung menggunakan password ini saat login. Pastikan Anda menginformasikan password ini kepada yang bersangkutan.</span>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" @click="resetPasswordModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-sm shadow-amber-600/20 transition cursor-pointer">
                        Konfirmasi Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Tambah Pengguna Baru ke Role Ini -->
    <div x-show="addUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div @click.away="addUserModal = false" class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Tambah Pengguna Baru</h3>
                    <p class="text-[11px] text-slate-500">Daftarkan personel baru ke dalam role <strong>{{ $role->name }}</strong></p>
                </div>
                <button type="button" @click="addUserModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" class="p-5 space-y-3.5">
                @csrf
                <input type="hidden" name="role_id" value="{{ $role->id }}">

                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Budi Santoso, S.Kom."
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Email Login <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" required placeholder="user@kampus.ac.id"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Password</label>
                        <input type="text" name="password" placeholder="Default: password"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Jabatan</label>
                        <input type="text" name="jabatan" placeholder="Contoh: Staff IT Support"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Status Akun</label>
                        <select name="status_akademik"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-white">
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Spesialisasi / Keahlian Teknis</label>
                    <input type="text" name="keahlian" placeholder="Contoh: Networking, Hardware, Troubleshooting (pisahkan dengan koma)"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                </div>

                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-slate-50/70 text-slate-800 text-xs cursor-pointer">
                    <input type="checkbox" name="is_role_head" value="1" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500/20">
                    <div class="space-y-0.5">
                        <span class="font-bold block">Tetapkan sebagai Kepala Unit {{ $role->name }}</span>
                        <span class="text-[11px] text-slate-500 block">Menjadi penanggung jawab utama penerima disposisi tiket untuk role ini</span>
                    </div>
                </label>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" @click="addUserModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition cursor-pointer">
                        Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Edit Pengguna -->
    <div x-show="editUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div @click.away="editUserModal = false" class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Edit Data Pengguna</h3>
                    <p class="text-[11px] text-slate-500">Perbarui profil dan status penugasan pengguna</p>
                </div>
                <button type="button" @click="editUserModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="`{{ url('admin/users') }}/${editUserData.id}`" method="POST" class="p-5 space-y-3.5">
                @csrf
                @method('PUT')
                <input type="hidden" name="role_id" value="{{ $role->id }}">

                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editUserData.name" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" x-model="editUserData.email" required
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-700">Status Akun</label>
                        <select name="status_akademik" x-model="editUserData.status_akademik"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 bg-white">
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Jabatan</label>
                    <input type="text" name="jabatan" x-model="editUserData.jabatan"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-slate-700">Spesialisasi / Keahlian Teknis</label>
                    <input type="text" name="keahlian" x-model="editUserData.keahlian" placeholder="Contoh: Hardware, Jaringan"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                </div>

                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-slate-50/70 text-slate-800 text-xs cursor-pointer">
                    <input type="checkbox" name="is_role_head" value="1" x-model="editUserData.is_role_head" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500/20">
                    <div class="space-y-0.5">
                        <span class="font-bold block">Tetapkan sebagai Kepala Unit {{ $role->name }}</span>
                        <span class="text-[11px] text-slate-500 block">Jadikan pengguna ini sebagai Kepala Unit peninjau tiket</span>
                    </div>
                </label>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" @click="editUserModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
