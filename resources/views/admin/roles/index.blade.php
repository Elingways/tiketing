@extends('layouts.app')

@section('content')
@php
    $rolesData = $roles->mapWithKeys(fn ($r) => [
        $r->id => [
            'id' => $r->id,
            'name' => $r->name,
            'actionUrl' => "/admin/hierarchy/roles/{$r->id}/staff",
            'head_user_id' => $r->head_user_id ? (string) $r->head_user_id : '',
            'staff' => $r->users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'keahlian' => $u->keahlian,
            ])->values(),
        ]
    ]);
@endphp

<script>
    function roleManagerComponent() {
        return {
            addRoleModal: {{ old('from_role_modal') ? 'true' : 'false' }},
            divisionModal: {{ old('from_division_modal') ? 'true' : 'false' }},
            editDivisionModal: false,
            editRoleModal: false,
            manageRoleModal: false,
            selectedDivisionId: '',
            selectedDivisionName: '',
            selectedPosition: 'staff',
            editDivision: { id: null, name: '', badge_color: 'indigo', description: '', actionUrl: '' },
            editRoleData: { id: null, name: '', division_id: '', parent_id: '', badge_color: 'indigo', description: '', can_create_ticket: true, can_receive_ticket: true, actionUrl: '' },
            staffIds: @json(old('staff_user_ids', ['']) ?: ['']),
            rolesMap: @json($rolesData),
            manage: { id: null, name: '', actionUrl: '', staff: [], newStaff: [], headId: '', truncated: false },
            openAddRoleForDivision(divId, divName, position = 'staff') {
                this.selectedDivisionId = String(divId);
                this.selectedDivisionName = divName;
                this.selectedPosition = position;
                this.addRoleModal = true;
            },
            openEditDivision(div) {
                this.editDivision = {
                    id: div.id,
                    name: div.name,
                    badge_color: div.badge_color || 'indigo',
                    description: div.description || '',
                    actionUrl: '/admin/hierarchy/divisions/' + div.id,
                };
                this.editDivisionModal = true;
            },
            openEditRole(role) {
                this.editRoleData = {
                    id: role.id,
                    name: role.name,
                    division_id: role.division_id ? String(role.division_id) : '',
                    parent_id: role.parent_id ? String(role.parent_id) : '',
                    badge_color: role.badge_color || 'indigo',
                    description: role.description || '',
                    can_create_ticket: !!role.can_create_ticket,
                    can_receive_ticket: !!role.can_receive_ticket,
                    actionUrl: '/admin/hierarchy/roles/' + role.id,
                };
                this.editRoleModal = true;
            },
            openManage(roleId) {
                const cached = this.rolesMap && this.rolesMap[roleId];
                if (cached) {
                    this.manage = {
                        id: cached.id,
                        name: cached.name,
                        actionUrl: cached.actionUrl,
                        staff: JSON.parse(JSON.stringify(cached.staff || [])),
                        newStaff: [],
                        headId: cached.head_user_id || (cached.staff[0]?.id ? String(cached.staff[0].id) : ''),
                        truncated: false,
                    };
                    this.manageRoleModal = true;
                    return;
                }

                const self = this;
                fetch('/admin/hierarchy/roles/' + roleId + '/staff', {
                    headers: { 'Accept': 'application/json' },
                })
                .then(function(res) {
                    if (!res.ok) throw new Error('Status ' + res.status);
                    return res.json();
                })
                .then(function(data) {
                    self.manage = {
                        id: data.id,
                        name: data.name,
                        actionUrl: '/admin/hierarchy/roles/' + data.id + '/staff',
                        staff: data.staff || [],
                        newStaff: [],
                        headId: data.head_user_id || '',
                        truncated: !!data.truncated,
                    };
                    self.manageRoleModal = true;
                })
                .catch(function(err) {
                    console.error('Error fetching staff:', err);
                    alert('Gagal memuat data staf role: ' + err.message);
                });
            },
            addManageStaff() {
                this.manage.newStaff.push({ name: '', email: '', password: '', keahlian: '' });
            },
            addDivisionStaff() {
                this.staffIds.push('');
            },
            removeDivisionStaff(index) {
                if (this.staffIds.length > 1) {
                    this.staffIds.splice(index, 1);
                }
            }
        };
    }
