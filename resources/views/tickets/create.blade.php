@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto"
     x-data="{
        selectedCategory: '{{ old('category_id', $categories->first()?->id) }}',
        chains: {{ Js::from($categoryChains) }}
     }">

    <div class="mb-5">
        <h1 class="text-lg font-bold text-slate-900">Buat Tiket Baru</h1>
        <p class="text-xs text-slate-500">Pemohon: {{ $user->name }} ({{ $user->role?->name }})</p>
    </div>

    <div class="bg-white rounded-xl p-6 border border-slate-200">
        <form action="{{ route('tickets.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori Layanan</label>
                <select name="category_id" x-model="selectedCategory"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-sm bg-white focus:outline-none focus:border-indigo-600">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">
                            {{ $cat->name }} ({{ $cat->workflow_type === 'tiered_approval' ? 'Approval Berjenjang' : 'Eskalasi/Disposisi' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Info Alur Hirarki Otomatis -->
            <div x-show="selectedCategory && chains[selectedCategory]" x-cloak
                 class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="font-semibold text-slate-700">Alur Hirarki Tujuan:</span>
                    <span class="text-[11px] font-medium text-indigo-600" x-text="chains[selectedCategory]?.workflow_label"></span>
                </div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <template x-for="(roleName, idx) in (chains[selectedCategory]?.chain_names || [])" :key="idx">
                        <div class="flex items-center gap-1.5">
                            <template x-if="idx > 0">
                                <span class="text-slate-400">→</span>
                            </template>
                            <span class="px-2 py-0.5 rounded font-medium"
                                  :class="idx === 1 ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-700'"
                                  x-text="roleName">
                            </span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-8">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Tiket</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           placeholder="Contoh: Permohonan Persetujuan KRS"
                           class="w-full px-3.5 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:border-indigo-600">
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Prioritas</label>
                    <select name="priority" class="w-full px-3.5 py-2 rounded-lg border border-slate-200 text-sm bg-white focus:outline-none focus:border-indigo-600">
                        <option value="low">Rendah</option>
                        <option value="medium" selected>Normal</option>
                        <option value="high">Tinggi</option>
                        <option value="urgent">Mendesak</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi</label>
                <textarea name="description" rows="4" required
                          placeholder="Tuliskan rincian permohonan atau kendala..."
                          class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-sm focus:outline-none focus:border-indigo-600">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition">
                    Kirim Tiket
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
