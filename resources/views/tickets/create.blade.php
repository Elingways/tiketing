@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto"
     x-data="{
        selectedCategory: '{{ old('category_id', $categories->first()?->id) }}',
        chains: {{ Js::from($categoryChains) }}
     }">

    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
        <div class="pb-6 border-b border-slate-100">
            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-extrabold text-xs border border-emerald-200">
                Pemohon Saat Ini: {{ $user->name }} (Role: {{ $user->role?->name }})
            </span>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2">
                Ajukan Tiket Layanan Baru
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Pilih kategori layanan. Sistem akan otomatis mengarahkan tiket ke role tujuan sesuai struktur hirarki yang telah diatur Superuser.
            </p>
        </div>

        <form action="{{ route('tickets.store') }}" method="POST" class="mt-6 space-y-6">
            @csrf

            <!-- Pilihan Kategori ala SuperApp Cards -->
            <div>
                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-3">
                    1. Pilih Kategori Layanan Tiket
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    @foreach($categories as $cat)
                        <label @click="selectedCategory = '{{ $cat->id }}'"
                               class="cursor-pointer p-4 rounded-2xl border transition-all flex flex-col justify-between"
                               :class="selectedCategory == '{{ $cat->id }}' ? 'ring-2 ring-indigo-600 bg-indigo-50/50 border-indigo-400' : 'bg-slate-50/60 border-slate-200 hover:bg-white'">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <input type="radio" name="category_id" value="{{ $cat->id }}" class="text-indigo-600 focus:ring-indigo-500"
                                           {{ old('category_id', $categories->first()?->id) == $cat->id ? 'checked' : '' }}>
                                    <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full {{ $cat->workflow_type === 'tiered_approval' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $cat->workflow_type === 'tiered_approval' ? 'Approval Berjenjang' : 'Eskalasi / Disposisi' }}
                                    </span>
                                </div>
                                <p class="font-bold text-sm text-slate-900">{{ $cat->name }}</p>
                                <p class="text-xs text-slate-500 mt-1">{{ $cat->description }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Live Preview Jalur Hirarki Otomatis -->
            <div x-show="selectedCategory && chains[selectedCategory]" x-cloak
                 class="p-4 rounded-2xl bg-slate-900 text-white">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <span class="text-xs font-bold text-indigo-300">Simulasi Jalur Hirarki Otomatis untuk Kategori Ini:</span>
                    <span class="text-[11px] font-extrabold px-2.5 py-0.5 rounded-full bg-white/15 text-emerald-300"
                          x-text="chains[selectedCategory]?.workflow_label"></span>
                </div>
                <div class="flex items-center gap-2 flex-wrap mt-2">
                    <template x-for="(roleName, idx) in (chains[selectedCategory]?.chain_names || [])" :key="idx">
                        <div class="flex items-center gap-2">
                            <template x-if="idx > 0">
                                <span class="text-indigo-400 font-bold">→</span>
                            </template>
                            <span class="px-3 py-1 rounded-xl text-xs font-bold"
                                  :class="idx === 0 ? 'bg-white/15 text-slate-200' : (idx === 1 ? 'bg-emerald-500 text-slate-950' : 'bg-indigo-600 text-white')"
                                  x-text="(idx === 0 ? 'Pemohon: ' : (idx === 1 ? 'Tujuan Pertama: ' : 'Hirarki Lanjutan: ')) + roleName">
                            </span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Judul & Prioritas -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <div class="sm:col-span-8">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Judul / Subjek Tiket</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           placeholder="Contoh: Permohonan Persetujuan KRS / Konsultasi Bimbingan Skripsi"
                           class="w-full px-4 py-2.5 rounded-2xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-500">
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tingkat Prioritas</label>
                    <select name="priority" class="w-full px-4 py-2.5 rounded-2xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-500 bg-white">
                        <option value="low">Rendah (Low)</option>
                        <option value="medium" selected>Normal (Medium)</option>
                        <option value="high">Tinggi (High)</option>
                        <option value="urgent">Mendesak (Urgent)</option>
                    </select>
                </div>
            </div>

            <!-- Deskripsi Detail -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Detail Permohonan / Kendala</label>
                <textarea name="description" rows="4" required
                          placeholder="Tuliskan rincian keperluan, NIM/Mata Kuliah terkait, atau alasan pengajuan secara lengkap..."
                          class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-500">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-extrabold shadow-lg shadow-indigo-500/25 transition">
                    Kirim Tiket ke Hirarki Tujuan →
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