</script>

<div x-data="roleManagerComponent()">

    <!-- Page Header Modern & Clean -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Manajemen Divisi</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Kelola divisi kampus beserta pimpinan (Kepala), supervisor, dan staf pelaksana tiket.
            </p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button" @click="selectedDivisionId = ''; selectedDivisionName = ''; selectedPosition = 'staff'; addRoleModal = true"
                    class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
                Tambah Role Baru
            </button>
            <button type="button" @click="divisionModal = true"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition cursor-pointer">
                <span>+ Tambah Divisi Baru</span>
            </button>
        </div>
    </div>

    <!-- 3 Quick KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Divisi Kampus</p>
            <p class="text-2xl font-extrabold text-indigo-600 mt-1">{{ $divisions->count() }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Unit penanganan tiket kampus</p>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Role Terdaftar</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $roles->count() }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Jabatan & peran fungsional</p>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Anggota & Staf</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ number_format($roles->sum('users_count')) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Pengguna aktif dalam peran</p>
        </div>
    </div>

    <!-- Section: Divisi Kampus -->
    <div class="space-y-6 mb-8">
        @if($divisions->isEmpty())
            <div class="p-10 text-center rounded-2xl bg-white border border-dashed border-slate-200 text-slate-500 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900">Belum Ada Divisi Terdaftar</h3>
                <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 mb-4">
                    Buat divisi pertama (misal: Divisi IT, Divisi Sarana & Prasarana, Divisi Keuangan) lalu tambahkan role serta akun ke dalamnya.
                </p>
                <button type="button" @click="divisionModal = true"
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition cursor-pointer">
                    + Tambah Divisi Baru
                </button>
            </div>
        @else
            @foreach($divisions as $division)
                @php
                    $headMember = $division->members->firstWhere('position', 'head');
                    $spvMember = $division->members->firstWhere('position', 'spv');
                    $staffMembers = $division->members->where('position', 'staff');

                    // Roles belonging to this division
                    $divRoles = $division->roles->isNotEmpty()
                        ? $division->roles
                        : $roles->where('division_id', $division->id);

                    if ($divRoles->isEmpty()) {
                        $divRoles = collect([$division->staffRole, $division->spvRole, $division->headRole])->filter()->unique('id');
                    }

                    // Identifikasi Role Kepala
                    $headRole = $division->headRole;
                    if (!$headRole) {
                        $headRole = $divRoles->first(function ($r) use ($headMember) {
                            if ($headMember && $headMember->user?->role_id === $r->id) return true;
                            $lower = strtolower($r->name);
                            return str_contains($lower, 'kepala') || str_contains($lower, 'head') || str_contains($lower, 'admin');
                        });
                    }

                    // Identifikasi Role SPV
                    $spvRole = $division->spvRole;
                    if (!$spvRole || ($headRole && $spvRole->id === $headRole->id && $divRoles->count() > 1)) {
                        $foundSpv = $divRoles->first(function ($r) use ($headRole, $spvMember) {
                            if ($headRole && $r->id === $headRole->id) return false;
                            if ($spvMember && $spvMember->user?->role_id === $r->id) return true;
                            $lower = strtolower($r->name);
                            return str_contains($lower, 'spv') || str_contains($lower, 'supervisor');
                        });
                        if ($foundSpv) $spvRole = $foundSpv;
                    }

                    // Role Staff / Pelaksana
                    $staffRoles = $divRoles->filter(function ($r) use ($headRole, $spvRole) {
                        if ($headRole && $r->id === $headRole->id) return false;
                        if ($spvRole && $r->id === $spvRole->id) return false;
                        return true;
                    });

                    if ($divRoles->count() === 1 && $headRole && $spvRole && $headRole->id === $spvRole->id) {
                        $staffRoles = collect([$headRole]);
                    }

                    $headUser = $headMember?->user ?? $headRole?->head ?? $headRole?->users->first();
                    $spvUser = $spvMember?->user ?? $spvRole?->head ?? $spvRole?->users->first();
                    $peakName = $headUser?->name ?? 'Kepala';
                @endphp

                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden mb-6">
                    <!-- Division Header -->
                    <div class="p-5 bg-slate-50/60 border-b border-slate-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h2 class="text-base font-bold text-slate-900 tracking-tight">{{ $division->name }}</h2>
                                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200/80">
                                    Puncak: {{ $peakName }}
                                </span>
                                @if($division->categories->isNotEmpty())
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ $division->categories->count() }} Layanan
                                    </span>
                                @endif
                            </div>
                            @if($division->description)
                                <p class="text-xs text-slate-500 mt-1 max-w-xl">{{ $division->description }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                            <button type="button" @click="openAddRoleForDivision({{ $division->id }}, '{{ addslashes($division->name) }}', 'staff')"
                                    class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition cursor-pointer">
                                <span>+ Tambah Role Baru</span>
                            </button>
                            <button type="button" @click="openEditDivision({{ Js::from($division) }})"
                                    class="px-3 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold transition cursor-pointer">
                                Edit Divisi
                            </button>
                            @if($division->categories->isEmpty())
                                <form action="{{ route('admin.hierarchy.divisions.destroy', $division) }}" method="POST"
                                      onsubmit="return confirm('Hapus divisi {{ addslashes($division->name) }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-2.5 py-2 rounded-xl text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 text-xs font-medium transition cursor-pointer">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Division Structured Tiers -->
                    <div class="p-5 space-y-3.5">
                        <!-- TIER 1: KEPALA DIVISI -->
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                            Kepala Divisi
                                        </span>
                                        <span class="text-[10px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200/60">
                                            Puncak Approval
                                        </span>
                                        @if($headRole)
                                            <span class="text-xs font-bold text-slate-900">· {{ $headRole->name }}</span>
                                        @endif
                                    </div>
                                    @if($headRole && ($headUser || $headRole->head))
                                        @php $hUser = $headUser ?? $headRole->head; @endphp
                                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                            <span class="text-xs font-bold text-slate-800">{{ $hUser->name }}</span>
                                            <span class="text-xs text-slate-500">({{ $hUser->email }})</span>
                                            @if($hUser->keahlian)
                                                <span class="text-slate-600 text-xs font-medium">• {{ $hUser->keahlian }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-400 mt-1 italic">Belum ada akun Kepala Divisi yang ditentukan.</p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 self-end md:self-auto shrink-0">
                                    @if($headRole)
                                        <button type="button" @click="openEditRole({{ Js::from($headRole) }})"
                                                class="px-2.5 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition cursor-pointer">
                                            Edit Role
                                        </button>
                                        <form action="{{ route('admin.hierarchy.destroy', $headRole) }}" method="POST"
                                              onsubmit="return confirm('Hapus role \'{{ $headRole->name }}\'?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2 py-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs font-medium transition cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" @click="openAddRoleForDivision({{ $division->id }}, '{{ addslashes($division->name) }}', 'head')"
                                                class="px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition cursor-pointer">
                                            + Tambah Kepala Divisi
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- TIER 2: SUPERVISOR (SPV) -->
                        <div class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                            Supervisor (SPV)
                                        </span>
                                        <span class="text-[10px] font-semibold text-sky-700 bg-sky-50 px-2 py-0.5 rounded border border-sky-200/60">
                                            Review & Eskalasi
                                        </span>
                                        @if($spvRole)
                                            <span class="text-xs font-bold text-slate-900">· {{ $spvRole->name }}</span>
                                        @endif
                                    </div>
                                    @if($spvRole && ($spvUser || $spvRole->head))
                                        @php $sUser = $spvUser ?? $spvRole->head; @endphp
                                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                            <span class="text-xs font-bold text-slate-800">{{ $sUser->name }}</span>
                                            <span class="text-xs text-slate-500">({{ $sUser->email }})</span>
                                            @if($sUser->keahlian)
                                                <span class="text-slate-600 text-xs font-medium">• {{ $sUser->keahlian }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <p class="text-xs text-slate-400 mt-1 italic">Belum ada akun Supervisor (Opsional).</p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 self-end md:self-auto shrink-0">
                                    @if($spvRole)
                                        <button type="button" @click="openEditRole({{ Js::from($spvRole) }})"
                                                class="px-2.5 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition cursor-pointer">
                                            Edit Role
                                        </button>
                                        <form action="{{ route('admin.hierarchy.destroy', $spvRole) }}" method="POST"
                                              onsubmit="return confirm('Hapus role \'{{ $spvRole->name }}\'?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2 py-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 text-xs font-medium transition cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" @click="openAddRoleForDivision({{ $division->id }}, '{{ addslashes($division->name) }}', 'spv')"
                                                class="px-3 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition cursor-pointer">
                                            + Tambah Supervisor
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- TIER 3: TIM PELAKSANA & STAFF OPERASIONAL -->
                        <div class="rounded-xl border border-slate-200 bg-white p-4 space-y-3">
                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                        Tim Pelaksana & Staff Operasional
                                    </span>
                                    <p class="text-xs text-slate-400 mt-1">Personel pelaksana tiket di divisi ini</p>
                                </div>
                                <button type="button" @click="openAddRoleForDivision({{ $division->id }}, '{{ addslashes($division->name) }}', 'staff')"
                                        class="px-3 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 text-xs font-semibold transition cursor-pointer">
                                    + Tambah Role Staff
                                </button>
                            </div>

                            @if($staffRoles->isEmpty())
                                <div class="py-4 text-center text-xs text-slate-400">
                                    Belum ada role Staff di divisi ini. Klik <strong>+ Tambah Role Staff</strong> untuk menambahkan tim pelaksana teknis.
                                </div>
                            @else
                                <div class="space-y-2">
                                    @foreach($staffRoles as $stRole)
                                        <div class="p-3.5 bg-slate-50/50 rounded-xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-xs font-bold text-slate-900">{{ $stRole->name }}</span>
                                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-200/80 text-slate-700">
                                                        {{ $stRole->users_count }} Anggota Staf
                                                    </span>
                                                </div>
                                                <!-- Daftar Anggota Staf -->
                                                <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                                                    @forelse($stRole->users as $u)
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-[11px]">
                                                            <span class="font-semibold text-slate-800">{{ $u->name }}</span>
                                                            <span class="text-slate-500 font-normal">({{ $u->email }})</span>
                                                            @if($u->keahlian)
                                                                <span class="text-slate-600 font-medium">• {{ $u->keahlian }}</span>
                                                            @endif
                                                        </span>
                                                    @empty
                                                        @if($stRole->head)
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-[11px]">
                                                                <span class="font-semibold text-slate-800">{{ $stRole->head->name }}</span>
                                                                <span class="text-slate-500 font-normal">({{ $stRole->head->email }})</span>
                                                            </span>
                                                        @else
                                                            <span class="text-slate-400 italic text-[11px]">Belum ada staf terdaftar di role ini.</span>
                                                        @endif
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1.5 self-end md:self-auto shrink-0">
                                                <button type="button" @click="openManage({{ $stRole->id }})"
                                                        class="px-3 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 text-xs font-semibold transition cursor-pointer">
                                                    Kelola Staf
                                                </button>
                                                <button type="button" @click="openEditRole({{ Js::from($stRole) }})"
                                                        class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition cursor-pointer">
                                                    Edit
                                                </button>
                                                <form action="{{ route('admin.hierarchy.destroy', $stRole) }}" method="POST"
                                                      onsubmit="return confirm('Hapus role \'{{ $stRole->name }}\'?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="px-2 py-1.5 text-slate-400 hover:text-rose-600 text-xs font-medium transition cursor-pointer">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- Section: Role Global / Sistem Lainnya -->
    @php
        $globalRoles = $roles->filter(fn ($r) => empty($r->division_id) && !$divisions->pluck('id')->contains($r->division_id));
    @endphp
    @if($globalRoles->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm mb-6">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Role Global & Sistem Lainnya</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Role yang berlaku umum di luar divisi operasional (contoh: Mahasiswa, Dosen, Yayasan, Superuser).</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-4">Nama Role</th>
                            <th class="py-3 px-4">Pemegang Role / Email Akun</th>
                            <th class="py-3 px-4">Jumlah Anggota</th>
                            <th class="py-3 px-4">Kapabilitas Tiket</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($globalRoles as $r)
                            <tr class="hover:bg-slate-50/70 transition align-middle">
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-{{ $r->badge_color ?? 'indigo' }}-600"></span>
                                        <span class="font-bold text-slate-900 text-xs">{{ $r->name }}</span>
                                    </div>
                                    @if($r->description)
                                        <p class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs">{{ $r->description }}</p>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($r->head)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                            {{ $r->head->name }} ({{ $r->head->email }})
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="font-semibold text-slate-800">{{ $r->users_count }}</span>
                                    <span class="text-slate-400 text-[11px]">pengguna</span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        @if($r->can_create_ticket)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">Buat Tiket</span>
                                        @endif
                                        @if($r->can_receive_ticket)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">Terima Tiket</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" @click="openManage({{ $r->id }})"
                                                class="px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-slate-700 hover:text-indigo-700 text-xs font-semibold transition cursor-pointer">
                                            Kelola Staf
                                        </button>
                                        <button type="button" @click="openEditRole({{ Js::from($r) }})"
                                                class="px-2 py-1 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition cursor-pointer">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.hierarchy.destroy', $r) }}" method="POST"
                                              onsubmit="return confirm('Hapus role \'{{ $r->name }}\'?');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2 py-1 rounded-lg bg-slate-50 hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-slate-500 hover:text-rose-600 text-xs font-semibold transition cursor-pointer"
                                                    title="Hapus Role">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ========================================================== --}}
    {{-- MODAL 1: Tambah Role Baru                                  --}}
    {{-- ========================================================== --}}
    <div x-show="addRoleModal" x-cloak
         @keydown.escape.window="addRoleModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="addRoleModal = false"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Tambah Role Baru</h3>
                    <p class="text-[11px] text-slate-500">
                        <span x-show="selectedDivisionName" x-text="'Menambahkan role ke divisi: ' + selectedDivisionName"></span>
                        <span x-show="!selectedDivisionName">Tentukan divisi, nama role, serta akun email penanggung jawabnya.</span>
                    </p>
                </div>
                <button type="button" @click="addRoleModal = false"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.hierarchy.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                <input type="hidden" name="from_role_modal" value="1">

                <!-- Pilih Divisi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Divisi Kampus</label>
                    <select name="division_id" x-model="selectedDivisionId" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                        <option value="">— Role Global / Tanpa Divisi —</option>
                        @foreach($divisions as $divOption)
                            <option value="{{ $divOption->id }}">{{ $divOption->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Posisi / Tingkatan dalam Divisi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tingkatan Posisi dalam Divisi</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="p-2.5 rounded-xl border cursor-pointer text-center transition"
                               :class="selectedPosition === 'head' ? 'border-indigo-600 bg-indigo-50 text-indigo-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                            <input type="radio" name="position" value="head" x-model="selectedPosition" class="hidden">
                            <span class="text-xs block font-bold">Kepala Divisi</span>
                            <span class="text-[10px] text-slate-500 block">Puncak Approval</span>
                        </label>
                        <label class="p-2.5 rounded-xl border cursor-pointer text-center transition"
                               :class="selectedPosition === 'spv' ? 'border-indigo-600 bg-indigo-50 text-indigo-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                            <input type="radio" name="position" value="spv" x-model="selectedPosition" class="hidden">
                            <span class="text-xs block font-bold">Supervisor</span>
                            <span class="text-[10px] text-slate-500 block">Review & Eskalasi</span>
                        </label>
                        <label class="p-2.5 rounded-xl border cursor-pointer text-center transition"
                               :class="selectedPosition === 'staff' ? 'border-indigo-600 bg-indigo-50 text-indigo-900 font-bold' : 'border-slate-200 hover:bg-slate-50 text-slate-700'">
                            <input type="radio" name="position" value="staff" x-model="selectedPosition" class="hidden">
                            <span class="text-xs block font-bold">Staff Pelaksana</span>
                            <span class="text-[10px] text-slate-500 block">Tim Teknis Tiket</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Role <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required value="{{ old('from_role_modal') ? old('name') : '' }}"
                           placeholder="Contoh: Admin IT / Supervisor / Staff Teknisi"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Atasan Role (Opsional)</label>
                    <select name="parent_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                        <option value="">— Tanpa atasan langsung —</option>
                        @foreach($roles as $roleOption)
                            <option value="{{ $roleOption->id }}">{{ $roleOption->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warna Badge</label>
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

                <!-- Akun Pemegang Role & Email -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2">
                    <label class="block text-xs font-bold text-slate-800">Akun Penanggung Jawab Role (Langsung Dibuat)</label>
                    <input type="text" name="head_name" value="{{ old('from_role_modal') ? old('head_name') : '' }}" placeholder="Nama lengkap pengguna"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                    <input type="email" name="head_email" value="{{ old('from_role_modal') ? old('head_email') : '' }}" placeholder="email@kampus.ac.id"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                    <input type="password" name="password" placeholder="Kata sandi akun (opsional, default: password)"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                    <p class="text-[11px] text-slate-500">Akun ini langsung terdaftar dengan role di atas dan dapat digunakan login ke sistem.</p>
                </div>

                <div class="flex items-center gap-4 text-xs text-slate-700">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="can_create_ticket" value="1" checked class="rounded border-slate-300 text-indigo-600">
                        <span>Dapat buat tiket</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="can_receive_ticket" value="1" checked class="rounded border-slate-300 text-indigo-600">
                        <span>Dapat menerima tiket</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="addRoleModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition cursor-pointer">
                        Simpan Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 2: Tambah Divisi Baru                                --}}
    {{-- ========================================================== --}}
    <div x-show="divisionModal" x-cloak
         @keydown.escape.window="divisionModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="divisionModal = false"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Tambah Divisi Baru</h3>
                    <p class="text-[11px] text-slate-500">Buat divisi kampus. Selanjutnya Anda dapat menambahkan role dan akun ke dalamnya.</p>
                </div>
                <button type="button" @click="divisionModal = false"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <form action="{{ route('admin.hierarchy.divisions.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto"
                  x-data="{ directPlot: false }">
                @csrf
                <input type="hidden" name="from_division_modal" value="1">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Divisi <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required value="{{ old('from_division_modal') ? old('name') : '' }}"
                           placeholder="Contoh: Divisi IT / Divisi Sarpras / Divisi Keuangan"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warna Aksen Divisi</label>
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

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi Divisi <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <textarea name="description" rows="2" placeholder="Keterangan ruang lingkup penanganan divisi ini..."
                              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs"></textarea>
                </div>

                <!-- Petakan Langsung Kepala, SPV, dan Staff (Opsional) -->
                <div class="pt-3 border-t border-slate-100">
                    <button type="button" @click="directPlot = !directPlot"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1.5 cursor-pointer">
                        <span x-text="directPlot ? '▾ Sembunyikan pemetaan akun instan' : '▸ Petakan langsung dari akun yang sudah ada (Opsional)'"></span>
                    </button>
                    <p class="text-[11px] text-slate-400 mt-0.5">Jika Anda sudah memiliki user akun, Anda bisa langsung memilih Kepala, SPV, dan staf.</p>
                </div>

                <div x-show="directPlot" class="space-y-3 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <div>
                        <label class="block text-xs font-bold text-slate-900 mb-1">1. Kepala — puncak divisi</label>
                        <select name="head_user_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                            @include('partials.division-person-options', ['placeholder' => 'Pilih Kepala', 'selected' => old('head_user_id')])
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-900 mb-1">2. SPV</label>
                        <select name="spv_user_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                            @include('partials.division-person-options', ['placeholder' => 'Pilih SPV', 'selected' => old('spv_user_id')])
                        </select>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <label class="block text-xs font-bold text-slate-900">3. Staff</label>
                            <button type="button" @click="addDivisionStaff()"
                                    class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                + Tambah Staff
                            </button>
                        </div>
                        <template x-for="(staffId, index) in staffIds" :key="index">
                            <div class="flex items-center gap-2 mb-2">
                                <select name="staff_user_ids[]" x-model="staffIds[index]"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                                    @include('partials.division-person-options', ['placeholder' => 'Pilih staff'])
                                </select>
                                <button type="button" @click="removeDivisionStaff(index)" x-show="staffIds.length > 1"
                                        class="shrink-0 px-2 py-2 text-[11px] font-semibold text-rose-600 cursor-pointer">
                                    Hapus
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="divisionModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition cursor-pointer">
                        Simpan Divisi
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 3: Edit Divisi                                       --}}
    {{-- ========================================================== --}}
    <div x-show="editDivisionModal" x-cloak
         @keydown.escape.window="editDivisionModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="editDivisionModal = false"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Edit Divisi</h3>
                    <p class="text-[11px] text-slate-500">Perbarui nama atau keterangan divisi kampus</p>
                </div>
                <button type="button" @click="editDivisionModal = false"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <form :action="editDivision.actionUrl" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Divisi <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editDivision.name" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warna Aksen</label>
                    <input type="hidden" name="badge_color" :value="editDivision.badge_color">
                    <div class="flex items-center gap-2">
                        @foreach(['indigo' => 'bg-indigo-600', 'emerald' => 'bg-emerald-600', 'violet' => 'bg-violet-600', 'sky' => 'bg-sky-600', 'amber' => 'bg-amber-600', 'rose' => 'bg-rose-600', 'teal' => 'bg-teal-600'] as $cName => $cClass)
                            <button type="button" @click="editDivision.badge_color = '{{ $cName }}'"
                                    class="w-6 h-6 rounded-full {{ $cClass }} transition-transform cursor-pointer flex items-center justify-center text-white"
                                    :class="editDivision.badge_color === '{{ $cName }}' ? 'ring-2 ring-offset-2 ring-slate-900 scale-110' : 'opacity-70 hover:opacity-100'">
                                <svg x-show="editDivision.badge_color === '{{ $cName }}'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi Divisi</label>
                    <textarea name="description" x-model="editDivision.description" rows="2"
                              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="editDivisionModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 4: Edit Role                                         --}}
    {{-- ========================================================== --}}
    <div x-show="editRoleModal" x-cloak
         @keydown.escape.window="editRoleModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="editRoleModal = false"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Edit Role</h3>
                    <p class="text-[11px] text-slate-500">Perbarui nama, divisi, atasan langsung, dan kapabilitas tiket</p>
                </div>
                <button type="button" @click="editRoleModal = false"
                        class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition">
                    &times;
                </button>
            </div>

            <form :action="editRoleData.actionUrl" method="POST" class="p-6 space-y-4 overflow-y-auto">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Role <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editRoleData.name" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Divisi Kampus</label>
                    <select name="division_id" x-model="editRoleData.division_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                        <option value="">— Role Global / Tanpa Divisi —</option>
                        @foreach($divisions as $divOption)
                            <option value="{{ $divOption->id }}">{{ $divOption->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Atasan Role</label>
                    <select name="parent_id" x-model="editRoleData.parent_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs bg-white">
                        <option value="">— Tanpa atasan langsung —</option>
                        @foreach($roles as $roleOption)
                            <option value="{{ $roleOption->id }}">{{ $roleOption->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warna Badge</label>
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="badge_color" :value="editRoleData.badge_color">
                        @foreach(['indigo' => 'bg-indigo-600', 'emerald' => 'bg-emerald-600', 'violet' => 'bg-violet-600', 'sky' => 'bg-sky-600', 'amber' => 'bg-amber-600', 'rose' => 'bg-rose-600', 'teal' => 'bg-teal-600'] as $cName => $cClass)
                            <button type="button" @click="editRoleData.badge_color = '{{ $cName }}'"
                                    class="w-6 h-6 rounded-full {{ $cClass }} transition-transform cursor-pointer flex items-center justify-center text-white"
                                    :class="editRoleData.badge_color === '{{ $cName }}' ? 'ring-2 ring-offset-2 ring-slate-900 scale-110' : 'opacity-70 hover:opacity-100'">
                                <svg x-show="editRoleData.badge_color === '{{ $cName }}'" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi Singkat</label>
                    <textarea name="description" x-model="editRoleData.description" rows="2"
                              class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs"></textarea>
                </div>

                <div class="flex items-center gap-4 text-xs text-slate-700">
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="can_create_ticket" value="1" x-model="editRoleData.can_create_ticket" class="rounded border-slate-300 text-indigo-600">
                        <span>Dapat buat tiket</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-pointer">
                        <input type="checkbox" name="can_receive_ticket" value="1" x-model="editRoleData.can_receive_ticket" class="rounded border-slate-300 text-indigo-600">
                        <span>Dapat menerima tiket</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" @click="editRoleModal = false"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm transition cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 5: Kelola Staf (Khusus Tim Pelaksana)                --}}
    {{-- ========================================================== --}}
    <div x-show="manageRoleModal" x-cloak
         @keydown.escape.window="manageRoleModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        <div @click.outside="manageRoleModal = false"
             class="bg-white rounded-2xl border border-slate-200/80 shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/50">
                <div>
                    <h3 class="text-sm font-bold text-slate-900" x-text="'Kelola Anggota Staf — ' + manage.name"></h3>
                    <p class="text-[11px] text-slate-500">Daftarkan personel teknisi & pelaksana ke dalam role ini</p>
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
                    <p class="text-xs font-bold text-slate-800 mb-1">Daftar Anggota Staf Terdaftar</p>
                    <p class="text-[11px] text-slate-500 mb-2">Klik ikon sampah untuk menghapus atau melepaskan staf dari role ini.</p>
                    <template x-if="manage.staff.length === 0">
                        <p class="text-xs text-slate-400 py-2">Belum ada anggota staf yang terdaftar.</p>
                    </template>
                    <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                        <template x-for="person in manage.staff" :key="person.id">
                            <div class="flex items-center justify-between p-2.5 rounded-xl border bg-slate-50/50 border-slate-200 text-slate-700 hover:bg-white transition">
                                <label class="flex items-center gap-2.5 min-w-0 pr-2 cursor-pointer flex-1 select-none">
                                    <input type="radio" name="head_user_id" :value="String(person.id)" x-model="manage.headId"
                                           class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500/20 shrink-0">
                                    <div class="truncate">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="text-xs font-semibold text-slate-900 truncate" x-text="person.name"></span>
                                            <span class="text-[10px] text-slate-500 font-normal truncate" x-text="'• ' + person.email"></span>
                                        </div>
                                        <p class="text-[10px] text-slate-500 truncate mt-0.5" x-text="person.keahlian ? ('Keahlian: ' + person.keahlian) : 'Staf Operasional'"></p>
                                    </div>
                                </label>
                                <div class="flex items-center gap-2 shrink-0">
                                    <form :action="`/admin/hierarchy/roles/${manage.id}/staff/${person.id}`" method="POST"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus staf ini dari role?');"
                                          class="inline" @click.stop>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                                title="Hapus staf dari role">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Tambah Staf Baru -->
                <div class="pt-3 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-xs font-bold text-slate-800">Tambah Staf Baru</p>
                            <p class="text-[11px] text-slate-500">Daftarkan personel staf pelaksana teknis ke role ini</p>
                        </div>
                        <button type="button" @click="addManageStaff()"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition cursor-pointer">
                            <span>+ Tambah Baris</span>
                        </button>
                    </div>

                    <!-- Notice Password Login -->
                    <div class="mb-3 p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-slate-700 flex items-start gap-2">
                        <div class="space-y-0.5">
                            <span class="font-semibold block text-slate-900">Informasi Login Staf Baru:</span>
                            <span class="text-slate-600">Akun dapat langsung login dengan email terdaftar dan password default: <code class="font-mono font-bold bg-white px-1.5 py-0.5 rounded border border-slate-200 text-slate-900">password</code> (atau tentukan password khusus pada kolom password).</span>
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <template x-for="(row, index) in manage.newStaff" :key="index">
                            <div class="rounded-xl border border-slate-200/80 p-3 bg-slate-50/50 space-y-2">
                                <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 items-center">
                                    <input type="text" :name="`staff[${index}][name]`" x-model="row.name" placeholder="Nama lengkap staf" required
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                    <input type="email" :name="`staff[${index}][email]`" x-model="row.email" placeholder="email@kampus.ac.id" required
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                    <button type="button" @click="manage.newStaff.splice(index, 1)"
                                            class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition text-xs shrink-0"
                                            title="Batalkan baris staf ini">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <input type="text" :name="`staff[${index}][keahlian]`" x-model="row.keahlian" placeholder="Keahlian / Spesialisasi teknis (opsional)"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                    <input type="text" :name="`staff[${index}][password]`" x-model="row.password" placeholder="Password login (default: password)"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                </div>
                                <label class="flex items-center gap-2 p-2 rounded-lg border bg-white border-slate-200 text-slate-700 text-[11px] cursor-pointer">
                                    <input type="radio" name="head_user_id" :value="'new-' + index" x-model="manage.headId"
                                           class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500/20">
                                    <span>Tandai sebagai <strong>Penanggung Jawab / Lead</strong></span>
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
