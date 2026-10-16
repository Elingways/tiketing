@extends('layouts.app')

@section('content')
<div class="mb-6 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
    <span class="px-3 py-1 rounded-full bg-rose-50 text-rose-700 font-extrabold text-xs border border-rose-200">
        Superuser Menu & Access Control
    </span>
    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2">
        Manajemen Menu SuperApp & Matriks Hak Akses Role
    </h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
        Atur semua menu aplikasi secara dinamis: tentukan role mana saja yang dapat melihat menu di Sidebar maupun di Grid Launcher SuperApp.
    </p>
</div>

<!-- 1. Matriks Hak Akses Menu per Role (1-Klik Simpan) -->
<div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs mb-6">
    <form action="{{ route('admin.menus.matrix') }}" method="POST">
        @csrf
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">Matriks Hak Akses Menu Berdasarkan Role</h2>
                <p class="text-xs text-slate-500">Centang role mana saja yang berhak mengakses masing-masing menu di dashboard mereka.</p>
            </div>
            <button type="submit"
                    class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-extrabold shadow-md shadow-indigo-500/20 transition">
                ✓ Simpan Matriks Hak Akses Menu
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Menu SuperApp</th>
                        <th class="py-3 px-3">Grup & Route</th>
                        @foreach($roles as $role)
                            <th class="py-3 px-3 text-center">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800">{{ $role->name }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @foreach($menus as $menu)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                        @include('partials.icon', ['name' => $menu->icon, 'class' => 'w-4 h-4'])
                                    </span>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $menu->name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $menu->description }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="font-mono text-[11px] text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">{{ $menu->route_name }}</span>
                                <span class="block text-[10px] text-slate-400 mt-0.5">Urutan: #{{ $menu->sort_order }}</span>
                            </td>
                            @foreach($roles as $role)
                                <td class="py-3.5 px-3 text-center">
                                    <input type="checkbox"
                                           name="matrix[{{ $menu->id }}][]"
                                           value="{{ $role->id }}"
                                           {{ $menu->roles->contains('id', $role->id) ? 'checked' : '' }}
                                           class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
</div>

<!-- 2. Tambah Menu Baru & Edit Menu -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-5">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-extrabold text-slate-900 mb-1">+ Tambah Menu Dinamis Baru</h2>
            <p class="text-xs text-slate-500 mb-4">Menu baru akan langsung muncul di Sidebar dan/atau SuperApp Launcher sesuai role yang dipilih.</p>

            <form action="{{ route('admin.menus.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Menu</label>
                    <input type="text" name="name" required placeholder="Contoh: Laporan Akademik / Arsip Surat"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Route Target</label>
                        <select name="route_name" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                            <option value="dashboard">dashboard (Beranda)</option>
                            <option value="tickets.inbox">tickets.inbox (Tiket Masuk)</option>
                            <option value="tickets.create">tickets.create (Buat Tiket)</option>
                            <option value="tickets.my">tickets.my (Tiket Saya)</option>
                            <option value="admin.hierarchy">admin.hierarchy (Hirarki)</option>
                            <option value="admin.menus">admin.menus (Menu)</option>
                            <option value="admin.categories">admin.categories (Kategori)</option>
                            <option value="admin.users">admin.users (Pengguna)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Grup Navigasi</label>
                        <select name="group" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                            <option value="main">Menu Utama</option>
                            <option value="ticketing">Layanan Tiketing</option>
                            <option value="management">Manajemen Superuser</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Ikon</label>
                        <select name="icon" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                            @foreach(['home','plus-circle','inbox-arrow-down','ticket','share','squares-plus','adjustments-horizontal','users','academic-cap','shield-check','building-library','document-check','trophy'] as $ic)
                                <option value="{{ $ic }}">{{ $ic }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Warna SuperApp</label>
                        <select name="color" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                            @foreach(['indigo','emerald','amber','rose','violet','sky','teal','fuchsia'] as $col)
                                <option value="{{ $col }}">{{ ucfirst($col) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Urutan</label>
                        <input type="number" name="sort_order" value="{{ $menus->count() + 1 }}" min="1" required
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Badge Singkat (Opsional)</label>
                    <input type="text" name="badge_text" placeholder="Contoh: Baru / Penting / Akademik"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Kartu SuperApp</label>
                    <input type="text" name="description" placeholder="Keterangan singkat di kartu SuperApp Launcher..."
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                </div>

                <div class="flex flex-wrap gap-4 pt-1 text-xs">
                    <label class="inline-flex items-center gap-1.5 font-semibold text-slate-700">
                        <input type="checkbox" name="show_in_sidebar" value="1" checked class="rounded text-indigo-600">
                        <span>Tampil di Sidebar</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 font-semibold text-slate-700">
                        <input type="checkbox" name="show_in_launcher" value="1" checked class="rounded text-indigo-600">
                        <span>Tampil di Grid SuperApp</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 font-semibold text-slate-700">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded text-indigo-600">
                        <span>Aktif</span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Berikan Akses ke Role:</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($roles as $r)
                            <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-slate-100 text-xs font-semibold">
                                <input type="checkbox" name="roles[]" value="{{ $r->id }}" checked class="rounded text-indigo-600">
                                <span>{{ $r->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold transition">
                    + Tambahkan Menu Baru
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-7">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-extrabold text-slate-900 mb-1">Daftar Menu Terdaftar ({{ $menus->count() }})</h2>
            <p class="text-xs text-slate-500 mb-4">Kustomisasi detail tampilan, warna ikon, dan visibilitas setiap menu.</p>

            <div class="space-y-3">
                @foreach($menus as $m)
                    <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center">
                                @include('partials.icon', ['name' => $m->icon, 'class' => 'w-5 h-5'])
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-bold text-sm text-slate-900">{{ $m->name }}</p>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-600">
                                        Urutan #{{ $m->sort_order }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500">{{ $m->description }}</p>
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @foreach($m->roles as $mr)
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700">
                                            {{ $mr->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @if(!in_array($m->slug, ['dashboard', 'admin-hierarchy', 'admin-menus']))
                            <form action="{{ route('admin.menus.destroy', $m) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Hapus</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
