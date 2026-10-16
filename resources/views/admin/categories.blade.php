@extends('layouts.app')

@section('content')
<div class="mb-6 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
    <span class="px-3 py-1 rounded-full bg-teal-50 text-teal-700 font-extrabold text-xs border border-teal-200">
        Workflow & Routing Engine
    </span>
    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2">
        Manajemen Kategori & Alur Hirarki Tiket
    </h1>
    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
        Atur per kategori tiket apakah menggunakan <strong>Mode Eskalasi/Disposisi</strong> (masuk ke Dosen, lalu opsional dieskalasi ke Kaprodi) atau <strong>Mode Approval Berjenjang Otomatis</strong> (wajib disetujui berurutan sesuai hirarki).
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Kolom Kiri (5 Cols): Tambah Kategori & Konfigurasi Workflow -->
    <div class="lg:col-span-5">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
            <h2 class="text-base font-extrabold text-slate-900 mb-1">+ Buat Kategori Tiket Baru</h2>
            <p class="text-xs text-slate-500 mb-4">Tentukan role penerima awal dan mode alur hirarkinya.</p>

            <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Kategori Layanan</label>
                    <input type="text" name="name" required placeholder="Contoh: Permohonan Pindah Kelas / Surat Aktif Kuliah"
                           class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mode Alur Kerja Hirarki (Workflow Type)</label>
                    <select name="workflow_type" class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs bg-white font-semibold">
                        <option value="escalation">Mode Eskalasi / Disposisi Dinamis (Bisa selesai di tempat atau dieskalasi ke atasan)</option>
                        <option value="tiered_approval">Mode Approval Berjenjang Otomatis (Wajib disetujui naik per tahap hirarki)</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Role Penerima Pertama</label>
                        <select name="initial_target_role_id" class="w-full px-3 py-2.5 rounded-2xl border border-slate-200 text-xs bg-white">
                            <option value="">Otomatis ke Atasan Pembuat</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">Role {{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Batas Puncak Role Approval</label>
                        <select name="max_approval_role_id" class="w-full px-3 py-2.5 rounded-2xl border border-slate-200 text-xs bg-white">
                            <option value="">Sampai Puncak Hirarki</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">Sampai Role {{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Warna Kategori</label>
                    <select name="color" class="w-full px-3.5 py-2 rounded-2xl border border-slate-200 text-xs bg-white">
                        @foreach(['indigo','emerald','amber','rose','violet','sky','teal','fuchsia'] as $col)
                            <option value="{{ $col }}">{{ ucfirst($col) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Panduan</label>
                    <textarea name="description" rows="3" placeholder="Jelaskan ketentuan kategori tiket ini..."
                              class="w-full px-3.5 py-2 rounded-2xl border border-slate-200 text-xs"></textarea>
                </div>

                <button type="submit" class="w-full py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-xs font-extrabold shadow-md transition">
                    + Simpan Kategori & Workflow
                </button>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan (7 Cols): Daftar Kategori & Edit Alur -->
    <div class="lg:col-span-7 space-y-4">
        @foreach($categories as $cat)
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
                <form action="{{ route('admin.categories.update', $cat) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-xl text-xs font-extrabold {{ $cat->workflow_type === 'tiered_approval' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $cat->workflow_type === 'tiered_approval' ? 'Approval Berjenjang' : 'Eskalasi / Disposisi' }}
                            </span>
                            <span class="text-xs text-slate-400">{{ $cat->tickets->count() }} tiket menggunakan alur ini</span>
                        </div>
                        <label class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700">
                            <input type="checkbox" name="is_active" value="1" {{ $cat->is_active ? 'checked' : '' }} class="rounded text-indigo-600">
                            <span>Aktif</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                        <div class="sm:col-span-8">
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Kategori</label>
                            <input type="text" name="name" value="{{ $cat->name }}" required
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold">
                        </div>
                        <div class="sm:col-span-4">
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Warna</label>
                            <select name="color" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                                @foreach(['indigo','emerald','amber','rose','violet','sky','teal','fuchsia'] as $col)
                                    <option value="{{ $col }}" {{ $cat->color === $col ? 'selected' : '' }}>{{ ucfirst($col) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Mode Workflow</label>
                            <select name="workflow_type" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                                <option value="escalation" {{ $cat->workflow_type === 'escalation' ? 'selected' : '' }}>Eskalasi / Disposisi</option>
                                <option value="tiered_approval" {{ $cat->workflow_type === 'tiered_approval' ? 'selected' : '' }}>Approval Berjenjang</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Role Tujuan Pertama</label>
                            <select name="initial_target_role_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                                <option value="">Otomatis ke Parent Role</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ $cat->initial_target_role_id === $r->id ? 'selected' : '' }}>Role {{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Puncak Role Approval</label>
                            <select name="max_approval_role_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                                <option value="">Puncak Hirarki</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ $cat->max_approval_role_id === $r->id ? 'selected' : '' }}>Role {{ $r->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <input type="text" name="description" value="{{ $cat->description }}"
                               class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-600">
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="text-[11px] text-slate-500">
                            Simulasi Rantai:
                            <strong class="text-indigo-600">
                                {{ $cat-> getWorkflowChain()->pluck('name')->join(' → ') ?: 'Mengikuti Parent Pemohon' }}
                            </strong>
                        </span>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold transition">
                            Perbarui Alur
                        </button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection
