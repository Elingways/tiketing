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

    $oldStaff = old('staff');
    if (!is_array($oldStaff) || count($oldStaff) === 0) {
        $oldStaff = [['name' => '', 'email' => '', 'keahlian' => '']];
    }
    $oldRouteInput = old('routes', []);
    $oldRoutes = [];
    foreach (range(1, 8) as $level) {
        $oldRoutes[$level] = (string) ($oldRouteInput[$level] ?? $oldRouteInput[(string) $level] ?? '');
    }
@endphp

<div x-data="{
    addCategoryModal: false,
    addRoleModal: {{ old('from_role_modal') ? 'true' : 'false' }},
    manageRoleModal: false,
    editCategoryModal: false,
    staffRows: {{ Js::from(array_values($oldStaff)) }},
    routes: {{ Js::from($oldRoutes) }},
    headIndex: '{{ (string) old('head_index', '0') }}',
    manage: { id: null, name: '', actionUrl: '', staff: [], newStaff: [], routes: {}, headId: '', truncated: false },
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
    },
    addStaffRow() {
        this.staffRows.push({ name: '', email: '', keahlian: '' });
    },
    removeStaffRow(index) {
        if (this.staffRows.length === 1) {
            this.staffRows = [{ name: '', email: '', keahlian: '' }];
            this.routes = { 1:'', 2:'', 3:'', 4:'', 5:'', 6:'', 7:'', 8:'' };
            this.headIndex = '0';
            return;
        }
        this.staffRows.splice(index, 1);
        if (String(this.headIndex) === String(index)) {
            this.headIndex = '0';
        } else if (Number(this.headIndex) > index) {
            this.headIndex = String(Number(this.headIndex) - 1);
        }
        const next = {};
        for (let level = 1; level <= 8; level++) {
            const current = this.routes[level];
            if (current === '' || current === null || current === undefined) {
                next[level] = '';
            } else if (Number(current) === index) {
                next[level] = '';
            } else if (Number(current) > index) {
                next[level] = String(Number(current) - 1);
            } else {
                next[level] = String(current);
            }
        }
        this.routes = next;
    },
    staffChoices() {
        return this.staffRows.map((row, i) => ({
            value: String(i),
            label: (row.name || '').trim() ? row.name : ('Staff ' + (i + 1)),
        }));
    },
    async openManage(roleId) {
        const response = await fetch(`{{ url('/admin/hierarchy/roles') }}/${roleId}/staff`, {
            headers: { 'Accept': 'application/json' },
        });
        if (!response.ok) {
            return;
        }
        const data = await response.json();
        this.manage = {
            id: data.id,
            name: data.name,
            actionUrl: data.actionUrl,
            staff: data.staff || [],
            newStaff: [],
            routes: data.routes || {},
            headId: data.head_user_id || '',
            truncated: !!data.truncated,
        };
        this.manageRoleModal = true;
    },
    addManageStaff() {
        this.manage.newStaff.push({ name: '', email: '', keahlian: '' });
    },
    manageChoices() {
        const existing = (this.manage.staff || []).map(person => ({
            value: String(person.id),
            label: person.name,
        }));
        const fresh = (this.manage.newStaff || []).map((row, i) => ({
            value: 'new-' + i,
            label: (row.name || '').trim() ? row.name : ('Staff baru ' + (i + 1)),
        }));
        return existing.concat(fresh);
    }
}">

    <!-- Header Ringkas -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Kategori Layanan</h1>
            <p class="text-xs text-slate-500">
                Atur layanan tiketing, tahapan hirarki role, dan template dokumen yang perlu diisi pemohon.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="addRoleModal = true"
                    class="px-3.5 py-2 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
                + Role Baru
            </button>
            <button type="button" @click="addCategoryModal = true"
                    class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition cursor-pointer">
                + Tambah Layanan
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-5">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Kepala & Keahlian Staff</h2>
                <p class="text-xs text-slate-500 mt-0.5">Tiket masuk ke kepala dulu. Setelah ditinjau, kepala mengarahkan ke staff yang keahliannya sesuai permintaan.</p>
            </div>
        </div>
        @php $configuredRoles = $roles->filter(fn ($role) => $role->head_user_id || $role->priorityRoutes->isNotEmpty()); @endphp
        @if($configuredRoles->isEmpty())
            <p class="text-xs text-slate-400">Belum ada kepala. Buat role baru, tandai kepalanya, dan isi keahlian tiap staff.</p>
        @else
            <div class="space-y-3">
                @foreach($configuredRoles as $roleItem)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div>
                                <p class="text-xs font-semibold text-slate-900">{{ $roleItem->name }}</p>
                                <p class="text-[11px] text-slate-500">
                                    {{ $roleItem->users_count }} orang
                                    @if($roleItem->head) · Kepala: {{ $roleItem->head->name }} @endif
                                </p>
                            </div>
                            <button type="button" @click="openManage({{ $roleItem->id }})"
                                    class="px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                                Ubah staff
                            </button>
                        </div>
                        @if($roleItem->relationLoaded('users'))
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($roleItem->users as $person)
                                    @if((int) $person->id !== (int) $roleItem->head_user_id)
                                        <span class="px-2 py-1 rounded bg-slate-100 text-[11px] text-slate-700">
                                            {{ $person->name }}@if($person->keahlian) — {{ $person->keahlian }}@endif
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Tabel Kategori Layanan yang Bersih -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Nama Layanan</th>
                        <th class="py-3 px-4">Alur Hirarki Role</th>
                        <th class="py-3 px-4 w-36">Mode Alur</th>
                        <th class="py-3 px-4 w-56">Template Dokumen</th>
                        <th class="py-3 px-4 w-28 text-right">Aksi</th>
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
                        <tr class="hover:bg-slate-50/70 transition align-middle">
                            <!-- 1. Nama Layanan -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-900">{{ $cat->name }}</div>
                                @if($cat->description)
                                    <p class="text-[11px] text-slate-400 truncate max-w-xs mt-0.5" title="{{ $cat->description }}">
                                        {{ $cat->description }}
                                    </p>
                                @endif
                            </td>

                            <!-- 2. Alur Hirarki Role -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1 flex-wrap">
                                    @foreach($fullChain as $idx => $stepRole)
                                        @if($idx > 0)
                                            <span class="text-slate-300 text-[10px]">→</span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded text-[11px] font-medium {{ $idx === 0 ? 'bg-slate-100 text-slate-600' : 'bg-indigo-50 text-indigo-700 border border-indigo-100' }}">
                                            {{ $stepRole->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <!-- 3. Mode Alur -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($cat->workflow_type === 'tiered_approval')
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Approval Berjenjang
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                        Eskalasi
                                    </span>
                                @endif
                            </td>

                            <!-- 4. Template Dokumen -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($cat->hasTemplate())
                                    <div class="flex items-center gap-1.5">
                                        <button type="button"
                                                @click="openDocPreview('{{ route('categories.template.preview', $cat) }}', '{{ route('categories.template.download', $cat) }}', '{{ addslashes($cat->template_name ?? 'Template Layanan') }}')"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[11px] font-semibold transition cursor-pointer">
                                            <span>📎 Preview</span>
                                        </button>
                                        <a href="{{ route('categories.template.download', $cat) }}"
                                           class="px-2 py-1 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 text-[11px] font-medium transition">
                                            Unduh
                                        </a>
                                        @if($cat->is_attachment_required)
                                            <span class="text-[10px] font-semibold text-rose-600">Wajib</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400">—</span>
                                @endif
                            </td>

                            <!-- 5. Aksi -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button"
                                            @click="openEditCategory({{ Js::from($editPayload) }})"
                                            class="px-3 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-medium transition cursor-pointer">
                                        Edit
                                    </button>
                                    @if($cat->tickets_count === 0)
                                        <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST"
                                              onsubmit="return confirm('Hapus kategori layanan {{ addslashes($cat->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2.5 py-1 rounded-lg text-rose-600 hover:bg-rose-50 font-medium transition cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 px-4 text-center text-xs text-slate-400">
                                Belum ada kategori layanan. Klik <strong>+ Tambah Layanan</strong> di kanan atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: Tambah Layanan Baru -->
    <div x-show="addCategoryModal" x-cloak
         @keydown.escape.window="addCategoryModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="addCategoryModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Tambah Kategori Layanan</h3>
                <button type="button" @click="addCategoryModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">✕</button>
            </div>

            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <input type="hidden" name="color" value="indigo">

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Layanan</label>
                        <input type="text" name="name" required
                               placeholder="Contoh: Pengadaan Dana"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                    </div>
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Mode Alur</label>
                        <select name="workflow_type" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                            <option value="tiered_approval">Approval Berjenjang</option>
                            <option value="escalation">Eskalasi / Disposisi</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan Singkat <span class="font-normal text-slate-400">(Opsional)</span></label>
                    <input type="text" name="description"
                           placeholder="Deskripsi singkat layanan..."
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                </div>

                <!-- Tahapan Hirarki Role -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-700">Urutan Hirarki Role</label>
                        <button type="button"
                                @click="newSteps.push({{ $defaultYayasanRole?->id ?? 1 }})"
                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-500 cursor-pointer">
                            + Tambah Tahap
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(roleId, idx) in newSteps" :key="idx">
                            <div class="flex items-center gap-2">
                                <span class="w-20 text-[11px] font-medium text-slate-500 shrink-0"
                                      x-text="idx === 0 ? '1. Pemohon' : (idx + 1) + '. Tahap ' + idx"></span>
                                <select name="workflow_roles[]" x-model.number="newSteps[idx]"
                                        class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button"
                                        x-show="newSteps.length > 2"
                                        @click="newSteps.splice(idx, 1)"
                                        class="p-1.5 text-slate-400 hover:text-rose-600">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="px-3 py-2 rounded-lg bg-slate-50 border border-slate-200/70 text-[11px] text-slate-600">
                        Alur: <strong class="text-slate-900" x-text="formatPreviewChain(newSteps)"></strong>
                    </div>
                </div>

                <!-- Upload Template Dokumen -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">
                        Template Dokumen Layanan <span class="font-normal text-slate-400">(Opsional — PDF, DOCX, XLSX)</span>
                    </label>
                    <label class="flex items-center justify-between px-3.5 py-2 rounded-lg border border-dashed border-slate-300 hover:border-slate-400 bg-slate-50/50 cursor-pointer transition">
                        <span class="text-xs text-slate-600 truncate" x-text="newTemplateName || 'Pilih file template dokumen...'"></span>
                        <span class="text-[11px] font-semibold text-indigo-600 shrink-0 ml-2">Pilih File</span>
                        <input type="file" name="template_file" class="hidden"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip"
                               @change="newTemplateName = $event.target.files[0]?.name || ''">
                    </label>

                    <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                        <input type="checkbox" name="is_attachment_required" value="1" checked
                               class="rounded border-slate-300 text-indigo-600">
                        <span>Wajibkan pemohon mengunggah dokumen yang sudah diisi</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addCategoryModal = false"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                        Simpan Layanan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Edit Layanan -->
    <div x-show="editCategoryModal" x-cloak
         @keydown.escape.window="editCategoryModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="editCategoryModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Edit Kategori Layanan</h3>
                <button type="button" @click="editCategoryModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">✕</button>
            </div>

            <form :action="editForm.actionUrl" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Layanan</label>
                        <input type="text" name="name" x-model="editForm.name" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                    </div>
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Mode Alur</label>
                        <select name="workflow_type" x-model="editForm.workflow_type"
                                class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                            <option value="tiered_approval">Approval Berjenjang</option>
                            <option value="escalation">Eskalasi / Disposisi</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan Singkat</label>
                    <input type="text" name="description" x-model="editForm.description"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                </div>

                <!-- Tahapan Hirarki Role -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-700">Urutan Hirarki Role</label>
                        <button type="button"
                                @click="editForm.steps.push({{ $defaultYayasanRole?->id ?? 1 }})"
                                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-500 cursor-pointer">
                            + Tambah Tahap
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(roleId, idx) in editForm.steps" :key="idx">
                            <div class="flex items-center gap-2">
                                <span class="w-20 text-[11px] font-medium text-slate-500 shrink-0"
                                      x-text="idx === 0 ? '1. Pemohon' : (idx + 1) + '. Tahap ' + idx"></span>
                                <select name="workflow_roles[]" x-model.number="editForm.steps[idx]"
                                        class="flex-1 px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button"
                                        x-show="editForm.steps.length > 2"
                                        @click="editForm.steps.splice(idx, 1)"
                                        class="p-1.5 text-slate-400 hover:text-rose-600">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="px-3 py-2 rounded-lg bg-slate-50 border border-slate-200/70 text-[11px] text-slate-600">
                        Alur: <strong class="text-slate-900" x-text="formatPreviewChain(editForm.steps)"></strong>
                    </div>
                </div>

                <!-- Template Dokumen -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-700">Template Dokumen Layanan</label>
                        <template x-if="editForm.has_template">
                            <span class="text-[11px] text-emerald-700 font-medium truncate max-w-[200px]" x-text="editForm.template_name"></span>
                        </template>
                    </div>

                    <label class="flex items-center justify-between px-3.5 py-2 rounded-lg border border-dashed border-slate-300 hover:border-slate-400 bg-slate-50/50 cursor-pointer transition">
                        <span class="text-xs text-slate-600 truncate"
                              x-text="editForm.new_file_name || (editForm.has_template ? 'Ganti file template...' : 'Pilih file template dokumen...')"></span>
                        <span class="text-[11px] font-semibold text-indigo-600 shrink-0 ml-2">Pilih File</span>
                        <input type="file" name="template_file" class="hidden"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip"
                               @change="editForm.new_file_name = $event.target.files[0]?.name || ''">
                    </label>

                    <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                        <input type="checkbox" name="is_attachment_required" value="1"
                               x-model="editForm.is_attachment_required"
                               class="rounded border-slate-300 text-indigo-600">
                        <span>Wajibkan pemohon mengunggah dokumen yang sudah diisi</span>
                    </label>

                    <template x-if="editForm.has_template">
                        <label class="flex items-center gap-2 text-xs text-rose-600 cursor-pointer">
                            <input type="checkbox" name="remove_template" value="1"
                                   x-model="editForm.remove_template"
                                   class="rounded border-rose-300 text-rose-600">
                            <span>Hapus template dokumen saat ini</span>
                        </label>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editCategoryModal = false"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Tambah Role Baru -->
    <div x-show="addRoleModal" x-cloak
         @keydown.escape.window="addRoleModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="addRoleModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Tambah Role Baru</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Kepala meninjau tiap tiket dulu, lalu mengarahkan ke staff sesuai permintaan dan keahlian.</p>
                </div>
                <button type="button" @click="addRoleModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">✕</button>
            </div>

            <form action="{{ route('admin.hierarchy.store') }}" method="POST" class="p-6 space-y-3.5 overflow-y-auto">
                @csrf
                <input type="hidden" name="from_role_modal" value="1">
                <input type="hidden" name="badge_color" value="indigo">
                <input type="hidden" name="can_create_ticket" value="1">
                <input type="hidden" name="can_receive_ticket" value="1">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Role</label>
                    <input type="text" name="name" required value="{{ old('from_role_modal') ? old('name') : '' }}"
                           placeholder="Contoh: Admin IT"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan Singkat <span class="font-normal text-slate-400">(Opsional)</span></label>
                    <input type="text" name="description" value="{{ old('from_role_modal') ? old('description') : '' }}"
                           placeholder="Deskripsi singkat role..."
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                </div>

                <div class="pt-1 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-xs font-semibold text-slate-700">Staff / User</p>
                            <p class="text-[11px] text-slate-400">Password awal setiap staff: password</p>
                        </div>
                        <button type="button" @click="addStaffRow()"
                                class="px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                            + Staff
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(row, index) in staffRows" :key="index">
                            <div class="rounded-lg border border-slate-200 p-2 space-y-2">
                                <div class="grid grid-cols-[1fr_1fr_auto] gap-2">
                                    <input type="text" :name="`staff[${index}][name]`" x-model="row.name" placeholder="Nama"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                                    <input type="email" :name="`staff[${index}][email]`" x-model="row.email" placeholder="email@kampus.ac.id"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                                    <button type="button" @click="removeStaffRow(index)"
                                            class="px-2 rounded-lg text-slate-400 hover:text-rose-600 text-xs">Hapus</button>
                                </div>
                                <input type="text" :name="`staff[${index}][keahlian]`" x-model="row.keahlian" placeholder="Keahlian, contoh: jaringan / hardware / aplikasi"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                                <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-600">
                                    <input type="radio" name="head_index" :value="String(index)" x-model="headIndex">
                                    <span>Kepala — meninjau tiket dulu, baru mengarahkan</span>
                                </label>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pt-1">
                    <p class="text-[11px] text-slate-400 mb-1.5">Role tersedia saat ini:</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($roles as $r)
                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px]">{{ $r->name }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="addRoleModal = false"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                        Simpan Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="manageRoleModal" x-cloak
         @keydown.escape.window="manageRoleModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div @click.outside="manageRoleModal = false"
             class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-sm font-bold text-slate-900" x-text="'Staff & Prioritas — ' + manage.name"></h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Ubah staff penerima untuk nomor prioritas 1 sampai 8.</p>
                </div>
                <button type="button" @click="manageRoleModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700">✕</button>
            </div>
            <form :action="manage.actionUrl" method="POST" class="p-6 space-y-3.5 overflow-y-auto">
                @csrf
                @method('PUT')
                <input type="hidden" name="from_staff_modal" value="1">
                <input type="hidden" name="manage_role_id" :value="manage.id">

                <div>
                    <p class="text-xs font-semibold text-slate-700 mb-1.5">Staff saat ini</p>
                    <template x-if="manage.staff.length === 0">
                        <p class="text-[11px] text-slate-400">Belum ada staff.</p>
                    </template>
                    <div class="space-y-1">
                        <template x-for="person in manage.staff" :key="person.id">
                            <label class="flex items-center gap-2 text-xs text-slate-700">
                                <input type="radio" name="head_user_id" :value="String(person.id)" x-model="manage.headId">
                                <span><span class="font-medium" x-text="person.name"></span> · <span class="text-slate-400" x-text="person.keahlian || person.email"></span></span>
                                <span class="text-[11px] text-slate-400" x-show="String(manage.headId) === String(person.id)">Kepala</span>
                            </label>
                        </template>
                    </div>
                    <p class="text-[11px] text-amber-700 mt-1" x-show="manage.truncated">Daftar staff dipotong. Tambahkan staff baru di bawah jika perlu.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-slate-700">Tambah staff</p>
                        <button type="button" @click="addManageStaff()"
                                class="px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                            + Staff
                        </button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(row, index) in manage.newStaff" :key="index">
                            <div class="rounded-lg border border-slate-200 p-2 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" :name="`staff[${index}][name]`" x-model="row.name" placeholder="Nama staff"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                                    <input type="email" :name="`staff[${index}][email]`" x-model="row.email" placeholder="email@kampus.ac.id"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                                </div>
                                <input type="text" :name="`staff[${index}][keahlian]`" x-model="row.keahlian" placeholder="Keahlian staff"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
                                <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-600">
                                    <input type="radio" name="head_user_id" :value="'new-' + index" x-model="manage.headId">
                                    <span>Jadikan kepala</span>
                                </label>
                            </div>
                        </template>
                    </div>
                </div>

                <p class="text-[11px] text-slate-400">Siapa yang mengerjakan ditentukan kepala saat meninjau tiket, sesuai permintaan dan keahlian staff.</p>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="manageRoleModal = false"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                        Simpan Staff
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
