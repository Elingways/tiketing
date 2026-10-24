@extends('layouts.app')

@section('content')

<div x-data="{
    addRoleModal: {{ old('from_role_modal') ? 'true' : 'false' }},
    manageRoleModal: false,
    manage: { id: null, name: '', actionUrl: '', staff: [], newStaff: [], headId: '', truncated: false },
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
            headId: data.head_user_id || '',
            truncated: !!data.truncated,
        };
        this.manageRoleModal = true;
    },
    addManageStaff() {
        this.manage.newStaff.push({ name: '', email: '', keahlian: '' });
    }
}">

    <!-- Page Header Modern -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                @include('partials.icon', ['name' => 'users', 'class' => 'w-5 h-5'])
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-900 tracking-tight">Manajemen Role & Unit</h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Kelola peran operasional, tetapkan Kepala Unit (peninjau pertama tiket), dan atur personel staf pelaksana beserta keahliannya.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button" @click="addRoleModal = true"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white text-xs font-semibold shadow-sm shadow-indigo-600/20 transition cursor-pointer flex items-center gap-1.5">
                @include('partials.icon', ['name' => 'plus-circle', 'class' => 'w-3.5 h-3.5 text-white'])
                <span>Tambah Role Baru</span>
            </button>
        </div>
    </div>

    <!-- 3 Quick KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Role Terdaftar</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $roles->count() }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Peran operasional sistem</p>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Unit Dengan Kepala</p>
            <p class="text-2xl font-extrabold text-indigo-600 mt-1">{{ $configuredRoles->count() }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Siap triase & disposisi tiket</p>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Anggota & Staf</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ number_format($roles->sum('users_count')) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Pengguna aktif dalam peran</p>
        </div>
    </div>

    <!-- Section 1: Kepala Unit & Keahlian Staf (Grid Kartu Unit) -->
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

        @if($configuredRoles->isEmpty())
            <div class="p-8 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200 text-xs text-slate-400">
                Belum ada unit dengan kepala yang dikonfigurasi. Klik <strong>+ Tambah Role Baru</strong> untuk mendaftarkan peran baru dan menetapkan kepalanya.
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
                                        <span>Kepala Unit: <strong class="text-slate-900">{{ $roleItem->head->name }}</strong></span>
                                    </div>
                                @endif
                            </div>
                            <button type="button" @click="openManage({{ $roleItem->id }})"
                                    class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-[11px] font-semibold text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs shrink-0 cursor-pointer">
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

    <!-- Section 2: Tabel Seluruh Role Sistem -->
    <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Daftar Seluruh Role Sistem</h2>
                <p class="text-xs text-slate-500 mt-0.5">Semua peran pengguna yang terdaftar beserta hak akses dan penanggung jawabnya.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4">Nama Role</th>
                        <th class="py-3 px-4">Kepala Peninjau</th>
                        <th class="py-3 px-4">Jumlah Anggota</th>
                        <th class="py-3 px-4">Kapabilitas Tiket</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($roles as $r)
                        <tr class="hover:bg-slate-50/70 transition align-middle">
                            <!-- 1. Nama Role -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-{{ $r->badge_color ?? 'indigo' }}-600"></span>
                                    <span class="font-bold text-slate-900 text-xs">{{ $r->name }}</span>
                                </div>
                                @if($r->description)
                                    <p class="text-[11px] text-slate-400 mt-0.5 truncate max-w-xs">{{ $r->description }}</p>
                                @endif
                            </td>

                            <!-- 2. Kepala Unit -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($r->head)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        {{ $r->head->name }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">Belum ditentukan</span>
                                @endif
                            </td>

                            <!-- 3. Jumlah Anggota -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-semibold text-slate-800">{{ $r->users_count }}</span>
                                <span class="text-slate-400 text-[11px]">pengguna</span>
                            </td>

                            <!-- 4. Kapabilitas Tiket -->
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

                            <!-- 5. Aksi -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <button type="button" @click="openManage({{ $r->id }})"
                                        class="px-2.5 py-1 rounded-lg bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-slate-700 hover:text-indigo-700 text-xs font-semibold transition cursor-pointer">
                                    Kelola Staf &rarr;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-xs text-slate-400">Belum ada role yang terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 1: Tambah Role Baru                                  --}}
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
                        <h3 class="text-sm font-bold text-slate-900">Tambah Role & Unit Baru</h3>
                        <p class="text-[11px] text-slate-500">Daftarkan peran operasional baru dan akun penanggung jawabnya</p>
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
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Role / Unit Kerja <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required value="{{ old('from_role_modal') ? old('name') : '' }}"
                           placeholder="Contoh: Unit Sarpras, Admin IT, Bagian Akademik, dsb."
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition text-slate-900 placeholder:text-slate-400">
                </div>

                <!-- Atasan Hirarki -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Atasan Hirarki <span class="font-normal text-slate-400">(Opsional - Terhubung ke atasan)</span>
                    </label>
                    <select name="parent_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition text-slate-900 bg-white">
                        <option value="">-- Puncak Hirarki (Tanpa Atasan / Root) --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ old('from_role_modal') && old('parent_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Keterangan Singkat -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Keterangan Singkat <span class="font-normal text-slate-400">(Opsional)</span>
                    </label>
                    <input type="text" name="description" value="{{ old('from_role_modal') ? old('description') : '' }}"
                           placeholder="Deskripsi tugas dan ruang lingkup peran ini..."
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

                <!-- Penanggung Jawab / Kepala Unit -->
                <div class="pt-4 border-t border-slate-100 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                        <p class="text-xs font-bold text-slate-900">Penanggung Jawab / Kepala Unit (Login Akun)</p>
                    </div>

                    <!-- Alert Tip Default Password -->
                    <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-100 text-[11px] text-indigo-950 space-y-1">
                        <p class="font-semibold flex items-center gap-1.5">
                            <span>👤</span>
                            <span>Akun ini otomatis dibuatkan dan langsung dapat dipakai untuk login:</span>
                        </p>
                        <p class="text-indigo-800">
                            Password default login: <code class="font-mono font-bold bg-white px-1.5 py-0.5 rounded border border-indigo-200 text-indigo-900">password</code> (dapat diganti di menu edit profile kapan saja).
                        </p>
                    </div>

                    <!-- Input Nama Pemegang Role -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap Penanggung Jawab <span class="text-rose-500">*</span></label>
                        <input type="text" name="head_name" required value="{{ old('from_role_modal') ? old('head_name') : '' }}"
                               placeholder="Contoh: Budi Santoso, S.Kom."
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition text-slate-900 placeholder:text-slate-400">
                    </div>

                    <!-- Input Email Pemegang Role -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Login Penanggung Jawab <span class="text-rose-500">*</span></label>
                        <input type="email" name="head_email" required value="{{ old('from_role_modal') ? old('head_email') : '' }}"
                               placeholder="budi@kampus.ac.id"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition text-slate-900 placeholder:text-slate-400">
                    </div>

                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-[11px] text-slate-500 flex items-center gap-2">
                        <span>💡</span>
                        <span>Personel teknis & staf pelaksana tambahan dapat ditambahkan nanti melalui tombol <strong>Kelola Staf</strong> pada tabel.</span>
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
            </form>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- MODAL 2: Kelola Staf & Prioritas Role                      --}}
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
                                <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 items-center">
                                    <input type="text" :name="`staff[${index}][name]`" x-model="row.name" placeholder="Nama staf"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                    <input type="email" :name="`staff[${index}][email]`" x-model="row.email" placeholder="email@kampus.ac.id"
                                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600">
                                    <button type="button" @click="manage.newStaff.splice(index, 1)"
                                            class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition text-xs shrink-0"
                                            title="Batalkan baris staf ini">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
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
