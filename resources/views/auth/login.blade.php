<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Masuk — SuperTicket USH</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full flex flex-col justify-center items-center px-4 py-8 sm:py-12 text-slate-800 bg-slate-50 relative selection:bg-indigo-500 selection:text-white"
      x-data="{ forgotModal: false, showDemoRoles: false }">

    <!-- Ambient Glow Background -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-5xl h-96 -z-10 pointer-events-none overflow-hidden">
        <div class="absolute -top-32 left-1/4 w-72 h-72 sm:w-96 sm:h-96 bg-indigo-200/40 rounded-full blur-3xl"></div>
        <div class="absolute -top-20 right-1/4 w-64 h-64 sm:w-80 sm:h-80 bg-sky-200/35 rounded-full blur-3xl"></div>
    </div>

    <!-- Main Clean Container (Mobile Friendly & Centered) -->
    <main class="w-full max-w-[420px] mx-auto my-auto">

        <!-- Header: Animasi Customer Service sebagai Ikon Utama -->
        <div class="text-center mb-6">
            <div class="relative inline-flex items-center justify-center mb-2">
                <!-- Soft Glow Ring -->
                <div class="absolute inset-0 bg-indigo-400/20 rounded-full blur-2xl transform scale-110 pointer-events-none"></div>

                <!-- Lottie Animated Icon Wrapper -->
                <div class="relative w-28 h-28 sm:w-36 sm:h-36 rounded-3xl bg-white/95 border border-slate-200/80 shadow-md shadow-indigo-500/10 p-1 flex items-center justify-center backdrop-blur-sm overflow-hidden">
                    <div id="lottie-customer-service" class="w-full h-full flex items-center justify-center"></div>
                </div>

                <!-- Active Online Indicator Pill -->
                <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-full bg-white shadow-2xs border border-slate-200 text-[10px] font-bold text-emerald-700 flex items-center gap-1.5 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Helpdesk Aktif</span>
                </div>
            </div>

            <!-- Brand Title & Tagline -->
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 mt-3">SuperTicket</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Sistem Layanan Terpadu & Tiketing Kampus</p>
        </div>

        <!-- Auth Card -->
        <div class="bg-white border border-slate-200/80 rounded-2xl sm:rounded-3xl shadow-xl shadow-slate-200/60 p-5 sm:p-7 backdrop-blur-sm">

            <!-- Flash Success Alert -->
            @if(session('success'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-start gap-2.5">
                    @include('partials.icon', ['name' => 'check-circle', 'class' => 'w-4 h-4 text-emerald-600 shrink-0 mt-0.5'])
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Flash Error Alert -->
            @if($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Login Form -->
            <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Alamat Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            @include('partials.icon', ['name' => 'envelope', 'class' => 'w-4 h-4'])
                        </div>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               autofocus
                               autocomplete="email"
                               placeholder="nama@kampus.ac.id"
                               class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition placeholder:text-slate-400 text-slate-900 bg-slate-50/50 hover:bg-white focus:bg-white">
                    </div>
                </div>

                <!-- Password Input -->
                <div x-data="{ showPassword: false }">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-slate-700">
                            Kata Sandi
                        </label>
                        <button type="button"
                                @click="forgotModal = true"
                                class="text-[11px] font-medium text-indigo-600 hover:text-indigo-700 hover:underline transition cursor-pointer p-0.5">
                            Lupa kata sandi?
                        </button>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            @include('partials.icon', ['name' => 'lock', 'class' => 'w-4 h-4'])
                        </div>
                        <input :type="showPassword ? 'text' : 'password'"
                               id="password"
                               name="password"
                               required
                               autocomplete="current-password"
                               placeholder="Masukkan kata sandi"
                               class="w-full pl-10 pr-11 py-2.5 rounded-xl border border-slate-200 text-base sm:text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 transition placeholder:text-slate-400 text-slate-900 bg-slate-50/50 hover:bg-white focus:bg-white">
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none transition cursor-pointer">
                            <span x-show="!showPassword" title="Tampilkan kata sandi">
                                @include('partials.icon', ['name' => 'eye', 'class' => 'w-4 h-4'])
                            </span>
                            <span x-show="showPassword" x-cloak title="Sembunyikan kata sandi">
                                @include('partials.icon', ['name' => 'eye-slash', 'class' => 'w-4 h-4'])
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center pt-0.5">
                    <input id="remember"
                           type="checkbox"
                           name="remember"
                           class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30 cursor-pointer">
                    <label for="remember" class="ml-2 text-xs text-slate-600 cursor-pointer select-none">
                        Ingat sesi saya di peramban ini
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full mt-1.5 py-3 sm:py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] font-semibold text-xs tracking-wide text-white shadow-md shadow-indigo-600/20 transition duration-150 flex items-center justify-center gap-2 group cursor-pointer">
                    <span>Masuk ke Akun</span>
                    <span class="group-hover:translate-x-0.5 transition-transform duration-150">
                        @include('partials.icon', ['name' => 'arrow-right', 'class' => 'w-3.5 h-3.5'])
                    </span>
                </button>
            </form>

            <!-- Akses Pengujian Cepat (Role) -->
            @if(isset($demoUsers) && $demoUsers->count() > 0)
                <div class="mt-5 pt-4 border-t border-slate-100 text-center">
                    <button type="button"
                            @click="showDemoRoles = !showDemoRoles"
                            class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-600 font-medium transition py-1 px-2.5 rounded-lg hover:bg-slate-50 cursor-pointer">
                        <span>Akses Pengujian Cepat (Role)</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200"
                             :class="showDemoRoles ? 'rotate-180 text-indigo-600' : ''"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div x-show="showDemoRoles" x-cloak class="mt-3 text-left space-y-1.5 max-h-48 overflow-y-auto pr-1">
                        @foreach($demoUsers as $demo)
                            <form action="{{ route('auth.quick-switch', $demo) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center justify-between p-2 rounded-xl bg-slate-50 hover:bg-indigo-50/70 border border-slate-200/80 hover:border-indigo-200 text-left transition group cursor-pointer">
                                    <div class="min-w-0 pr-2">
                                        <p class="text-xs font-semibold text-slate-800 truncate group-hover:text-indigo-700">{{ $demo->name }}</p>
                                        <p class="text-[10px] text-slate-500 truncate">{{ $demo->role?->name ?? 'Pengguna' }} &bull; {{ $demo->email }}</p>
                                    </div>
                                    <span class="text-[11px] font-semibold text-indigo-600 shrink-0 opacity-80 group-hover:opacity-100">Pilih &rarr;</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Footer Note -->
        <p class="text-center text-xs text-slate-400 mt-6 font-normal">
            &copy; {{ date('Y') }} Universitas Sugeng Hartono &bull; Ticketing System
        </p>
    </main>

    <!-- Modal Lupa Kata Sandi (Clean Dialog) -->
    <div x-show="forgotModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="forgotModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="forgotModal = false"
                 class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>

            <!-- Modal Content -->
            <div x-show="forgotModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white rounded-2xl p-6 text-left shadow-2xl border border-slate-200/80 max-w-sm w-full mx-auto z-10">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        @include('partials.icon', ['name' => 'shield-check', 'class' => 'w-5 h-5'])
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Pemulihan Kata Sandi</h3>
                        <p class="text-xs text-slate-500">Bantuan Keamanan Akun</p>
                    </div>
                </div>
                <div class="text-xs text-slate-600 leading-relaxed space-y-2 mb-5">
                    <p>Demi keamanan data civitas kampus, reset kata sandi dikelola secara tersentralisasi oleh tim <strong>IT Support & BAAK Kampus</strong>.</p>
                    <p>Silakan hubungi Helpdesk IT Kampus dengan membawa Kartu Identitas / KTM atau hubungi administrator melalui loket layanan terpadu.</p>
                </div>
                <div class="flex justify-end">
                    <button type="button"
                            @click="forgotModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition cursor-pointer">
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Initialize Lottie Animation -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            try {
                var container = document.getElementById('lottie-customer-service');
                if (container && window.lottie) {
                    window.lottie.loadAnimation({
                        container: container,
                        renderer: 'svg',
                        loop: true,
                        autoplay: true,
                        path: '{{ asset("customer-service.json") }}'
                    });
                }
            } catch (err) {
                console.error('Gagal memuat animasi customer service:', err);
            }
        });
    </script>
</body>
</html>
