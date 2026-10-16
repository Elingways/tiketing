@extends('layouts.app')

@section('content')
<div class="mb-5">
    <h1 class="text-lg font-bold text-slate-900">Manajemen Pengguna</h1>
    <p class="text-xs text-slate-500">Kelola pengguna dan penempatan role dalam hirarki.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Tambah Pengguna -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <h2 class="text-sm font-bold text-slate-900 mb-3">Tambah Pengguna</h2>
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs">
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">NIM/NIDN</label>
                        <input type="text" name="identity_number" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Password</label>
                        <input type="text" name="password" value="password" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs">
                    </div>
                </div>
                <input type="hidden" name="department" value="Teknik Informatika">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Role</label>
                    <select name="role_id" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Pembimbing / Atasan</label>
                    <select name="supervisor_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
                        <option value="">— Ikuti Hirarki Role —</option>
                        @foreach($users as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->role?->name }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold">
                    Simpan Pengguna
                </button>
            </form>
        </div>
    </div>

    <!-- Daftar Pengguna -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
            @foreach($users as $u)
                <div class="p-4">
                    <form action="{{ route('admin.users.update', $u) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="email" value="{{ $u->email }}">
                        <input type="hidden" name="identity_number" value="{{ $u->identity_number }}">
                        <input type="hidden" name="department" value="{{ $u->department }}">

                        <div class="sm:col-span-4">
                            <input type="text" name="name" value="{{ $u->name }}" required
                                   class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-medium">
                            <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $u->email }}</p>
                        </div>
                        <div class="sm:col-span-3">
                            <select name="role_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs bg-white">
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ $u->role_id === $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <select name="supervisor_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs bg-white">
                                <option value="">Default Hirarki</option>
                                @foreach($users as $sup)
                                    @if($sup->id !== $u->id)
                                        <option value="{{ $sup->id }}" {{ $u->supervisor_id === $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2 flex justify-end">
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
