@extends('layouts.app')

@section('title', 'Profil Pengguna — SuperTicket')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Pengaturan Profil</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola informasi pribadi, kontak, foto profil, dan kredensial akun Anda.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition shadow-2xs">
            &larr; Kembali ke Dashboard
        </a>
    </div>

    <!-- Main Profile Form -->
    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data"
          x-data="{
              photoPreview: null,
              previewPhoto(event) {
                  const file = event.target.files[0];
                  if (file) {
                      const reader = new FileReader();
                      reader.onload = (e) => {
                          this.photoPreview = e.target.result;
                      };
                      reader.readAsDataURL(file);
                  }
              }
          }"
          class="space-y-6">
        @csrf

        <!-- Card 1: Foto Profil & Info Cepat -->
        <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs">
            <h2 class="text-sm font-bold text-slate-900">Foto Profil</h2>
            <p class="text-xs text-slate-400 mt-0.5">Foto ini akan tampil di sudut kiri atas, pojok kanan atas, serta riwayat aktivitas tiket Anda.</p>

            <div class="mt-5 flex flex-col sm:flex-row items-start sm:items-center gap-5">
                <!-- Avatar Preview -->
                <div class="relative shrink-0">
                    <template x-if="photoPreview">
                        <img :src="photoPreview" alt="Preview Foto" class="w-20 h-20 rounded-2xl object-cover ring-2 ring-indigo-500 shadow-md">
                    </template>
                    <template x-if="!photoPreview">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-2xl object-cover ring-2 ring-slate-200 shadow-md">
                        @else
                            <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-white font-extrabold text-xl flex items-center justify-center ring-2 ring-indigo-100 shadow-md">
                                {{ $user->initials() }}
                            </div>
                        @endif
                    </template>
                </div>

                <!-- Upload Input & Requirements -->
                <div class="space-y-2 flex-1">
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100/80 text-indigo-700 text-xs font-semibold border border-indigo-200/80 cursor-pointer transition shadow-2xs">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                            </svg>
                            <span>Pilih Foto Baru</span>
                            <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif" @change="previewPhoto" class="hidden">
                        </label>

                        @if($user->avatar_path)
                            <label class="inline-flex items-center gap-1.5 text-xs text-rose-600 hover:text-rose-800 cursor-pointer">
                                <input type="checkbox" name="remove_avatar" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                <span>Hapus Foto</span>
                            </label>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Format yang didukung: PNG, JPG, WEBP, atau GIF (Maksimal 2MB). Foto akan dipotong secara simetris otomatis.
                    </p>
                </div>
            </div>
        </div>

        <!-- Card 2: Informasi Pribadi & Pekerjaan -->
        <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Informasi Pribadi & Kontak</h2>
                <p class="text-xs text-slate-400 mt-0.5">Perbarui nama lengkap dan nomor kontak untuk koordinasi layanan tiket.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Email <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Contoh: 081234567890"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Identitas (NIM / NIDN / NIP)</label>
                    <input type="text" value="{{ $user->identity_number ?? '-' }}" disabled
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 text-slate-500 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Unit Kerja / Program Studi</label>
                    <input type="text" name="department" value="{{ old('department', $user->department) }}" placeholder="Contoh: Informatika / Direktorat TI"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jabatan / Posisi</label>
                    <input type="text" name="jabatan" value="{{ old('jabatan', $user->jabatan) }}" placeholder="Contoh: Staff IT / Dosen Pembimbing"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keahlian & Spesialisasi Teknis</label>
                    <input type="text" name="keahlian" value="{{ old('keahlian', $user->keahlian) }}" placeholder="Contoh: Jaringan, Hardware, Sistem Informasi Akademik"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </div>
            </div>

            <!-- Role & Hirarki Info (Readonly Info) -->
            <div class="mt-4 p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-xs">
                <div>
                    <span class="text-slate-500">Peran Sistem:</span>
                    <strong class="text-slate-900 ml-1">{{ $user->role?->name ?? 'Belum Ditugaskan' }}</strong>
                </div>
                <div class="text-[11px] text-slate-500">
                    Hirarki Atasan: <strong class="text-indigo-600">{{ implode(' → ', $user->role?->getUpwardChainNames() ?? []) }}</strong>
                </div>
            </div>
        </div>

        <!-- Card 3: Keamanan / Ganti Password (Opsional) -->
        <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Keamanan Akun (Ganti Password)</h2>
                <p class="text-xs text-slate-400 mt-0.5">Biarkan kosong jika Anda tidak ingin mengubah password akun saat ini.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Baru</label>
                    <input type="password" name="password" placeholder="Minimal 6 karakter"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" placeholder="Ulangi password baru"
                           class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </div>
            </div>
        </div>

        <!-- Submit Button Footer -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition shadow-2xs">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm shadow-indigo-600/25 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                <span>Simpan Perubahan Profil</span>
            </button>
        </div>
    </form>
</div>
@endsection
