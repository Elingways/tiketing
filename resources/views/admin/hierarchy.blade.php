@extends('layouts.app')

@section('content')
<div class="mb-5">
    <h1 class="text-lg font-bold text-slate-900">Manajemen Role & Hirarki</h1>
    <p class="text-xs text-slate-500">Buat role baru dan tentukan jalur hirarki atasannya (contoh: Mahasiswa → Dosen → Kaprodi).</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Kiri: Buat Role Baru & Struktur Hirarki -->
    <div class="lg:col-span-5 space-y-5">
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <h2 class="text-sm font-bold text-slate-900 mb-3">Tambah Role Baru</h2>

            <form action="{{ route('admin.hierarchy.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Role</label>
                    <input type="text" name="name" required placeholder="Contoh: Asisten Dosen"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-indigo-600">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Hirarki ke Role Atasan (Parent)</label>
                    <select name="parent_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-indigo-600">
                        <option value="">— Puncak Hirarki (Root) —</option>
                        @foreach($roles->where('is_superuser', false) as $r)
                            <option value="{{ $r->id }}">Role {{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="badge_color" value="indigo">

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Deskripsi Singkat</label>
                    <input type="text" name="description" placeholder="Opsional..."
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-indigo-600">
                </div>

                <div class="flex items-center gap-4 text-xs pt-1">
                    <label class="inline-flex items-center gap-1.5 text-slate-700">
                        <input type="checkbox" name="can_create_ticket" value="1" checked class="rounded text-indigo-600">
                        <span>Bisa Buat Tiket</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 text-slate-700">
                        <input type="checkbox" name="can_receive_ticket" value="1" checked class="rounded text-indigo-600">
                        <span>Terima Tiket Masuk</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition">
                    Simpan Role Baru
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <h2 class="text-sm font-bold text-slate-900 mb-3">Pohon Hirarki Saat Ini</h2>
            <div class="space-y-1.5">
                @foreach($treeRoots as $root)
                    @include('partials.hierarchy-node', ['node' => $root])
                @endforeach
            </div>
        </div>
    </div>

    <!-- Kanan: Daftar & Ubah Hirarki Role -->
    <div class="lg:col-span-7">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900">Pengaturan Hirarki per Role</h2>
                <p class="text-xs text-slate-500">Ubah atasan hirarki dari masing-masing role di bawah ini.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($roles as $roleItem)
                    <div class="p-4">
                        <form action="{{ route('admin.hierarchy.update', $roleItem) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="badge_color" value="{{ $roleItem->badge_color }}">
                            <input type="hidden" name="description" value="{{ $roleItem->description }}">

                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-slate-900">{{ $roleItem->name }}</span>
                                    @if(!$roleItem->is_superuser)
                                        <span class="text-[11px] text-slate-400">
                                            ({{ implode(' → ', $roleItem->getUpwardChainNames()) }})
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-400">{{ $roleItem->users->count() }} user</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
                                <div class="sm:col-span-4">
                                    <input type="text" name="name" value="{{ $roleItem->name }}" required
                                           class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs">
                                </div>
                                <div class="sm:col-span-5">
                                    @if($roleItem->is_superuser)
                                        <input type="text" disabled value="Superuser (Semua Akses)"
                                               class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-slate-50 text-slate-400">
                                    @else
                                        <select name="parent_id" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white">
                                            <option value="">— Root (Puncak Hirarki) —</option>
                                            @foreach($roles->where('is_superuser', false) as $candidate)
                                                @if($candidate->id !== $roleItem->id)
                                                    <option value="{{ $candidate->id }}" {{ $roleItem->parent_id === $candidate->id ? 'selected' : '' }}>
                                                        Hirarki ke: {{ $candidate->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                                <div class="sm:col-span-3 flex justify-end gap-2">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                                        Simpan
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-2.5 text-[11px] text-slate-500">
                                <div class="flex items-center gap-3">
                                    <label class="inline-flex items-center gap-1">
                                        <input type="checkbox" name="can_create_ticket" value="1" {{ $roleItem->can_create_ticket ? 'checked' : '' }} class="rounded text-indigo-600">
                                        <span>Buat Tiket</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1">
                                        <input type="checkbox" name="can_receive_ticket" value="1" {{ $roleItem->can_receive_ticket ? 'checked' : '' }} class="rounded text-indigo-600">
                                        <span>Terima Tiket</span>
                                    </label>
                                </div>
                            </div>
                        </form>

                        @if(!$roleItem->is_superuser && $roleItem->users->count() === 0)
                            <form action="{{ route('admin.hierarchy.destroy', $roleItem) }}" method="POST" class="mt-1 flex justify-end">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] text-rose-600 hover:underline">Hapus Role</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
