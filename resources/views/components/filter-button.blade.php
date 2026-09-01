@props(['filters'])

@php
    $hasWireClick = $attributes->has('wire:click') || $attributes->has('wire:click.prevent');
@endphp

<button
    {{ $attributes->class([
        'inline-flex relative items-center justify-center rounded-[10px] h-9 w-9 border border-hairline transition',
        'bg-[#f4f4f5] text-ink border-transparent' => count($filters) > 0,
        'bg-white text-ink-soft hover:border-zinc-300 hover:bg-[#fafafa] hover:text-ink' => count($filters) === 0,
    ])->merge([
        'type' => 'button',
        'title' => __('ui::filters.actions.open_filters'),
        'aria-label' => __('ui::filters.actions.open_filters'),
    ]) }}
    @unless($hasWireClick)
        @click="window.dispatchEvent(new CustomEvent('open-filter-modal')); $wire.dispatch('setOpenFilter')"
    @endunless
>
    <x-icons.search-file size="w-[18px] h-[18px]" color="text-current" hover="text-current" />
    @if (count($filters) > 0)
        <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-semibold leading-none text-white">
            {{ count(array_filter($filters, fn ($v) => $v !== null)) }}
        </span>
    @endif
</button>
