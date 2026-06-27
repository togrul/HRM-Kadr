@props(['filters'])

@php
    $hasWireClick = $attributes->has('wire:click') || $attributes->has('wire:click.prevent');
@endphp

<button
    {{ $attributes->class([
        'flex relative items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-gray-100',
        'bg-gray-100' => count($filters) > 0,
    ])->merge([
        'type' => 'button',
        'title' => __('ui::filters.actions.open_filters'),
        'aria-label' => __('ui::filters.actions.open_filters'),
    ]) }}
    @unless($hasWireClick)
        @click="window.dispatchEvent(new CustomEvent('open-filter-modal')); $wire.dispatch('setOpenFilter')"
    @endunless
>
    <x-icons.search-file />
    @if (count($filters) > 0)
        <span class="absolute top-0 right-0 rounded-full bg-rose-500 text-white flex justify-center w-4 h-4 text-xs">
            {{ count(array_filter($filters, fn($v) => $v !== null)); }}
        </span>
    @endif
</button>
