<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SuperTicket — Sistem Tiketing & Hirarki Role' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
<body class="min-h-full font-sans text-slate-800 antialiased bg-slate-50"
      x-data="{
          createTicketModal: {{ $errors->any() && old('from_modal') ? 'true' : 'false' }},
          previewModal: false,
          previewUrl: '',
          downloadUrl: '',
          previewName: '',
          openDocPreview(previewUrl, downloadUrl, name) {
              this.previewUrl = previewUrl;
              this.downloadUrl = downloadUrl;
              this.previewName = name;
              this.previewModal = true;
          }
      }">

@php
    $groupLabels = [
        'main' => 'Utama',
        'ticketing' => 'Tiketing',
        'management' => 'Manajemen',
    ];
@endphp

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 bg-white border-r border-slate-200 z-30">
        <div class="flex items-center gap-2.5 px-5 h-14 border-b border-slate-100">
            <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                @include('partials.icon', ['name' => 'ticket', 'class' => 'w-4 h-4'])
            </div>
            <span class="font-bold text-sm text-slate-900 tracking-tight">SuperTicket</span>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-5 overflow-y-auto">
            @php
                $orderedGroupKeys = collect(['main', 'ticketing', 'management'])
                    ->concat(collect(array_keys(($sidebarMenus ?? collect())->all())))
                    ->unique()
                    ->values();
            @endphp
            @foreach($orderedGroupKeys as $groupKey)
                @if(isset($sidebarMenus[$groupKey]) && $sidebarMenus[$groupKey]->count() > 0)
                    <div>
                        <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                            {{ $groupLabels[$groupKey] ?? ucfirst($groupKey) }}
                        </p>
                        <div class="space-y-0.5">
                            @foreach($sidebarMenus[$groupKey] as $menuItem)
                                @php
                                    $children = $menuItem->children ?? collect();
                                    $hasChildren = $children->count() > 0;
                                    $isChildActive = $hasChildren && $children->contains(fn ($c) => $c->isCurrentRoute());
                                    $isActive = $isChildActive
                                        || $menuItem->isCurrentRoute()
                                        || (request()->routeIs('tickets.show') && $menuItem->route_name === 'tickets.inbox');
                                @endphp

                                @if($hasChildren)
                                    <div x-data="{ openSub: true }" class="space-y-0.5">
                                        <button type="button" @click="openSub = !openSub"
                                                class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition {{ $isActive ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                @include('partials.icon', ['name' => $menuItem->icon, 'class' => 'w-4 h-4 shrink-0 ' . ($isActive ? 'text-indigo-600' : 'text-slate-400')])
                                                <span class="truncate">{{ $menuItem->name }}</span>
                                            </div>
                                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-150" :class="openSub ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                            </svg>
                                        </button>

                                        <!-- Sub-Sidebar Items -->
                                        <div x-show="openSub" x-cloak class="ml-5 pl-2.5 border-l-2 border-slate-200 space-y-0.5 pt-0.5 pb-1">
                                            @foreach($children as $childMenu)
                                                @php
                                                    $childActive = $childMenu->isCurrentRoute();
                                                @endphp
                                                <a href="{{ $childMenu->resolveUrl() }}"
                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-medium transition {{ $childActive ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        @include('partials.icon', ['name' => $childMenu->icon, 'class' => 'w-3.5 h-3.5 shrink-0 ' . ($childActive ? 'text-white' : 'text-slate-400')])
                                                        <span class="truncate">{{ $childMenu->name }}</span>
                                                    </div>
                                                    @if($childMenu->badge_text)
                                                        <span class="text-[9px] px-1.5 py-0.5 rounded {{ $childActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                                                            {{ $childMenu->badge_text }}
                                                        </span>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ $menuItem->resolveUrl() }}"
                                       class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition {{ $isActive ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            @include('partials.icon', ['name' => $menuItem->icon, 'class' => 'w-4 h-4 shrink-0 ' . ($isActive ? 'text-white' : 'text-slate-400')])
                                            <span class="truncate">{{ $menuItem->name }}</span>
                                        </div>
                                        @if($menuItem->route_name === 'tickets.inbox' && $roleInboxCount > 0)
                                            <span class="px-1.5 py-0.5 text-[10px] font-bold rounded {{ $isActive ? 'bg-white text-slate-900' : 'bg-indigo-600 text-white' }}">
                                                {{ $roleInboxCount }}
                                            </span>
                                        @endif
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="p-3 border-t border-slate-100">
            <div class="flex items-center justify-between gap-2 px-2 py-1">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-900 truncate">{{ $authUser->name }}</p>
                    <p class="text-[11px] text-slate-400 truncate">Role: {{ $authUser->role?->name }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Keluar" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 lg:pl-64 flex flex-col min-w-0 pb-20 lg:pb-10">
        <!-- Top Header -->
        <header class="sticky top-0 z-20 bg-white border-b border-slate-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="font-semibold text-slate-800">{{ $authUser->name }}</span>
                    <span class="text-slate-300">•</span>
                    <span>Alur Hirarki: <strong class="text-slate-700">{{ implode(' → ', $authUser->role?->getUpwardChainNames() ?? []) }}</strong></span>
                </div>

                <!-- Role Switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" type="button"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200/80 text-slate-700 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                        <span>Role: <strong>{{ $authUser->role?->name }}</strong></span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                    </button>

                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute right-0 mt-1.5 w-60 rounded-xl bg-white shadow-lg border border-slate-200 p-1.5 z-50">
                        <div class="px-2.5 py-1 border-b border-slate-100 mb-1">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Pindah Role / Akun</p>
                        </div>
                        <div class="max-h-72 overflow-y-auto space-y-0.5">
                            @foreach($allDemoUsers as $demoUser)
                                <form action="{{ route('auth.quick-switch', $demoUser) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-left text-xs transition {{ $demoUser->id === $authUser->id ? 'bg-slate-100 text-slate-900 font-semibold' : 'hover:bg-slate-50 text-slate-600' }}">
                                        <div class="truncate pr-2">
                                            <p class="truncate">{{ $demoUser->name }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $demoUser->role?->name }}</p>
                                        </div>
                                        @if($demoUser->id === $authUser->id)
                                            <span class="text-[10px] font-semibold text-indigo-600">Aktif</span>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Alerts -->
        <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 pt-4">
            @if(session('success'))
                <div class="mb-4 px-4 py-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 px-4 py-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 px-4 py-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Content -->
        <main class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-5 flex-1">
            @yield('content')
        </main>
    </div>
</div>

<!-- MODAL 1: Buat Tiket Baru -->
<div x-show="createTicketModal" x-cloak
     @keydown.escape.window="createTicketModal = false"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
    <div @click.outside="createTicketModal = false"
         x-data="{
            selectedCategory: '{{ old('category_id', ($globalCategories ?? collect())->first()?->id) }}',
            chains: {{ Js::from($globalCategoryChains ?? []) }},
            fileName: ''
         }"
         class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full overflow-hidden">

        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Buat Tiket Baru</h2>
                <p class="text-[11px] text-slate-400">Prioritas tiket ditentukan oleh role penerima di tahap berikutnya.</p>
            </div>
            <button type="button" @click="createTicketModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                ✕
            </button>
        </div>

        <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="from_modal" value="1">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori Layanan</label>
                <select name="category_id" x-model="selectedCategory"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-white focus:outline-none focus:border-slate-900">
                    @foreach(($globalCategories ?? collect()) as $cat)
                        <option value="{{ $cat->id }}">
                            {{ $cat->name }} ({{ $cat->workflow_type === 'tiered_approval' ? 'Approval Berjenjang' : 'Eskalasi/Disposisi' }})
                        </option>
                    @endforeach
                </select>

                <div x-show="selectedCategory && chains[selectedCategory]" x-cloak
                     class="mt-2 px-3 py-2 rounded-lg bg-slate-50 border border-slate-200/70 text-[11px] text-slate-600 flex items-center justify-between gap-2">
                    <span>Alur Hirarki: <strong class="text-slate-900" x-text="(chains[selectedCategory]?.chain_names || []).join(' → ')"></strong></span>
                    <span class="text-slate-400 shrink-0" x-text="chains[selectedCategory]?.workflow_label"></span>
                </div>

                <!-- Box Template Dokumen Layanan (Tampil Otomatis Jika Kategori Memiliki Template) -->
                <div x-show="selectedCategory && chains[selectedCategory]?.has_template" x-cloak
                     class="mt-2.5 p-3 rounded-xl bg-indigo-50/70 border border-indigo-200 space-y-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-bold text-indigo-950 flex items-center gap-1.5">
                                <span>📄 Template Dokumen Layanan</span>
                                <span x-show="chains[selectedCategory]?.is_attachment_required"
                                      class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-rose-100 text-rose-700 border border-rose-200">
                                    Wajib Diisi
                                </span>
                            </p>
                            <p class="text-[11px] text-indigo-800/80 mt-0.5">
                                Silakan unduh dan lengkapi template <strong x-text="chains[selectedCategory]?.template_name"></strong> sebelum mengunggahnya di bawah.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a :href="chains[selectedCategory]?.template_download_url"
                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-semibold transition">
                            <span>⬇ Unduh Template Dokumen</span>
                        </a>
                        <button type="button"
                                @click="openDocPreview(chains[selectedCategory]?.template_preview_url, chains[selectedCategory]?.template_download_url, chains[selectedCategory]?.template_name)"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-white hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-[11px] font-semibold transition cursor-pointer">
                            <span>📎 Preview Template</span>
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Permohonan / Kendala</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       placeholder="Contoh: Pengajuan Pencairan Dana Kegiatan Seminar"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi Lengkap</label>
                <textarea name="description" rows="3" required
                       placeholder="Tuliskan rincian keperluan atau pengajuan Anda..."
                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs focus:outline-none focus:border-slate-900">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                    <span>Upload Dokumen Layanan / Pendukung</span>
                    <span x-show="chains[selectedCategory]?.is_attachment_required" class="text-rose-600 font-bold ml-1">
                        * (Wajib mengunggah template yang telah diisi)
                    </span>
                    <span x-show="!chains[selectedCategory]?.is_attachment_required" class="font-normal text-slate-400 ml-1">
                        (Opsional — PDF, Gambar, DOCX maks 10MB)
                    </span>
                </label>
                <label class="flex items-center justify-between px-3.5 py-2.5 rounded-lg border border-dashed transition cursor-pointer"
                       :class="chains[selectedCategory]?.is_attachment_required ? 'border-indigo-300 bg-indigo-50/30 hover:border-indigo-500' : 'border-slate-300 bg-slate-50/50 hover:border-slate-400'">
                    <div class="flex items-center gap-2 text-xs text-slate-600 truncate">
                        <span>📎</span>
                        <span x-text="fileName || (chains[selectedCategory]?.is_attachment_required ? 'Pilih file dokumen template yang sudah diisi...' : 'Pilih file dokumen lampiran...')" class="truncate"></span>
                    </div>
                    <span class="text-[11px] font-semibold text-indigo-600 shrink-0 ml-2">Pilih File</span>
                    <input type="file" name="attachment" class="hidden"
                           :required="Boolean(chains[selectedCategory]?.is_attachment_required)"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip"
                           @change="fileName = $event.target.files[0]?.name || ''">
                </label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" @click="createTicketModal = false"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                    Kirim Tiket
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: Preview Dokumen Lampiran -->
<div x-show="previewModal" x-cloak
     @keydown.escape.window="previewModal = false"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div @click.outside="previewModal = false"
         class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-4xl w-full h-[82vh] flex flex-col overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 min-w-0">
                <span>📎</span>
                <h3 class="text-xs font-bold text-slate-900 truncate" x-text="previewName"></h3>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a :href="previewUrl" target="_blank"
                   class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50">
                    Buka di Tab Baru ↗
                </a>
                <a :href="downloadUrl"
                   class="px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
                    Unduh File
                </a>
                <button type="button" @click="previewModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    ✕
                </button>
            </div>
        </div>
        <div class="flex-1 bg-slate-100 relative">
            <template x-if="previewModal && previewUrl">
                <iframe :src="previewUrl" class="w-full h-full border-0"></iframe>
            </template>
        </div>
    </div>
</div>

<!-- Mobile Bottom Nav -->
<div class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 px-2 py-1.5 z-40">
    <div class="flex items-center justify-around">
        @foreach($launcherMenus->take(5) as $mMenu)
            @php
                $hasRoute = \Illuminate\Support\Facades\Route::has($mMenu->route_name);
                $activeMobile = request()->routeIs($mMenu->route_name);
            @endphp
            @if($mMenu->route_name === 'tickets.create')
                <button type="button" @click="createTicketModal = true"
                        class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-lg text-indigo-600 font-semibold">
                    @include('partials.icon', ['name' => $mMenu->icon, 'class' => 'w-5 h-5'])
                    <span class="text-[10px] truncate max-w-[64px]">{{ $mMenu->name }}</span>
                </button>
            @else
                <a href="{{ $hasRoute ? route($mMenu->route_name) : '#' }}"
                   class="flex flex-col items-center gap-0.5 py-1 px-2 rounded-lg {{ $activeMobile ? 'text-indigo-600 font-semibold' : 'text-slate-500' }}">
                    @include('partials.icon', ['name' => $mMenu->icon, 'class' => 'w-5 h-5'])
                    <span class="text-[10px] truncate max-w-[64px]">{{ $mMenu->name }}</span>
                </a>
            @endif
        @endforeach
    </div>
</div>

</body>
</html>
