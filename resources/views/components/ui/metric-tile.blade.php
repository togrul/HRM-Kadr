@props([
    'label',
    'value' => null,
    'tone' => 'ink',   // ink | green | amber | rose | blue | violet
    'suffix' => null,  // small muted figure beside the number, e.g. a share of total
    'hint' => null,    // one line under the number
    'href' => null,    // makes the whole tile a link to where the number is worked off
])

@php
    // Design-system stat tile: white card, eyebrow label, mono number. A tone colours the
    // number and adds the status dot, so a problem count reads as one at a glance without
    // tinting the whole card.
    [$numberClass, $dotClass] = match ($tone) {
        'green', 'emerald' => ['text-[#047857]', 'bg-[#059669]'],
        'amber', 'warning' => ['text-[#b45309]', 'bg-[#d97706]'],
        'rose', 'red', 'danger' => ['text-[#be123c]', 'bg-[#e11d48]'],
        'blue', 'sky', 'info' => ['text-[#0369a1]', 'bg-[#0284c7]'],
        'violet', 'purple' => ['text-[#6d28d9]', 'bg-[#7c3aed]'],
        default => ['text-ink', ''],
    };

    $tag = $href ? 'a' : 'div';
@endphp

{{-- flex column with the number pushed to the bottom: a label that wraps to two lines
     must not drop its number out of line with the rest of the strip --}}
<{{ $tag }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => 'group flex flex-col rounded-2xl border border-hairline bg-white px-4 py-3.5 shadow-card'.($href ? ' transition hover:border-zinc-300 hover:shadow-md' : '')]) }}
>
    <div class="flex items-center gap-1.5">
        @if ($dotClass !== '')
            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
        @endif
        <x-ui.field-label as="div" class="tracking-tight">{{ $label }}</x-ui.field-label>
        @if ($href)
            <svg class="ml-auto h-3.5 w-3.5 shrink-0 text-ink-faint transition group-hover:translate-x-0.5 group-hover:text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        @endif
    </div>
    <div class="mt-auto flex items-baseline gap-1.5 pt-2">
        <p class="hrm-num text-[21px] font-semibold tracking-[-0.03em] {{ $numberClass }}">{{ $value ?? $slot }}</p>
        @if ($suffix !== null)
            <span class="hrm-num text-[12px] text-ink-faint">{{ $suffix }}</span>
        @endif
    </div>
    @if ($hint !== null)
        <p class="mt-1 text-[11px] leading-4 text-ink-faint">{{ $hint }}</p>
    @endif
</{{ $tag }}>
