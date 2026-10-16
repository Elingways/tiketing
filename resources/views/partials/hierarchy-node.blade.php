@php
    $isCurrentUserRole = isset($highlightRoleId) && (int) $highlightRoleId === (int) $node->id;
@endphp

<div>
    <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl border {{ $isCurrentUserRole ? 'bg-indigo-50/70 border-indigo-300' : 'bg-white border-slate-200' }}">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-2 h-2 rounded-full {{ $isCurrentUserRole ? 'bg-indigo-600' : 'bg-slate-400' }}"></span>
            <span class="font-semibold text-slate-900 text-xs">{{ $node->name }}</span>
            @if($node->parent)
                <span class="text-[11px] text-slate-400">→ {{ $node->parent->name }}</span>
            @else
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-medium">Root</span>
            @endif
        </div>
        <span class="text-[11px] text-slate-400 shrink-0">{{ $node->users->count() }} user</span>
    </div>

    @if($node->childrenRecursive && $node->childrenRecursive->count() > 0)
        <div class="ml-4 pl-3 mt-1.5 border-l border-slate-200 space-y-1.5">
            @foreach($node->childrenRecursive as $child)
                @include('partials.hierarchy-node', ['node' => $child, 'highlightRoleId' => $highlightRoleId ?? null])
            @endforeach
        </div>
    @endif
</div>
