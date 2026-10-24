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

    <!-- Page Header Modern -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    @include('partials.icon', ['name' => 'adjustments-horizontal', 'class' => 'w-5 h-5'])
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900 tracking-tight">Kategori Layanan</h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Konfigurasi alur approval tiket, penetapan unit kerja / kepala peninjau, dan template dokumen persyaratan.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button" @click="addRoleModal = true"
                    class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-sm cursor-pointer flex items-center gap-1.5">
                @include('partials.icon', ['name' => 'plus-circle', 'class' => 'w-3.5 h-3.5 text-slate-500'])
                <span>Tambah Role</span>
            </button>
            <button type="button" @click="addCategoryModal = true"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition cursor-pointer flex items-center gap-1.5">
                @include('partials.icon', ['name' => 'squares-plus', 'class' => 'w-3.5 h-3.5 text-white'])
                <span>Tambah Layanan</span>
            </button>
        </div>
    </div>

    <!-- Card: Kepala Unit & Keahlian Staf (Delegasi & Triase Layanan) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0">
                    @include('partials.icon', ['name' => 'shield-check', 'class' => 'w-4 h-4'])
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Kepala Unit & Keahlian Staf</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Tiket masuk ditinjau oleh Kepala Unit terlebih dahulu, lalu didelegasikan ke teknisi/staf sesuai spesialisasi.</p>
                </div>
            </div>
        </div>

        @php $configuredRoles = $roles->filter(fn ($role) => $role->head_user_id || $role->priorityRoutes->isNotEmpty()); @endphp
        @if($configuredRoles->isEmpty())
            <div class="p-6 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200 text-xs text-slate-400">
                Belum ada unit dengan kepala yang dikonfigurasi. Klik <strong>+ Tambah Role</strong> untuk mendaftarkan peran baru dan menetapkan kepalanya.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($configuredRoles as $roleItem)
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 hover:bg-white hover:border-indigo-200 transition space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900 text-xs">{{ $roleItem->name }}</span>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                        {{ $roleItem->users_count }} anggota
                                    </span>
                                </div>
                                @if($roleItem->head)
                                    <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        <span>Kepala: <strong class="text-slate-900">{{ $roleItem->head->name }}</strong></span>
                                    </div>
                                @endif
                            </div>
                            <button type="button" @click="openManage({{ $roleItem->id }})"
                                    class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-[11px] font-semibold text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs shrink-0">
                                Kelola Staf &rarr;
                            </button>
                        </div>

                        @if($roleItem->relationLoaded('users'))
                            <div class="flex flex-wrap gap-1.5 pt-1 border-t border-slate-200/60">
                                @foreach($roleItem->users as $person)
                                    @if((int) $person->id !== (int) $roleItem->head_user_id)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white border border-slate-200/80 text-[11px] text-slate-700 shadow-2xs">
                                            <span class="font-medium text-slate-900">{{ $person->name }}</span>
                                            @if($person->keahlian)
                                                <span class="text-slate-400">&bull;</span>
                                                <span class="text-indigo-600 font-medium">{{ $person->keahlian }}</span>
                                            @endif
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

    <!-- Data Table: Kategori Layanan -->
    <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Daftar Kategori Layanan Kampus</h2>
                <p class="text-xs text-slate-500 mt-0.5">Semua permohonan layanan yang tersedia beserta alur tahapan approval dan syarat dokumen.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                Total {{ $categories->count() }} Layanan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Nama Layanan</th>
                        <th class="py-3 px-4">Alur Hirarki Role</th>
                        <th class="py-3 px-4 w-40">Mode Alur</th>
                        <th class="py-3 px-4 w-52">Template Dokumen</th>
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
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="font-bold text-slate-900 text-xs">{{ $cat->name }}</div>
                                @if($cat->description)
                                    <p class="text-[11px] text-slate-500 truncate mt-0.5" title="{{ $cat->description }}">
                                        {{ $cat->description }}
                                    </p>
                                @endif
                            </td>

                            <!-- 2. Alur Hirarki Role -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @foreach($fullChain as $idx => $stepRole)
                                        @if($idx > 0)
                                            <span class="text-slate-300 text-xs font-bold">&rarr;</span>
                                        @endif
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold {{ $idx === 0 ? 'bg-slate-100 text-slate-700' : 'bg-indigo-50 text-indigo-700 border border-indigo-200/80' }}">
                                            {{ $stepRole->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <!-- 3. Mode Alur -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($cat->workflow_type === 'tiered_approval')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Approval Berjenjang
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-violet-50 text-violet-700 border border-violet-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                                        Eskalasi / Disposisi
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
                                            <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded">Wajib</span>
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
                                            class="px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-slate-700 hover:text-indigo-700 text-xs font-semibold transition cursor-pointer">
                                        Edit
                                    </button>
                                    @if($cat->tickets_count === 0)
                                        <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST"
                                              onsubmit="return confirm('Hapus kategori layanan {{ addslashes($cat->name) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2 py-1 rounded-lg text-rose-600 hover:bg-rose-50 text-xs font-medium transition cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 px-4 text-center text-xs text-slate-400">
                                Belum ada kategori layanan terdaftar. Klik <strong>+ Tambah Layanan</strong> untuk memulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 1: Tambah Role Baru (REFACTORED CLEAN SAAS MODAL)     --}}
    {{-- ========================================================== --}}
    <div x-show="addRoleModal" x-cloak
         @keydown.escape.window="addRoleModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="addRoleModal = false"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center shadow-md shadow-indigo-500/20">
                        @include('partials.icon', ['name' => 'users', 'class' => 'w-5 h-5'])
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Tambah Role & Tim Unit</h3>
                        <p class="text-[11px] text-slate-500">Atur peran baru, kepala peninjau, dan personel pelaksana</p>
                    </div>
                </div>
                <button type="button" @click="addRoleModal = false"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.hierarchy.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <input type="hidden" name="from_role_modal" value="1">
                <input type="hidden" name="can_create_ticket" value="1">
                <input type="hidden" name="can_receive_ticket" value="1">

                <!-- Nama Role -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Role / Unit Kerja</label>
                    <input type="text" name="name" required value="{{ old('from_role_modal') ? old('name') : '' }}"
                           placeholder="Contoh: Admin IT, Unit BAAK, dsb."
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition text-slate-900 placeholder:text-slate-400">
                </div>

                <!-- Keterangan Singkat -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Keterangan Singkat <span class="font-normal text-slate-400">(Opsional)</span>
                    </label>
                    <input type="text" name="description" value="{{ old('from_role_modal') ? old('description') : '' }}"
                           placeholder="Deskripsi tugas dan tanggung jawab peran ini..."
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition text-slate-900 placeholder:text-slate-400">
                </div>

                <!-- Pilihan Warna Badge Role -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warna Badge Identitas</label>
                    <div class="flex items-center gap-2" x-data="{ selectedColor: 'indigo' }">
                        <input type="hidden" name="badge_color" :value="selectedColor">
                        @foreach(['indigo' => 'bg-indigo-600', 'emerald' => 'bg-emerald-600', 'violet' => 'bg-violet-600', 'sky' => 'bg-sky-600', 'amber' => 'bg-amber-600', 'rose' => 'bg-rose-600', 'teal' => 'bg-teal-600'] as $cName => $cClass)
                            <button type="button" @click="selectedColor = '{{ $cName }}'"
                                    class="w-6 h-6 rounded-full {{ $cClass }} transition-transform cursor-pointer flex items-center justify-center text-white"
                                    :class="selectedColor === '{{ $cName }}' ? 'ring-2 ring-offset-2 ring-slate-900 scale-110' : 'opacity-70 hover:opacity-100'">
                                <svg x-show="selectedColor === '{{ $cName }}'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Bagian Staff / User -->
                <div class="pt-3 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-2.5">
                        <div>
                            <p class="text-xs font-bold text-slate-800">Personel & Staf Pelaksana</p>
                            <p class="text-[11px] text-slate-500">Tentukan kepala (peninjau tiket pertama) dan staf pelaksana</p>
                        </div>
                        <button type="button" @click="addStaffRow()"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition cursor-pointer">
                            <span>+ Tambah Staf</span>
                        </button>
                    </div>

                    <!-- Staff Alert Tip -->
                    <div class="mb-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] text-slate-600 flex items-center gap-2">
                        <span class="text-indigo-600 font-bold">ℹ️</span>
                        <span>Password akun default staf baru: <code class="font-semibold text-slate-800">password</code></span>
                    </div>

                    <!-- Dynamic Staff Card Rows -->
                    <div class="space-y-2.5">
                        <template x-for="(row, index) in staffRows" :key="index">
                            <div class="rounded-xl border border-slate-200/80 p-3 bg-slate-50/50 hover:bg-white transition space-y-2">
                                <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 items-center">
                                    <input type="text" :name="`staff[${index}][name]`" x-model="row.name" placeholder="Nama Lengkap"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                    <input type="email" :name="`staff[${index}][email]`" x-model="row.email" placeholder="email@kampus.ac.id"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                    <button type="button" @click="removeStaffRow(index)"
                                            class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition text-xs shrink-0"
                                            title="Hapus baris staf">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <input type="text" :name="`staff[${index}][keahlian]`" x-model="row.keahlian"
                                       placeholder="Spesialisasi / Keahlian (cth: Jaringan, Hardware, Sistem SIAKAD)"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">

                                <!-- Radio Kepala Unit -->
                                <label class="flex items-center gap-2 p-2 rounded-lg border transition cursor-pointer select-none"
                                       :class="String(headIndex) === String(index) ? 'bg-indigo-50/80 border-indigo-200 text-indigo-900 font-semibold' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
                                    <input type="radio" name="head_index" :value="String(index)" x-model="headIndex"
                                           class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500/20">
                                    <span class="text-[11px]">Tandai sebagai <strong>Kepala Unit</strong> (peninjau tiket pertama sebelum diarahkan ke staf)</span>
                                </label>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Role Yang Sudah Tersedia -->
                <div class="pt-2">
                    <p class="text-[11px] font-semibold text-slate-500 mb-1.5">Role terdaftar saat ini:</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($roles as $r)
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-medium border border-slate-200/60">
                                {{ $r->name }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="addRoleModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition cursor-pointer flex items-center gap-1.5">
                        @include('partials.icon', ['name' => 'plus-circle', 'class' => 'w-3.5 h-3.5 text-white'])
                        <span>Simpan Role Baru</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 2: Tambah Kategori Layanan                           --}}
    {{-- ========================================================== --}}
    <div x-show="addCategoryModal" x-cloak
         @keydown.escape.window="addCategoryModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="addCategoryModal = false"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        @include('partials.icon', ['name' => 'squares-plus', 'class' => 'w-5 h-5'])
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Tambah Kategori Layanan</h3>
                        <p class="text-[11px] text-slate-500">Definisikan jenis layanan baru dan alur tahapan approval</p>
                    </div>
                </div>
                <button type="button" @click="addCategoryModal = false"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <input type="hidden" name="color" value="indigo">

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Layanan</label>
                        <input type="text" name="name" required placeholder="Contoh: Pengadaan Dana Ormawa"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                    </div>
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mode Alur</label>
                        <select name="workflow_type"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                            <option value="tiered_approval">Approval Berjenjang</option>
                            <option value="escalation">Eskalasi / Disposisi</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Keterangan Singkat <span class="font-normal text-slate-400">(Opsional)</span>
                    </label>
                    <input type="text" name="description" placeholder="Deskripsi singkat jenis permohonan ini..."
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                </div>

                <!-- Tahapan Hirarki Role -->
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-800">Urutan Tahapan Role</label>
                        <button type="button" @click="newSteps.push({{ $defaultYayasanRole?->id ?? 1 }})"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 cursor-pointer">
                            + Tambah Tahap
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(roleId, idx) in newSteps" :key="idx">
                            <div class="flex items-center gap-2">
                                <span class="w-24 text-[11px] font-semibold text-slate-500 shrink-0"
                                      x-text="idx === 0 ? '1. Pemohon' : (idx + 1) + '. Tahap ' + idx"></span>
                                <select name="workflow_roles[]" x-model.number="newSteps[idx]"
                                        class="flex-1 px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" x-show="newSteps.length > 2" @click="newSteps.splice(idx, 1)"
                                        class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition" title="Hapus tahap">
                                    &times;
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-slate-600">
                        Alur: <strong class="text-slate-900" x-text="formatPreviewChain(newSteps)"></strong>
                    </div>
                </div>

                <!-- Template Dokumen -->
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <label class="block text-xs font-bold text-slate-800">
                        Template Dokumen Layanan <span class="font-normal text-slate-400">(Opsional — PDF, DOCX, XLSX)</span>
                    </label>
                    <label class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border border-dashed border-slate-300 hover:border-indigo-400 bg-slate-50/50 hover:bg-indigo-50/30 cursor-pointer transition">
                        <span class="text-xs text-slate-600 truncate" x-text="newTemplateName || 'Pilih file template formulir...'"></span>
                        <span class="text-xs font-semibold text-indigo-600 shrink-0 ml-2">Pilih File</span>
                        <input type="file" name="template_file" class="hidden"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip"
                               @change="newTemplateName = $event.target.files[0]?.name || ''">
                    </label>

                    <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" name="is_attachment_required" value="1" checked
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Wajibkan pemohon melampirkan berkas yang sudah diisi</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="addCategoryModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition">
                        Simpan Layanan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 3: Edit Kategori Layanan                             --}}
    {{-- ========================================================== --}}
    <div x-show="editCategoryModal" x-cloak
         @keydown.escape.window="editCategoryModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="editCategoryModal = false"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        @include('partials.icon', ['name' => 'squares-plus', 'class' => 'w-5 h-5'])
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Edit Kategori Layanan</h3>
                        <p class="text-[11px] text-slate-500">Perbarui informasi layanan dan tahapan approval</p>
                    </div>
                </div>
                <button type="button" @click="editCategoryModal = false"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <form :action="editForm.actionUrl" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                    <div class="sm:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Layanan</label>
                        <input type="text" name="name" x-model="editForm.name" required
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                    </div>
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mode Alur</label>
                        <select name="workflow_type" x-model="editForm.workflow_type"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                            <option value="tiered_approval">Approval Berjenjang</option>
                            <option value="escalation">Eskalasi / Disposisi</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keterangan Singkat</label>
                    <input type="text" name="description" x-model="editForm.description"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
                </div>

                <!-- Tahapan Hirarki Role -->
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-800">Urutan Tahapan Role</label>
                        <button type="button" @click="editForm.steps.push({{ $defaultYayasanRole?->id ?? 1 }})"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 cursor-pointer">
                            + Tambah Tahap
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(roleId, idx) in editForm.steps" :key="idx">
                            <div class="flex items-center gap-2">
                                <span class="w-24 text-[11px] font-semibold text-slate-500 shrink-0"
                                      x-text="idx === 0 ? '1. Pemohon' : (idx + 1) + '. Tahap ' + idx"></span>
                                <select name="workflow_roles[]" x-model.number="editForm.steps[idx]"
                                        class="flex-1 px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" x-show="editForm.steps.length > 2" @click="editForm.steps.splice(idx, 1)"
                                        class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition" title="Hapus tahap">
                                    &times;
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-slate-600">
                        Alur: <strong class="text-slate-900" x-text="formatPreviewChain(editForm.steps)"></strong>
                    </div>
                </div>

                <!-- Template Dokumen -->
                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <label class="block text-xs font-bold text-slate-800">Template Dokumen Layanan</label>
                    <label class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border border-dashed border-slate-300 hover:border-indigo-400 bg-slate-50/50 hover:bg-indigo-50/30 cursor-pointer transition">
                        <span class="text-xs text-slate-600 truncate" x-text="editForm.new_file_name || editForm.template_name || 'Unggah template baru jika ingin mengganti...'"></span>
                        <span class="text-xs font-semibold text-indigo-600 shrink-0 ml-2">Ganti File</span>
                        <input type="file" name="template_file" class="hidden"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip"
                               @change="editForm.new_file_name = $event.target.files[0]?.name || ''">
                    </label>

                    <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" name="is_attachment_required" value="1"
                               x-model="editForm.is_attachment_required"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Wajibkan pemohon mengunggah berkas persyaratan</span>
                    </label>

                    <template x-if="editForm.has_template">
                        <label class="flex items-center gap-2 text-xs text-rose-600 cursor-pointer select-none">
                            <input type="checkbox" name="remove_template" value="1"
                                   x-model="editForm.remove_template"
                                   class="rounded border-rose-300 text-rose-600">
                            <span>Hapus template dokumen yang ada saat ini</span>
                        </label>
                    </template>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="editCategoryModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 4: Kelola Staf & Prioritas Role                      --}}
    {{-- ========================================================== --}}
    <div x-show="manageRoleModal" x-cloak
         @keydown.escape.window="manageRoleModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="manageRoleModal = false"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                        @include('partials.icon', ['name' => 'users', 'class' => 'w-5 h-5'])
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900" x-text="'Kelola Staf — ' + manage.name"></h3>
                        <p class="text-[11px] text-slate-500">Tentukan Kepala Unit dan tambahkan staf pelaksana teknis</p>
                    </div>
                </div>
                <button type="button" @click="manageRoleModal = false"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <form :action="manage.actionUrl" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')
                <input type="hidden" name="from_staff_modal" value="1">
                <input type="hidden" name="manage_role_id" :value="manage.id">

                <!-- Staf Saat Ini -->
                <div>
                    <p class="text-xs font-bold text-slate-800 mb-2">Pilih Kepala Unit Saat Ini</p>
                    <template x-if="manage.staff.length === 0">
                        <p class="text-xs text-slate-400 py-2">Belum ada anggota staf yang terdaftar.</p>
                    </template>
                    <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                        <template x-for="person in manage.staff" :key="person.id">
                            <label class="flex items-center justify-between p-2.5 rounded-xl border transition cursor-pointer select-none"
                                   :class="String(manage.headId) === String(person.id) ? 'bg-indigo-50/80 border-indigo-200 text-indigo-900 font-semibold' : 'bg-slate-50/50 border-slate-200 text-slate-700 hover:bg-white'">
                                <div class="flex items-center gap-2.5 min-w-0 pr-2">
                                    <input type="radio" name="head_user_id" :value="String(person.id)" x-model="manage.headId"
                                           class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500/20">
                                    <div class="truncate">
                                        <p class="text-xs font-semibold truncate" x-text="person.name"></p>
                                        <p class="text-[10px] text-slate-500 truncate" x-text="person.keahlian ? ('Keahlian: ' + person.keahlian) : person.email"></p>
                                    </div>
                                </div>
                                <span x-show="String(manage.headId) === String(person.id)"
                                      class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 shrink-0">
                                    Kepala Unit
                                </span>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- Tambah Staf Baru -->
                <div class="pt-3 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-2.5">
                        <p class="text-xs font-bold text-slate-800">Tambah Staf Baru</p>
                        <button type="button" @click="addManageStaff()"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition cursor-pointer">
                            <span>+ Tambah Baris</span>
                        </button>
                    </div>

                    <div class="space-y-2.5">
                        <template x-for="(row, index) in manage.newStaff" :key="index">
                            <div class="rounded-xl border border-slate-200/80 p-3 bg-slate-50/50 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" :name="`staff[${index}][name]`" x-model="row.name" placeholder="Nama staf"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                    <input type="email" :name="`staff[${index}][email]`" x-model="row.email" placeholder="email@kampus.ac.id"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                </div>
                                <input type="text" :name="`staff[${index}][keahlian]`" x-model="row.keahlian" placeholder="Keahlian / Spesialisasi teknis"
                                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                <label class="flex items-center gap-2 p-2 rounded-lg border bg-white border-slate-200 text-slate-700 text-[11px] cursor-pointer">
                                    <input type="radio" name="head_user_id" :value="'new-' + index" x-model="manage.headId"
                                           class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500/20">
                                    <span>Jadikan sebagai <strong>Kepala Unit</strong></span>
                                </label>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="manageRoleModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition">
                        Simpan Staf
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
