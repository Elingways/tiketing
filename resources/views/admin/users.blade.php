@extends('layouts.app')

@section('content')
<div class="mb-6 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
    <span class="px-3 py-1 rounded-full bg-fuchsia-50 text-fuchsia-700 font-extrabold text-xs border border-fuchsia-200">
        User & Role Assignment
    </span>
    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2">
        Manajemen Pengguna & Penempatan Role Hirarki
    </h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
        Tambah pengguna baru untuk Role apa pun (termasuk Role baru yang baru saja Anda buat di Manajemen Hirarki) dan hubungkan pembimbing/atasannya.
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Tambah Pengguna Baru -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-extrabold text-slate-900 mb-1">+ Tambah Akun Pengguna</h2>
            <p class="text-xs text-slate-500 mb-4">Pengguna baru otomatis muncul di menu Simulasi Pindah Role di kanan atas.</p>

            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap & Gelar</label>
                    <input type="text" name="name" required placeholder="Contoh: Hendra Pratama, S.Kom."
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" required placeholder="nama@kampus.ac.id"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">NIM / NIDN / NIP</label>
                        <input type="text" name="identity_number" placeholder="NIM / NIDN"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Password</label>
                        <input type="text" name="password" value="password" required
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Program Studi / Unit</label>
                    <input type="text" name="department" value="S1 Teknik Informatika"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Role dalam Hirarki</label>
                    <select name="role_id" required class="w-full px-3.5 py-2 rounded-xl border border-indigo-200 text-xs bg-white font-bold">
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">
                                Role: {{ $r->name }} ({{ $r->parent ? 'Hirarki → ' . $r->parent->name : 'Root' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Atasan / Pembimbing Langsung (Opsional)</label>
                    <select name="supervisor_id" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                        <option value="">— Ikuti Default Role Atasan —</option>
                        @foreach($users as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->role?->name }})</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-xs font-extrabold shadow-md transition">
                    + Buat Akun Pengguna
                </button>
            </form>
        </div>
    </div>

    <!-- Daftar Pengguna & Ganti Role Cepat -->
    <div class="lg:col-span-8 space-y-3.5">
        @foreach($users as $u)
            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
                <form action="{{ route('admin.users.update', $u) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-xs">
                                {{ $u->initials() }}
                            </div>
                            <div>
                                <p class="font-bold text-sm text-slate-900">{{ $u->name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $u->email }} •
                                    <span class="text-indigo-600 font-bold">Role: {{ $u->role?->name }}</span>
                                    @if($u->role?->parent)
                                        (Hirarki → {{ $u->role->parent->name }})
                                    @endif
                                </p>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono bg-slate-100 px-2.5 py-1 rounded-lg text-slate-600">{{ $u->identity_number }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end">
                        <div class="sm:col-span-4">
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Nama</label>
                            <input type="text" name="name" value="{{ $u->name }}" required class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs">
                            <input type="hidden" name="email" value="{{ $u->email }}">
                            <input type="hidden" name="identity_number" value="{{ $u->identity_number }}">
                            <input type="hidden" name="department" value="{{ $u->department }}">
                        </div>
                        <div class="sm:col-span-4">
                            <label class="block text-[10px] font-bold text-slate-500 mb-1">Role Hirarki</label>
                            <select name="role_id" class="w-full px-3 py-1.5 rounded-xl border border-indigo-200 text-xs bg-white font-semibold">
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ $u->role_id === $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-4 flex gap-2">
                            <div class="flex-1">
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Pembimbing/Atasan</label>
                                <select name="supervisor_id" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs bg-white">
                                    <option value="">Default Hirarki</option>
                                    @foreach($users as $sup)
                                        @if($sup->id !== $u->id)
                                            <option value="{{ $sup->id }}" {{ $u->supervisor_id === $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shrink-0">
                                Simpan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
