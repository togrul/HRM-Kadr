@props([
    'node',
    'depth' => 0,
    'selected' => null,
])

@php
    $hasChildren = count($node['children']) > 0;
    $filled = (int) ($node['agg']['filled'] ?? 0);
    $isSelected = $selected !== null && (int) $selected === (int) $node['id'];
@endphp

{{--
    Contextual-panel row for one structure of the ştat tree: name + its filled headcount.
    Clicking scopes the whole page to that structure; the caret only folds the panel list.
    Expand state comes from the surrounding x-data (the panel keeps its own, because a
    @teleport lands outside the page component's Alpine scope).
--}}
<div {{ $attributes }}>
    <div class="group flex items-center gap-1 rounded-lg pr-1 transition hover:bg-[#fafafa]"
        style="padding-left: {{ $depth * 12 }}px">
        @if ($hasChildren)
            <button type="button" x-on:click="toggle({{ $node['id'] }})"
                class="flex h-5 w-5 shrink-0 items-center justify-center rounded text-ink-faint transition hover:text-ink"
                aria-label="{{ $node['name'] }}">
                <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="isOpen({{ $node['id'] }}) ? 'rotate-0' : '-rotate-90'"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
        @else
            <span class="h-5 w-5 shrink-0"></span>
        @endif

        <button type="button"
            wire:click.prevent="selectStructure({{ $node['id'] }})"
            wire:loading.attr="disabled"
            wire:target="selectStructure"
            @class([
                'flex min-w-0 flex-1 items-center gap-2 rounded-lg py-1 pr-1 text-left transition',
                'text-ink' => $isSelected,
                'text-ink-muted hover:text-ink' => ! $isSelected,
            ])
        >
            <span @class([
                'min-w-0 flex-1 truncate text-[14px] leading-snug',
                'font-semibold' => $isSelected,
                'font-medium' => ! $isSelected,
            ])>{{ $node['name'] }}</span>
            <span class="hrm-num shrink-0 text-[12px] text-ink-faint">{{ number_format($filled, 0, ',', ' ') }}</span>
        </button>
    </div>

    @if ($hasChildren)
        <div x-show="isOpen({{ $node['id'] }})" x-collapse>
            @foreach ($node['children'] as $child)
                <x-staff.panel-node wire:key="staff-panel-node-{{ $child['id'] }}" :node="$child" :depth="$depth + 1" :selected="$selected" />
            @endforeach
        </div>
    @endif
</div>
