@props([
    'active' => false,
    'count' => null,
    'dot' => null,      // tailwind bg-* class for the leading status dot
    'note' => null,     // secondary line under the label
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'button';

    // Single-line rows keep the prototype's exact 31px height; a row carrying a note needs
    // to grow instead, otherwise the two lines collide and the list reads as one block.
    $classes = trim(implode(' ', array_filter([
        'group flex w-full items-center gap-2 rounded-lg px-2.5 text-left transition',
        $note ? 'py-1.5' : 'h-[31px]',
        $active ? 'bg-[#f4f4f5] text-ink' : 'text-ink-muted hover:bg-[#fafafa] hover:text-ink',
    ])));
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="button" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if ($active) aria-current="true" @endif
>
    @if ($dot)
        <span class="mt-[1px] h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}"></span>
    @endif

    @isset($icon)
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-hairline bg-[#fafafa] text-ink-faint transition group-hover:text-ink">{{ $icon }}</span>
    @endisset

    <span class="min-w-0 flex-1">
        <span @class(['block truncate text-[12.5px] leading-tight', 'font-semibold' => $active, 'font-medium' => ! $active])>{{ $slot }}</span>
        @if ($note)
            <span class="mt-0.5 block text-[11px] leading-snug text-ink-faint">{{ $note }}</span>
        @endif
    </span>

    @if ($count !== null)
        <span class="hrm-num shrink-0 rounded-full bg-[#f4f4f5] px-1.5 py-0.5 text-[10.5px] text-ink-muted group-hover:bg-hairline">{{ $count }}</span>
    @endif
</{{ $tag }}>
