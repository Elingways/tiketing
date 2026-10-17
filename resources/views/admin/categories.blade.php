@extends('layouts.app')

@section('content')
@php
    $rolesMap = $roles->keyBy('id')->map(fn ($r) => [
        'id' => $r->id,
        'name' => $r->name,
        'slug' => $r->slug,
    ]);
    $defaultMahasiswaRole = $roles->firstWhere('slug', 'mahasiswa') ?? $roles->first();
    $defaultAkademikRole = $roles->firstWhere('slug', 'akademik') ?? $roles->firstWhere('slug', 'dosen') ?? $roles->skip(1)->first();
    $defaultYayasanRole = $roles->firstWhere('slug', 'yayasan') ?? $roles->firstWhere('slug', 'dekan') ?? $roles->last();

    $defaultChainIds = array_values(array_filter([
        $defaultMahasiswaRole?->id,
        $defaultAkademikRole?->id,
        $defaultYayasanRole?->id,
    ]));
@endphp

<div x-data="{
    addCategoryModal: false,
    addRoleModal: false,
    editCategoryModal: false,
    rolesMap: {{ Js::from($rolesMap) }},
    newSteps: {{ Js::from($defaultChainIds) }},
    newTemplateName: '',
    editForm: {
        id: null,
        actionUrl: '',
        name: '',
        description: '',
        workflow_type: 'tiered_approval',
        steps: [],
        has_template: false,
        template_name: '',
        is_attachment_required: true,
        remove_template: false,
        new_file_name: ''
    },
    openEditCategory(cat) {
        this.editForm.id = cat.id;
        this.editForm.actionUrl = cat.actionUrl;
        this.editForm.name = cat.name;
        this.editForm.description = cat.description || '';
        this.editForm.workflow_type = cat.workflow_type;
        this.editForm.steps = (cat.steps && cat.steps.length) ? [...cat.steps] : {{ Js::from($defaultChainIds) }};
        this.editForm.has_template = cat.has_template;
        this.editForm.template_name = cat.template_name || '';
        this.editForm.is_attachment_required = cat.is_attachment_required;
        this.editForm.remove_template = false;
        this.editForm.new_file_name = '';
        this.editCategoryModal = true;
    },
    formatPreviewChain(stepIds) {
        return (stepIds || [])
            .map(id => this.rolesMap[id]?.name)
            .filter(Boolean)
            .join(' → ');
    }
}">

    <!-- Header Halaman Kategori Layanan -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-lg font-bold text-slate-900">Manajemen Kategori Layanan</h1>
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-md bg-teal-50 text-teal-700 border border-teal-200">
                    Hirarki & Template Dokumen
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
                Buat kategori layanan (contoh: <strong>Pengadaan Dana</strong>), atur tahapan hirarki role (misal: <strong>Mahasiswa → Akademik → Yayasan</strong>), dan unggah template dokumen layanan.
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="button" @click="addRoleModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
                <span>+ Buat Role Baru</span>
            </button>
            <button type="button" @click="addCategoryModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition shadow-2xs cursor-pointer">
                <span>+ Buat Kategori Layanan</span>
            </button>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Total Kategori Layanan</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $categories->count() }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Layanan aktif di sistem tiketing</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] font-medium text-indigo-600 uppercase tracking-wider">Approval Berjenjang</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $categories->where('workflow_type', 'tiered_approval')->count() }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Persetujuan bertahap lintas role</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] font-medium text-emerald-600 uppercase tracking-wider">Memiliki Template Dokumen</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $categories->filter(fn ($c) => $c->hasTemplate())->count() }}</p>
            <p class="text-[11px] text-slate-500 mt-1">Layanan dengan form/dokumen wajib</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-[11px] font-medium text-amber-600 uppercase tracking-wider">Role Hirarki Tersedia</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $roles->count() }}</p>
            <p class="text-[11px] text-slate-500 mt-1 truncate">{{ $roles->pluck('name')->join(', ') }}</p>
        </div>
    </div>

    <!-- Tabel Kategori Layanan -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700">Daftar Kategori Layanan & Alur Hirarki</h2>
            </div>
            <span class="text-[11px] text-slate-400">Klik <strong>Edit Layanan</strong> untuk mengubah urutan hirarki atau mengganti template dokumen</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Nama Layanan & Deskripsi</th>
                        <th class="py-3 px-4">Susunan Tahapan Hirarki Role</th>
                        <th class="py-3 px-4 w-40">Mode Alur</th>
                        <th class="py-3 px-4 w-64">Template Dokumen Layanan</th>
                        <th class="py-3 px-4 w-20 text-center">Tiket</th>
                        <th class="py-3 px-4 w-36 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($categories as $cat)
                        @php
                            $fullChain = $cat->getFullHierarchyRoles();
                            if ($fullChain->isEmpty()) {
                                $mhs = $roles->firstWhere('slug', 'mahasiswa');
                                $fullChain = $mhs
                                    ? collect([$mhs])->concat($cat->getWorkflowChain($mhs))
                                    : $cat->getWorkflowChain();
                            }
                            $stepIds = $fullChain->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
                            $editPayload = [
                                'id' => $cat->id,
                                'actionUrl' => route('admin.categories.update', $cat),
                                'name' => $cat->name,
                                'description' => $cat->description,
                                'workflow_type' => $cat->workflow_type,
                                'steps' => $stepIds,
                                'has_template' => $cat->hasTemplate(),
                                'template_name' => $cat->template_name,
                                'is_attachment_required' => (bool) $cat->is_attachment_required,
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition align-top">
                            <!-- 1. Nama Layanan & Deskripsi -->
                            <td class="py-4 px-4">
                                <div class="font-bold text-slate-900">{{ $cat->name }}</div>
                                @if($cat->description)
                                    <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed max-w-md">{{ $cat->description }}</p>
                                @endif
                            </td>

                            <!-- 2. Susunan Tahapan Hirarki Role -->
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @foreach($fullChain as $idx => $stepRole)
                                        @if($idx > 0)
                                            <span class="text-slate-300 font-bold text-xs">→</span>
                                        @endif
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold border {{ $idx === 0 ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200' }}">
                                            <span class="w-4 h-4 rounded-full text-[10px] flex items-center justify-center {{ $idx === 0 ? 'bg-slate-300 text-slate-800' : 'bg-indigo-600 text-white' }}">
                                                {{ $idx + 1 }}
                                            </span>
                                            <span>{{ $stepRole->name }}</span>
                                        </span>
                                    @endforeach
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1.5">
                                    Total {{ $fullChain->count() }} tahapan role ({{ $fullChain->first()?->name ?? 'Pemohon' }} s/d {{ $fullChain->last()?->name ?? 'Puncak' }})
                                </p>
                            </td>

                            <!-- 3. Mode Alur -->
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if($cat->workflow_type === 'tiered_approval')
                                    <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Approval Berjenjang
                                    </span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Eskalasi / Disposisi
                                    </span>
                                @endif
                            </td>

                            <!-- 4. Template Dokumen Layanan -->
                            <td class="py-4 px-4">
                                @if($cat->hasTemplate())
                                    <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200/80 space-y-1.5">
                                        <div class="flex items-center justify-between gap-1.5">
                                            <span class="text-[11px] font-semibold text-slate-800 truncate" title="{{ $cat->template_name }}">
                                                📄 {{ $cat->template_name }}
                                            </span>
                                            @if($cat->is_attachment_required)
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200 shrink-0">
                                                    Wajib Diisi
                                                </span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-slate-100 text-slate-600 shrink-0">
                                                    Opsional
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button"
                                                    @click="openDocPreview('{{ route('categories.template.preview', $cat) }}', '{{ route('categories.template.download', $cat) }}', '{{ addslashes($cat->template_name ?? 'Template Layanan') }}')"
                                                    class="px-2 py-1 rounded bg-white hover:bg-indigo-50 text-indigo-600 border border-slate-200 text-[10px] font-semibold transition cursor-pointer">
                                                📎 Preview
                                            </button>
                                            <a href="{{ route('categories.template.download', $cat) }}"
                                               class="px-2 py-1 rounded bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-semibold transition">
                                                ⬇ Unduh Template
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] text-slate-400 italic">Tidak ada template khusus</span>
                                        <button type="button" @click="openEditCategory({{ Js::from($editPayload) }})"
                                                class="px-2 py-1 rounded border border-dashed border-slate-300 hover:border-indigo-400 text-[10px] font-semibold text-indigo-600 cursor-pointer">
                                            + Upload
                                        </button>
                                    </div>
                                @endif
                            </td>

                            <!-- 5. Jumlah Tiket -->
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ $cat->tickets_count }}
                                </span>
                            </td>

                            <!-- 6. Aksi -->
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button"
                                            @click="openEditCategory({{ Js::from($editPayload) }})"
                                            class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition cursor-pointer">
                                        Edit Layanan
                                    </button>
                                    @if($cat->tickets_count === 0)
                                        <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST"
                                              onsubmit="return confirm('Hapus kategori layanan {{ addslashes($cat->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2.5 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 text-rose-600 text-xs font-medium transition cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 px-4 text-center text-xs text-slate-400">
                                Belum ada kategori layanan. Klik tombol <strong>+ Buat Kategori Layanan</strong> di kanan atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: Buat Kategori Layanan Baru (Beserta Susunan Hirarki & Upload Template Dokumen) -->
    <div x-show="addCategoryModal" x-cloak
         @keydown.escape.window="addCategoryModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="addCategoryModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Buat Kategori Layanan Baru</h3>
                    <p class="text-[11px] text-slate-400">Tentukan nama layanan, tahapan hirarki role, dan template dokumen wajib.</p>
                </div>
                <button type="button" @click="addCategoryModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">✕</button>
            </div>

            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <input type="hidden" name="color" value="indigo">

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
                    <div class="sm:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori Layanan <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required
                               placeholder="Contoh: Pengadaan Dana Kegiatan"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                    </div>

                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Mode Alur Hirarki</label>
                        <select name="workflow_type" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                            <option value="tiered_approval">Approval Berjenjang</option>
                            <option value="escalation">Eskalasi / Disposisi</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi & Panduan Singkat Layanan</label>
                    <textarea name="description" rows="2"
                              placeholder="Contoh: Pengajuan pencairan dana kegiatan mahasiswa melalui persetujuan Akademik dan pengesahan Yayasan."
                              class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900"></textarea>
                </div>

                <!-- Builder Tahapan Hirarki Role -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold text-slate-800">Susunan Tahapan Hirarki Role Layanan</label>
                            <p class="text-[11px] text-slate-500">Contoh: Tahap 1 (Mahasiswa) → Tahap 2 (Akademik) → Tahap 3 (Yayasan)</p>
                        </div>
                        <button type="button"
                                @click="newSteps.push({{ $defaultYayasanRole?->id ?? 1 }})"
                                class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-semibold transition cursor-pointer">
                            + Tambah Tahap Role
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(roleId, idx) in newSteps" :key="idx">
                            <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200">
                                <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-[11px] font-bold shrink-0"
                                      x-text="idx === 0 ? 'Tahap 1 (Pemohon)' : 'Tahap ' + (idx + 1)"></span>

                                <select name="workflow_roles[]" x-model.number="newSteps[idx]"
                                        class="flex-1 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>

                                <button type="button"
                                        x-show="newSteps.length > 2"
                                        @click="newSteps.splice(idx, 1)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                        title="Hapus tahap ini">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="px-3 py-2 rounded-lg bg-indigo-50/70 border border-indigo-200/80 flex items-center justify-between text-[11px]">
                        <span class="text-indigo-700 font-medium">Pratinjau Alur Layanan:</span>
                        <strong class="text-indigo-950" x-text="formatPreviewChain(newSteps)"></strong>
                    </div>
                </div>

                <!-- Upload Template Dokumen Layanan -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-800">
                            Upload Template Dokumen Layanan <span class="font-normal text-slate-400">(PDF, DOCX, XLSX — maks 10MB)</span>
                        </label>
                        <p class="text-[11px] text-slate-500">
                            Jika diisi, pemohon dapat mengunduh template dokumen ini untuk dilengkapi saat mengajukan tiket pada layanan ini.
                        </p>
                    </div>

                    <label class="flex items-center justify-between px-3.5 py-2.5 rounded-lg border border-dashed border-slate-300 hover:border-indigo-500 bg-white cursor-pointer transition">
                        <div class="flex items-center gap-2 text-xs text-slate-600 truncate">
                            <span>📄</span>
                            <span x-text="newTemplateName || 'Pilih file template dokumen layanan (misal: Form_Pengadaan_Dana.pdf)...'" class="truncate"></span>
                        </div>
                        <span class="text-[11px] font-semibold text-indigo-600 shrink-0 ml-2">Pilih Template</span>
                        <input type="file" name="template_file" class="hidden"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip"
                               @change="newTemplateName = $event.target.files[0]?.name || ''">
                    </label>

                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" name="is_attachment_required" value="1" checked
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Wajibkan pemohon mengunggah dokumen yang sudah diisi saat mengajukan tiket layanan ini</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addCategoryModal = false"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                        Simpan Kategori Layanan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Kategori Layanan, Hirarki Role & Template Dokumen -->
    <div x-show="editCategoryModal" x-cloak
         @keydown.escape.window="editCategoryModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="editCategoryModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Edit Kategori Layanan & Alur Hirarki</h3>
                    <p class="text-[11px] text-slate-400" x-text="editForm.name"></p>
                </div>
                <button type="button" @click="editCategoryModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">✕</button>
            </div>

            <form :action="editForm.actionUrl" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5">
                    <div class="sm:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kategori Layanan <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" x-model="editForm.name" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                    </div>

                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Mode Alur Hirarki</label>
                        <select name="workflow_type" x-model="editForm.workflow_type"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                            <option value="tiered_approval">Approval Berjenjang</option>
                            <option value="escalation">Eskalasi / Disposisi</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi & Panduan Singkat Layanan</label>
                    <textarea name="description" rows="2" x-model="editForm.description"
                              class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900"></textarea>
                </div>

                <!-- Builder Tahapan Hirarki Role -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold text-slate-800">Susunan Tahapan Hirarki Role Layanan</label>
                            <p class="text-[11px] text-slate-500">Atur urutan role dari pemohon hingga pemberi persetujuan akhir.</p>
                        </div>
                        <button type="button"
                                @click="editForm.steps.push({{ $defaultYayasanRole?->id ?? 1 }})"
                                class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-semibold transition cursor-pointer">
                            + Tambah Tahap Role
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(roleId, idx) in editForm.steps" :key="idx">
                            <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200">
                                <span class="px-2 py-1 rounded bg-slate-100 text-slate-700 text-[11px] font-bold shrink-0"
                                      x-text="idx === 0 ? 'Tahap 1 (Pemohon)' : 'Tahap ' + (idx + 1)"></span>

                                <select name="workflow_roles[]" x-model.number="editForm.steps[idx]"
                                        class="flex-1 px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>

                                <button type="button"
                                        x-show="editForm.steps.length > 2"
                                        @click="editForm.steps.splice(idx, 1)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="px-3 py-2 rounded-lg bg-indigo-50/70 border border-indigo-200/80 flex items-center justify-between text-[11px]">
                        <span class="text-indigo-700 font-medium">Pratinjau Alur Layanan:</span>
                        <strong class="text-indigo-950" x-text="formatPreviewChain(editForm.steps)"></strong>
                    </div>
                </div>

                <!-- Template Dokumen Layanan -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold text-slate-800">Template Dokumen Layanan</label>
                            <p class="text-[11px] text-slate-500">Unggah atau perbarui file template yang harus diisi pemohon.</p>
                        </div>
                        <template x-if="editForm.has_template">
                            <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold"
                                  x-text="'Saat ini: ' + editForm.template_name"></span>
                        </template>
                    </div>

                    <label class="flex items-center justify-between px-3.5 py-2.5 rounded-lg border border-dashed border-slate-300 hover:border-indigo-500 bg-white cursor-pointer transition">
                        <div class="flex items-center gap-2 text-xs text-slate-600 truncate">
                            <span>📄</span>
                            <span x-text="editForm.new_file_name || (editForm.has_template ? 'Pilih file baru untuk mengganti template saat ini...' : 'Pilih file template dokumen layanan...')" class="truncate"></span>
                        </div>
                        <span class="text-[11px] font-semibold text-indigo-600 shrink-0 ml-2">Pilih File</span>
                        <input type="file" name="template_file" class="hidden"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip"
                               @change="editForm.new_file_name = $event.target.files[0]?.name || ''">
                    </label>

                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" name="is_attachment_required" value="1"
                               x-model="editForm.is_attachment_required"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Wajibkan pemohon mengunggah dokumen yang sudah diisi saat mengajukan tiket layanan ini</span>
                    </label>

                    <template x-if="editForm.has_template">
                        <label class="flex items-center gap-2 text-xs text-rose-600 cursor-pointer select-none pt-1">
                            <input type="checkbox" name="remove_template" value="1"
                                   x-model="editForm.remove_template"
                                   class="rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                            <span>Hapus template dokumen dari kategori layanan ini</span>
                        </label>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editCategoryModal = false"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Buat Role Baru Secara Cepat (Misal: Akademik, Yayasan, Keuangan, dll.) -->
    <div x-show="addRoleModal" x-cloak
         @keydown.escape.window="addRoleModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="addRoleModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Tambah Role Hirarki Baru</h3>
                    <p class="text-[11px] text-slate-400">Role baru dapat langsung dipilih pada tahapan hirarki kategori layanan.</p>
                </div>
                <button type="button" @click="addRoleModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">✕</button>
            </div>

            <form action="{{ route('admin.hierarchy.store') }}" method="POST" class="p-6 space-y-3.5">
                @csrf
                <input type="hidden" name="badge_color" value="indigo">
                <input type="hidden" name="can_create_ticket" value="1">
                <input type="hidden" name="can_receive_ticket" value="1">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Role Baru <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required
                           placeholder="Contoh: Akademik / Yayasan / Keuangan"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Atasan Default (Parent Role)</label>
                    <select name="parent_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white">
                        <option value="">Puncak Hirarki (Root)</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Role</label>
                    <input type="text" name="description"
                           placeholder="Contoh: Unit Akademik / Pengurus Yayasan"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addRoleModal = false"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                        Simpan Role
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
