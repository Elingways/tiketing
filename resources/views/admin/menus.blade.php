@extends('layouts.app')

@section('content')
<div class="mb-5">
    <h1 class="text-lg font-bold text-slate-900">Manajemen Menu & Hak Akses Role</h1>
    <p class="text-xs text-slate-500">Atur visibilitas menu untuk setiap role di Sidebar dan Dashboard.</p>
</div>

<!-- Matriks Hak Akses Menu per Role -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-6">
    <form action="{{ route('admin.menus.matrix') }}" method="POST">
        @csrf
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Hak Akses Menu Berdasarkan Role</h2>
            <button type="submit" class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition">
                Simpan Hak Akses
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60 text-slate-500 font-semibold">
                        <th class="py-2.5 px-4">Menu</th>
                        <th class="py-2.5 px-3">Route</th>
                        @foreach($roles as $role)
                            <th class="py-2.5 px-3 text-center">{{ $role->name }}</th>
                        @endforeach
                        <th class="py-2.5 px-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($menus as $menu)
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-900">{{ $menu->name }}</td>
                            <td class="py-3 px-3 font-mono text-[11px] text-slate-500">{{ $menu->route_name }}</td>
                            @foreach($roles as $role)
                                <td class="py-3 px-3 text-center">
                                    <input type="checkbox"
                                           name="matrix[{{ $menu->id }}][]"
                                           value="{{ $role->id }}"
                                           {{ $menu->roles->contains('id', $role->id) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded text-indigo-600 cursor-pointer">
                                </td>
                            @endforeach
                            <td class="py-3 px-3 text-right">
                                @if(!in_array($menu->slug, ['dashboard', 'admin-hierarchy', 'admin-menus']))
                                    <button type="submit" form="del-menu-{{ $menu->id }}" class="text-rose-600 hover:underline">Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>

    @foreach($menus as $menu)
        @if(!in_array($menu->slug, ['dashboard', 'admin-hierarchy', 'admin-menus']))
            <form id="del-menu-{{ $menu->id }}" action="{{ route('admin.menus.destroy', $menu) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endforeach
</div>

<!-- Tambah Menu Baru -->
<div class="bg-white rounded-xl p-5 border border-slate-200 max-w-xl">
    <h2 class="text-sm font-bold text-slate-900 mb-3">Tambah Menu Baru</h2>
    <form action="{{ route('admin.menus.store') }}" method="POST" class="space-y-3">
        @csrf
        <input type="hidden" name="color" value="indigo">
        <input type="hidden" name="show_in_sidebar" value="1">
        <input type="hidden" name="show_in_launcher" value="1">
        <input type="hidden" name="is_active" value="1">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Nama Menu</label>
                <input type="text" name="name" required placeholder="Contoh: Laporan Tiket"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Route Target</label>
                <select name="route_name" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
                    <option value="dashboard">dashboard</option>
                    <option value="tickets.my">tickets.my</option>
                    <option value="tickets.history">tickets.history</option>
                    <option value="tickets.inbox">tickets.inbox</option>
                    <option value="admin.hierarchy">admin.hierarchy</option>
                    <option value="admin.menus">admin.menus</option>
                    <option value="admin.categories">admin.categories</option>
                    <option value="admin.users">admin.users</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Grup</label>
                <select name="group" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
                    <option value="main">Utama</option>
                    <option value="ticketing">Tiketing</option>
                    <option value="management">Manajemen</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Ikon</label>
                <select name="icon" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
                    @foreach(['home','plus-circle','inbox-arrow-down','ticket','share','squares-plus','adjustments-horizontal','users'] as $ic)
                        <option value="{{ $ic }}">{{ $ic }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Urutan</label>
                <input type="number" name="sort_order" value="{{ $menus->count() + 1 }}" min="1" required
                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-700 mb-1">Akses Role</label>
            <div class="flex flex-wrap gap-2">
                @foreach($roles as $r)
                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-700">
                        <input type="checkbox" name="roles[]" value="{{ $r->id }}" checked class="rounded text-indigo-600">
                        <span>{{ $r->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
            Tambah Menu
        </button>
    </form>
</div>
@endsection
