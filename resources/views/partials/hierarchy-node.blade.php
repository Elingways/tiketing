@php
    $colorMap = [
        'indigo' => 'bg-indigo-50 border-indigo-200 text-indigo-700',
        'emerald' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
        'amber' => 'bg-amber-50 border-amber-200 text-amber-700',
        'rose' => 'bg-rose-50 border-rose-200 text-rose-700',
        'violet' => 'bg-violet-50 border-violet-200 text-violet-700',
        'sky' => 'bg-sky-50 border-sky-200 text-sky-700',
        'teal' => 'bg-teal-50 border-teal-200 text-teal-700',
        'fuchsia' => 'bg-fuchsia-50 border-fuchsia-200 text-fuchsia-700',
    ];
    $badgeClass = $colorMap[$node->badge_color] ?? $colorMap['indigo'];
    $isCurrentUserRole = isset($highlightRoleId) && (int) $highlightRoleId === (int) $node->id;
@endphp

<div class="relative">
    <div class="flex items-start gap-3 p-3.5 rounded-2xl border transition-all {{ $isCurrentUserRole ? 'ring-2 ring-indigo-600 bg-indigo-50/70 border-indigo-300 shadow-sm' : 'bg-white border-slate-200/80 hover:border-slate-300 shadow-xs' }}">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center border shrink-0 {{ $badgeClass }}">
            @include('partials.icon', ['name' => $node->icon, 'class' => 'w-5 h-5'])
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <span class="font-bold text-slate-900 text-sm">{{ $node->name }}</span>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full border {{ $badgeClass }}">
                    Level {{ $node->level }}
                </span>
                @if($isCurrentUserRole)
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-indigo-600 text-white">
                        Role Anda Saat Ini
                    </span>
                @endif
                @if($node->parent)
                    <span class="text-[11px] text-slate-500 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        Hirarki ke Atasan: <strong class="text-slate-700">{{ $node->parent->name }}</strong>
                    </span>
                @else
                    <span class="text-[11px] font-medium text-violet-600 bg-violet-50 px-2 py-0.5 rounded-full">
                        Puncak Hirarki (Root)
                    </span>
                @endif
            </div>
            @if($node->description)
                <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $node->description }}</p>
            @endif
            <div class="flex flex-wrap items-center gap-3 mt-2 text-[11px] text-slate-500">
                <span class="inline-flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                    {{ $node->users->count() }} Pengguna
                </span>
                @if($node->can_receive_ticket)
                    <span class="text-emerald-600 font-medium">✓ Menerima Tiket Masuk</span>
                @endif
                @if($node->can_create_ticket)
                    <span class="text-indigo-600 font-medium">✓ Dapat Mengajukan Tiket</span>
                @endif
            </div>
        </div>
    </div>

    @if($node->childrenRecursive && $node->childrenRecursive->count() > 0)
        <div class="ml-6 pl-5 mt-2.5 border-l-2 border-dashed border-indigo-200 space-y-2.5">
            @foreach($node->childrenRecursive as $child)
                @include('partials.hierarchy-node', ['node' => $child, 'highlightRoleId' => $highlightRoleId ?? null])
            @endforeach
        </div>
    @endif
</div>
