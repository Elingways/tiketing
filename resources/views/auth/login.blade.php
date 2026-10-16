<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SuperTicket Kampus</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-white flex items-center justify-center p-4 sm:p-8 relative overflow-x-hidden">
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-600/30 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-5xl w-full grid grid-cols-1 lg:grid-cols-12 bg-slate-900/90 backdrop-blur-xl border border-white/10 rounded-3xl shadow-2xl overflow-hidden z-10">
        <!-- Kiri: Quick Role Login ala SuperApp -->
        <div class="lg:col-span-7 p-6 sm:p-10 border-b lg:border-b-0 lg:border-r border-white/10">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center text-white shadow-lg">
                    @include('partials.icon', ['name' => 'ticket', 'class' => 'w-6 h-6'])
                </div>
                <div>
                    <h1 class="text-xl font-extrabold tracking-tight">SuperTicket Kampus</h1>
                    <p class="text-xs text-slate-400">Dynamic Role Hierarchy & SuperApp Management</p>
                </div>
            </div>

            <h2 class="text-lg font-bold text-white mb-1">Pilih Akun Demo (1-Klik Masuk)</h2>
            <p class="text-xs text-slate-400 mb-5">
                Klik salah satu role di bawah ini untuk langsung menguji dashboard dinamis beserta alur hirarkinya (<span class="text-indigo-400 font-semibold">Mahasiswa → Dosen → Kaprodi → Dekan</span> atau <span class="text-rose-400 font-semibold">Superuser</span>).
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($demoUsers as $u)
                    <form action="{{ route('auth.quick-switch', $u) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left p-3.5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-indigo-400/50 transition group">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-extrabold px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                                    Role: {{ $u->role?->name }}
                                </span>
                                <span class="text-xs text-slate-400 group-hover:text-white transition">Masuk →</span>
                            </div>
                            <p class="font-bold text-sm text-white truncate">{{ $u->name }}</p>
                            <p class="text-[11px] text-slate-400 truncate">{{ $u->email }}</p>
                            <div class="mt-2 pt-2 border-t border-white/5 text-[11px] text-slate-400">
                                @if($u->role?->is_superuser)
                                    <span class="text-rose-300 font-medium">★ Kelola Menu, Role & Pohon Hirarki</span>
                                @elseif($u->role?->parent)
                                    <span>Hirarki ke Atasan: <strong class="text-emerald-300">{{ $u->role->parent->name }}</strong></span>
                                @else
                                    <span class="text-violet-300">Puncak Hirarki Fakultas</span>
                                @endif
                            </div>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>

        <!-- Kanan: Form Login Manual -->
        <div class="lg:col-span-5 p-6 sm:p-10 flex flex-col justify-center bg-slate-950/50">
            <h3 class="text-base font-bold text-white mb-1">Login dengan Email & Password</h3>
            <p class="text-xs text-slate-400 mb-6">Semua akun demo menggunakan password: <code class="text-indigo-300 font-bold">password</code></p>

            @if($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-500/20 border border-rose-500/40 text-xs text-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Alamat Email</label>
                    <input type="email" name="email" value="{{ old('email', 'superuser@kampus.ac.id') }}" required
                           class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/15 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Password</label>
                    <input type="password" name="password" value="password" required
                           class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/15 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>
                <button type="submit"
                        class="w-full py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 font-bold text-sm text-white shadow-lg shadow-indigo-600/30 transition">
                    Masuk ke Dashboard SuperApp
                </button>
            </form>
        </div>
    </div>
</body>
</html>
