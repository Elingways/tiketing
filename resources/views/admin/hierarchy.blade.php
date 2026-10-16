@extends('layouts.app')

@section('content')
<div class="mb-6 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="px-3 py-1 rounded-full bg-violet-50 text-violet-700 font-extrabold text-xs border border-violet-200">
                Superuser Dynamic Hierarchy Engine
            </span>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2">
                Manajemen Role & Pohon Hirarki Dinamis
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5 max-w-3xl">
                Buat role baru dan tentukan jalur hirarkinya secara bebas (contoh: <strong>Role Baru → Dosen → Kaprodi → Dekan</strong>). Perubahan hirarki di sini langsung mengatur arah eskalasi & approval tiket secara real-time!
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Kolom Kiri (5 Cols): Form Buat Role Baru & Pohon Visual Hirarki -->
    <div class="lg:col-span-5 space-y-6">
        <!-- Card Buat Role Baru & Tentukan Hirarkinya -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-extrabold text-slate-900 mb-1">+ Buat Role Baru & Atur Hirarki</h2>
            <p class="text-xs text-slate-500 mb-4">
                Contoh: Buat role <em>"Asisten Lab"</em> atau <em>"Ketua Kelas"</em> lalu arahkan hirarki atasannya ke <strong>Dosen</strong>.
            </p>

            <form action="{{ route('admin.hierarchy.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Role Baru</label>
                    <input type="text" name="name" required placeholder="Contoh: Asisten Dosen / Staf Akademik / Wakil Dekan"
                           class="w-full px-4 py-2.5 rounded-2xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Hirarki Atasan Langsung (Parent Role)
                    </label>
                    <select name="parent_id" class="w-full px-4 py-2.5 rounded-2xl border border-slate-200 text-xs bg-white focus:outline-none focus:border-indigo-500">
                        <option value="">— Puncak Hirarki / Tanpa Atasan (Root) —</option>
                        @foreach($roles->where('is_superuser', false) as $r)
                            <option value="{{ $r->id }}">
                                Ke Role: {{ $r->name }} (Level {{ $r->level }}{{ $r->parent ? ' → ' . $r->parent->name : '' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Tiket atau eskalasi dari role baru ini akan otomatis mengalir ke role atasan yang dipilih di atas.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Warna Identitas Role</label>
                        <select name="badge_color" class="w-full px-3 py-2 rounded-2xl border border-slate-200 text-xs bg-white">
                            <option value="indigo">Indigo</option>
                            <option value="emerald">Emerald (Hijau)</option>
                            <option value="amber">Amber (Kuning/Oranye)</option>
                            <option value="violet">Violet (Ungu)</option>
                            <option value="sky">Sky (Biru Muda)</option>
                            <option value="rose">Rose (Merah)</option>
                            <option value="teal">Teal</option>
                            <option value="fuchsia">Fuchsia</option>
                        </select>
                    </div>
                    <div class="flex flex-col justify-end space-y-1.5 pb-1">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="can_create_ticket" value="1" checked class="rounded text-indigo-600">
                            <span>Bisa Buat Tiket</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                            <input type="checkbox" name="can_receive_ticket" value="1" checked class="rounded text-indigo-600">
                            <span>Punya Inbox Tiket Masuk</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Tugas & Wewenang</label>
                    <textarea name="description" rows="2" placeholder="Penjelasan singkat fungsi role ini dalam hirarki..."
                              class="w-full px-4 py-2 rounded-2xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <button type="submit"
                        class="w-full py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-extrabold text-xs shadow-lg shadow-indigo-500/20 transition">
                    + Simpan Role Baru ke Pohon Hirarki
                </button>
            </form>
        </div>

        <!-- Visualisasi Pohon Hirarki Live -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-extrabold text-slate-900 mb-1">Visualisasi Pohon Hirarki (Live Tree)</h2>
            <p class="text-xs text-slate-500 mb-4">Struktur relasi atasan-bawahan yang sedang aktif saat ini.</p>

            <div class="space-y-3">
                @foreach($treeRoots as $root)
                    @include('partials.hierarchy-node', ['node' => $root])
                @endforeach
            </div>
        </div>
    </div>

    <!-- Kolom Kanan (7 Cols): Daftar Role & Editor Hirarki Dinamis (Ubah Parent Role Kapan Saja) -->
    <div class="lg:col-span-7 space-y-4">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-extrabold text-slate-900 mb-1">Konfigurasi & Relasi Hirarki Setiap Role</h2>
            <p class="text-xs text-slate-500 mb-5">
                Anda dapat mengubah jalur atasan (<em>Hirarki ke Role Mana</em>) pada setiap kartu role di bawah ini. Sistem dilengkapi pendeteksi otomatis agar tidak terjadi <em>circular loop</em>.
            </p>

            <div class="space-y-4">
                @foreach($roles as $roleItem)
                    <div class="p-5 rounded-2xl border {{ $roleItem->is_superuser ? 'bg-rose-50/40 border-rose-200' : 'bg-slate-50/60 border-slate-200/90' }}">
                        <form action="{{ route('admin.hierarchy.update', $roleItem) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-xl bg-slate-900 text-white text-xs font-extrabold">
                                        {{ $roleItem->name }}
                                    </span>
                                    @if(!$roleItem->is_superuser)
                                        <span class="text-xs text-slate-500 font-semibold">
                                            Jalur ke Atas:
                                            <strong class="text-indigo-600">{{ implode(' → ', $roleItem->getUpwardChainNames()) }}</strong>
                                        </span>
                                    @else
                                        <span class="text-xs font-bold text-rose-600">Role Pengelola Utama (Superuser)</span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-slate-400 font-semibold">{{ $roleItem->users->count() }} akun pengguna</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <div class="sm:col-span-4">
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Role</label>
                                    <input type="text" name="name" value="{{ $roleItem->name }}" required
                                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                                </div>

                                <div class="sm:col-span-5">
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">
                                        Hirarki Atasan (Parent Role)
                                    </label>
                                    @if($roleItem->is_superuser)
                                        <input type="text" disabled value="Superuser (Mengawasi Semua)"
                                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-100 text-slate-500">
                                    @else
                                        <select name="parent_id" class="w-full px-3 py-2 rounded-xl border border-indigo-200 text-xs bg-white font-semibold text-slate-800">
                                            <option value="">— Puncak Hirarki (Root) —</option>
                                            @foreach($roles->where('is_superuser', false) as $candidate)
                                                @if($candidate->id !== $roleItem->id)
                                                    <option value="{{ $candidate->id }}" {{ $roleItem->parent_id === $candidate->id ? 'selected' : '' }}>
                                                        → Role {{ $candidate->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @endif
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Warna Badge</label>
                                    <select name="badge_color" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                                        @foreach(['indigo','emerald','amber','violet','sky','rose','teal','fuchsia'] as $col)
                                            <option value="{{ $col }}" {{ $roleItem->badge_color === $col ? 'selected' : '' }}>{{ ucfirst($col) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <input type="text" name="description" value="{{ $roleItem->description }}" placeholder="Deskripsi role..."
                                       class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs bg-white">
                            </div>

                            <div class="mt-3 pt-3 border-t border-slate-200/70 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-4 text-xs">
                                    <label class="inline-flex items-center gap-1.5 font-semibold text-slate-700">
                                        <input type="checkbox" name="can_create_ticket" value="1" {{ $roleItem->can_create_ticket ? 'checked' : '' }} class="rounded text-indigo-600">
                                        <span>Dapat Membuat Tiket</span>
                                    </label>
                                    <label class="inline-flex items-center gap-1.5 font-semibold text-slate-700">
                                        <input type="checkbox" name="can_receive_ticket" value="1" {{ $roleItem->can_receive_ticket ? 'checked' : '' }} class="rounded text-indigo-600">
                                        <span>Menerima Tiket Masuk di Dashboard</span>
                                    </label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-extrabold transition shadow-xs">
                                        Simpan Hirarki
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if(!$roleItem->is_superuser && $roleItem->users->count() === 0)
                            <form action="{{ route('admin.hierarchy.destroy', $roleItem) }}" method="POST" class="mt-2 flex justify-end">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] font-bold text-rose-600 hover:underline">
                                    Hapus Role {{ $roleItem->name }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
