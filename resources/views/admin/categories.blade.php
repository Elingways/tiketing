@extends('layouts.app')

@section('content')
<div class="mb-5">
    <h1 class="text-lg font-bold text-slate-900">Kategori & Alur Tiket</h1>
    <p class="text-xs text-slate-500">Atur role tujuan awal dan mode alur kerja (Eskalasi/Disposisi vs Approval Berjenjang).</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Tambah Kategori -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-xl p-5 border border-slate-200">
            <h2 class="text-sm font-bold text-slate-900 mb-3">Tambah Kategori</h2>
            <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="color" value="indigo">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Kategori</label>
                    <input type="text" name="name" required placeholder="Contoh: Konsultasi KRS"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Mode Alur</label>
                    <select name="workflow_type" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
                        <option value="escalation">Eskalasi / Disposisi</option>
                        <option value="tiered_approval">Approval Berjenjang</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Role Tujuan Pertama</label>
                    <select name="initial_target_role_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
                        <option value="">Otomatis ke Atasan Pemohon</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">Role {{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Puncak Role Approval</label>
                    <select name="max_approval_role_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
                        <option value="">Puncak Hirarki</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">Role {{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Keterangan</label>
                    <input type="text" name="description" placeholder="Opsional..."
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs">
                </div>
                <button type="submit" class="w-full py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold">
                    Simpan Kategori
                </button>
            </form>
        </div>
    </div>

    <!-- Daftar Kategori -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100">
            @foreach($categories as $cat)
                <div class="p-4">
                    <form action="{{ route('admin.categories.update', $cat) }}" method="POST" class="space-y-2.5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="color" value="{{ $cat->color }}">
                        <input type="hidden" name="description" value="{{ $cat->description }}">
                        <input type="hidden" name="is_active" value="1">

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
                            <div class="sm:col-span-4">
                                <input type="text" name="name" value="{{ $cat->name }}" required
                                       class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold">
                            </div>
                            <div class="sm:col-span-3">
                                <select name="workflow_type" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs bg-white">
                                    <option value="escalation" {{ $cat->workflow_type === 'escalation' ? 'selected' : '' }}>Eskalasi/Disposisi</option>
                                    <option value="tiered_approval" {{ $cat->workflow_type === 'tiered_approval' ? 'selected' : '' }}>Approval Berjenjang</option>
                                </select>
                            </div>
                            <div class="sm:col-span-3">
                                <select name="initial_target_role_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs bg-white">
                                    <option value="">Ke Parent Role</option>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}" {{ $cat->initial_target_role_id === $r->id ? 'selected' : '' }}>→ {{ $r->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2 flex justify-end">
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                                    Simpan
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span>Alur: {{ $cat->getWorkflowChain()->pluck('name')->join(' → ') ?: 'Mengikuti Parent' }}</span>
                            <div class="flex items-center gap-2">
                                <span>Puncak:</span>
                                <select name="max_approval_role_id" class="px-2 py-0.5 rounded border border-slate-200 text-[11px] bg-white text-slate-600">
                                    <option value="">Root</option>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}" {{ $cat->max_approval_role_id === $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
