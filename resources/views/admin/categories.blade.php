@extends('layouts.app')

@section('content')
@php
    $rolesMap = $roles->keyBy('id')->map(fn ($r) => [
        'id' => $r->id,
        'name' => $r->name,
        'slug' => $r->slug,
        'division_id' => $r->division_id,
        'division_name' => $r->division?->name,
    ]);

    $divisionOptions = ($divisions ?? collect())->map(function ($division) use ($roles) {
        $divRoles = $division->roles->isNotEmpty()
            ? $division->roles
            : $roles->where('division_id', $division->id);

        if ($divRoles->isEmpty()) {
            $divRoles = collect([$division->staffRole, $division->spvRole, $division->headRole])->filter();
        }

        // Cari role pimpinan (SPV atau Kepala)
        $leaderRole = $divRoles->first(fn ($r) => str_contains(strtolower($r->name), 'spv') || str_contains(strtolower($r->name), 'supervisor'))
            ?? $divRoles->first(fn ($r) => str_contains(strtolower($r->name), 'kepala') || str_contains(strtolower($r->name), 'head'))
            ?? $division->spvRole
            ?? $division->headRole;

        // Cari role staff
        $staffRole = $divRoles->first(fn ($r) => !$leaderRole || (int) $r->id !== (int) $leaderRole->id)
            ?? $division->staffRole
            ?? $divRoles->last();

        $defaultChain = collect([$leaderRole?->id, $staffRole?->id])
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'id' => $division->id,
            'name' => $division->name,
            'badge_color' => $division->badge_color ?? 'indigo',
            'peak' => $division->headRole?->head?->name,
            'chain' => $division->workflowRoleIds(),
            'default_chain' => !empty($defaultChain) ? $defaultChain : $division->workflowRoleIds(),
            'roles' => $divRoles->map(function ($r) {
                $lower = strtolower($r->name);
                $isSpv = str_contains($lower, 'spv') || str_contains($lower, 'supervisor');
                $isHead = str_contains($lower, 'kepala') || str_contains($lower, 'head');
                $isLeadership = $isSpv || $isHead;
                $prefix = $isLeadership ? 'Pimpinan Divisi (Kepala & SPV) · ' : 'Staff Pelaksana · ';

                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'label' => $prefix . $r->name,
                    'is_leadership' => $isLeadership,
                    'head_name' => $r->head?->name ?? $r->users->first()?->name,
                ];
            })->values(),
        ];
    })->values();
@endphp

