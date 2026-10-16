<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SuperTicket</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full flex items-center justify-center p-4 sm:p-6 text-slate-800">
    <div class="max-w-3xl w-full grid grid-cols-1 md:grid-cols-12 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <!-- Left: Form Login -->
        <div class="md:col-span-6 p-6 sm:p-8 border-b md:border-b-0 md:border-r border-slate-100">
            <div class="flex items-center gap-2.5 mb-6">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                    @include('partials.icon', ['name' => 'ticket', 'class' => 'w-4 h-4'])
                </div>
                <span class="font-bold text-base text-slate-900">SuperTicket</span>
            </div>

            <h1 class="text-lg font-bold text-slate-900">Masuk ke Akun</h1>
            <p class="text-xs text-slate-500 mt-1 mb-5">Password default akun demo: <code class="font-semibold text-slate-700">password</code></p>

            @if($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', 'superuser@kampus.ac.id') }}" required
                           class="w-full px-3.5 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:border-indigo-600">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" value="password" required
                           class="w-full px-3.5 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:border-indigo-600">
                </div>
                <button type="submit"
                        class="w-full py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white transition">
                    Masuk
                </button>
            </form>
        </div>

        <!-- Right: Quick Role Switch -->
        <div class="md:col-span-6 p-6 sm:p-8 bg-slate-50/70">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Akses Cepat Sesuai Role</h2>
            <div class="space-y-2">
                @foreach($demoUsers as $u)
                    <form action="{{ route('auth.quick-switch', $u) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-between p-2.5 rounded-xl bg-white hover:border-indigo-400 border border-slate-200 text-left transition">
                            <div class="min-w-0 pr-2">
                                <p class="text-xs font-semibold text-slate-900 truncate">{{ $u->name }}</p>
                                <p class="text-[11px] text-slate-500">
                                    Role: <span class="font-semibold text-indigo-600">{{ $u->role?->name }}</span>
                                    @if($u->role?->parent)
                                        (→ {{ $u->role->parent->name }})
                                    @endif
                                </p>
                            </div>
                            <span class="text-xs font-medium text-indigo-600 shrink-0">Masuk →</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</body>
</html>