<div x-data="{
    addCategoryModal: false,
    editCategoryModal: false,
    rolesMap: {{ Js::from($rolesMap) }},
    divisions: {{ Js::from($divisionOptions) }},
    divisionId: '{{ ($divisions ?? collect())->first()?->id }}',
    newSteps: [],
    newTemplateName: '',
    editForm: {
        id: null,
        actionUrl: '',
        name: '',
        description: '',
        workflow_type: 'tiered_approval',
        division_id: '',
        steps: [],
        has_template: false,
        template_name: '',
        is_attachment_required: true,
        remove_template: false,
        new_file_name: ''
    },
    init() {
        this.updateStepsForDivision(this.divisionId, 'new');
    },
    onDivisionChange() {
        this.updateStepsForDivision(this.divisionId, 'new');
    },
    onEditDivisionChange() {
        this.updateStepsForDivision(this.editForm.division_id, 'edit');
    },
    getRolesForDivision(divId) {
        const found = this.divisions.find(d => String(d.id) === String(divId));
        if (found && found.roles && found.roles.length) {
            return found.roles;
        }
        return Object.values(this.rolesMap);
    },
    isLeadershipRole(roleId, divId) {
        const roles = this.getRolesForDivision(divId || this.divisionId);
        const match = roles.find(r => String(r.id) === String(roleId));
        return match ? match.is_leadership : false;
    },
    getStepBadgeLabel(roleId, divId) {
        const roles = this.getRolesForDivision(divId || this.divisionId);
        const match = roles.find(r => String(r.id) === String(roleId));
        if (match) {
            return match.is_leadership ? 'Pimpinan (' + match.name + ')' : match.name;
        }
        return this.rolesMap[roleId]?.name || ('Role #' + roleId);
    },
    updateStepsForDivision(divId, target) {
        const found = this.divisions.find(d => String(d.id) === String(divId));
        let defaultStepIds = [];
        if (found && found.default_chain && found.default_chain.length) {
            defaultStepIds = [...found.default_chain];
        } else if (found && found.chain && found.chain.length) {
            defaultStepIds = [...found.chain];
        } else if (found && found.roles && found.roles.length) {
            defaultStepIds = found.roles.map(r => r.id);
        }
        if (target === 'new') {
            this.newSteps = defaultStepIds;
        } else if (target === 'edit') {
            this.editForm.steps = defaultStepIds;
        }
    },
    addStep(target) {
        const divId = target === 'new' ? this.divisionId : this.editForm.division_id;
        const available = this.getRolesForDivision(divId);
        const nextRole = available.length ? available[0].id : '';
        if (target === 'new') {
            this.newSteps.push(nextRole);
        } else {
            this.editForm.steps.push(nextRole);
        }
    },
    removeStep(target, index) {
        if (target === 'new') {
            this.newSteps.splice(index, 1);
        } else {
            this.editForm.steps.splice(index, 1);
        }
    },
    openEditCategory(cat) {
        this.editForm.id = cat.id;
        this.editForm.actionUrl = cat.actionUrl;
        this.editForm.name = cat.name;
        this.editForm.description = cat.description || '';
        this.editForm.workflow_type = cat.workflow_type;
        this.editForm.division_id = cat.division_id ? String(cat.division_id) : '';
        this.editForm.steps = (cat.steps && cat.steps.length) ? [...cat.steps] : [];
        if (this.editForm.steps.length === 0 && this.editForm.division_id) {
            this.updateStepsForDivision(this.editForm.division_id, 'edit');
        }
        this.editForm.has_template = cat.has_template;
        this.editForm.template_name = cat.template_name || '';
        this.editForm.is_attachment_required = cat.is_attachment_required;
        this.editForm.remove_template = false;
        this.editForm.new_file_name = '';
        this.editCategoryModal = true;
    },
    divisionPlot(id) {
        const found = this.divisions.find(item => String(item.id) === String(id));
        if (!found) return 'Pilih divisi yang sudah dibuat.';
        const peak = found.peak ? ' · Puncak: ' + found.peak : '';
        if (found.roles && found.roles.length) {
            return found.roles.map(r => r.name).join(' → ') + peak;
        }
        return (found.chain || []).map(rId => this.rolesMap[rId]?.name || rId).join(' → ') + peak;
    }
}" >

    <!-- Page Header Modern -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0 border border-teal-100">
                @include('partials.icon', ['name' => 'squares-plus', 'class' => 'w-5 h-5'])
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-900 tracking-tight">Kategori Layanan</h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Konfigurasi alur approval tiket bertingkat, penugasan target penanganan, dan berkas template persyaratan.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button" @click="addCategoryModal = true"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition cursor-pointer flex items-center gap-1.5">
                @include('partials.icon', ['name' => 'plus-circle', 'class' => 'w-3.5 h-3.5 text-white'])
                <span>Tambah Layanan Baru</span>
            </button>
        </div>
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
                                'division_id' => $cat->division_id,
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
                                        @php
                                            $isLead = str_contains(strtolower($stepRole->name), 'spv') || str_contains(strtolower($stepRole->name), 'supervisor')
                                                || str_contains(strtolower($stepRole->name), 'kepala') || str_contains(strtolower($stepRole->name), 'head');
                                        @endphp
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold {{ $isLead ? 'bg-amber-50 text-amber-800 border border-amber-200/80' : ($idx === 0 ? 'bg-slate-100 text-slate-700' : 'bg-indigo-50 text-indigo-700 border border-indigo-200/80') }}">
                                            {{ $isLead ? 'Pimpinan (' . $stepRole->name . ')' : $stepRole->name }}
                                        </span>
                                    @endforeach
                                </div>
                                @if($cat->division)
                                    <p class="mt-1 text-[11px] text-amber-800">Divisi {{ $cat->division->name }} · Puncak {{ $cat->division->headRole?->head?->name ?? 'Kepala' }}</p>
                                @endif
                            </td>

                            <!-- 3. Mode Alur -->
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
                                Belum ada kategori layanan terdaftar. Klik <strong>+ Tambah Layanan Baru</strong> untuk memulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 1: Tambah Kategori Layanan Baru                      --}}
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
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
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

                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <label class="text-xs font-bold text-slate-800">1. Pilih Divisi Penanganan <span class="text-rose-500">*</span></label>
                    @if(($divisions ?? collect())->isEmpty())
                        <p class="text-[11px] text-slate-500">Belum ada divisi. Buat dulu di Manajemen Divisi agar role divisi terplot.</p>
                    @else
                        <select name="division_id" x-model="divisionId" @change="onDivisionChange()" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>

                        <!-- 2. Hirarki Alur Penanganan Layanan -->
                        <div class="mt-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-xs font-bold text-slate-900 block">2. Susun Hirarki Alur Layanan</label>
                                    <p class="text-[11px] text-slate-500">Tentukan urutan role penangan tiket untuk layanan ini</p>
                                </div>
                                <button type="button" @click="addStep('new')"
                                        class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[11px] font-semibold transition cursor-pointer">
                                    + Tambah Tahap
                                </button>
                            </div>

                            <template x-if="newSteps.length === 0">
                                <p class="text-[11px] text-slate-400 italic">Belum ada tahapan alur. Klik '+ Tambah Tahap'.</p>
                            </template>

                            <div class="space-y-2">
                                <template x-for="(stepId, idx) in newSteps" :key="idx">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                                        <select name="workflow_roles[]" x-model="newSteps[idx]" required
                                                class="flex-1 px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                                            <option value="">-- Pilih Role Divisi --</option>
                                            <template x-for="r in getRolesForDivision(divisionId)" :key="r.id">
                                                <option :value="r.id" x-text="(r.label || r.name) + (r.head_name ? ' (' + r.head_name + ')' : '')"></option>
                                            </template>
                                        </select>
                                        <button type="button" @click="removeStep('new', idx)" x-show="newSteps.length > 1"
                                                class="px-2 py-1 text-slate-400 hover:text-rose-600 font-bold transition cursor-pointer" title="Hapus tahap ini">
                                            &times;
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <div class="pt-2 border-t border-slate-200/60 text-[11px] text-slate-600 flex items-center gap-1.5 flex-wrap">
                                <span class="font-semibold text-slate-700">Alur:</span>
                                <template x-for="(stepId, idx) in newSteps" :key="'prev-' + idx">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="px-2 py-0.5 rounded-md font-semibold text-[10px]"
                                              :class="isLeadershipRole(stepId, divisionId) ? 'bg-amber-100 text-amber-900 border border-amber-200' : 'bg-indigo-100 text-indigo-800'"
                                              x-text="getStepBadgeLabel(stepId, divisionId)"></span>
                                        <span x-show="idx < newSteps.length - 1" class="text-slate-400 font-bold">&rarr;</span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    @endif
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
    {{-- MODAL 2: Edit Kategori Layanan                             --}}
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
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition">
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

                <div class="space-y-2 pt-2 border-t border-slate-100">
                    <label class="text-xs font-bold text-slate-800">1. Divisi Penanganan</label>
                    @if(($divisions ?? collect())->isEmpty())
                        <p class="text-[11px] text-slate-500">Belum ada divisi. Buat dulu di Manajemen Divisi.</p>
                    @else
                        <select name="division_id" x-model="editForm.division_id" @change="onEditDivisionChange()"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                            <option value="">-- Tanpa Divisi Khusus --</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </select>

                        <!-- Hirarki Khusus Layanan Ini -->
                        <div class="mt-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-xs font-bold text-slate-900 block">2. Alur Hirarki Layanan</label>
                                    <p class="text-[11px] text-slate-500">Sesuaikan urutan role penangan tiket untuk layanan ini</p>
                                </div>
                                <button type="button" @click="addStep('edit')"
                                        class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[11px] font-semibold transition cursor-pointer">
                                    + Tambah Tahap
                                </button>
                            </div>

                            <template x-if="editForm.steps.length === 0">
                                <p class="text-[11px] text-slate-400 italic">Belum ada tahapan alur. Klik '+ Tambah Tahap'.</p>
                            </template>

                            <div class="space-y-2">
                                <template x-for="(stepId, idx) in editForm.steps" :key="idx">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                                        <select name="workflow_roles[]" x-model="editForm.steps[idx]" required
                                                class="flex-1 px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white">
                                            <option value="">-- Pilih Role --</option>
                                            <template x-for="r in getRolesForDivision(editForm.division_id)" :key="r.id">
                                                <option :value="r.id" x-text="(r.label || r.name) + (r.head_name ? ' (' + r.head_name + ')' : '')"></option>
                                            </template>
                                        </select>
                                        <button type="button" @click="removeStep('edit', idx)" x-show="editForm.steps.length > 1"
                                                class="px-2 py-1 text-slate-400 hover:text-rose-600 font-bold transition cursor-pointer" title="Hapus tahap ini">
                                            &times;
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <div class="pt-2 border-t border-slate-200/60 text-[11px] text-slate-600 flex items-center gap-1.5 flex-wrap">
                                <span class="font-semibold text-slate-700">Alur:</span>
                                <template x-for="(stepId, idx) in editForm.steps" :key="'edit-prev-' + idx">
                                    <span class="inline-flex items-center gap-1">
                                        <span class="px-2 py-0.5 rounded-md font-semibold text-[10px]"
                                              :class="isLeadershipRole(stepId, editForm.division_id) ? 'bg-amber-100 text-amber-900 border border-amber-200' : 'bg-indigo-100 text-indigo-800'"
                                              x-text="getStepBadgeLabel(stepId, editForm.division_id)"></span>
                                        <span x-show="idx < editForm.steps.length - 1" class="text-slate-400 font-bold">&rarr;</span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    @endif
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

</div>
@endsection
