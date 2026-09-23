@props([
    'value',
    'label',
    'tone' => 'ink',   // ink | amber | rose (other legacy tones render as ink)
])

@php
    // Colour carries meaning here, never decoration: only warning / danger tones are kept,
    // and only while there is something to act on — a zero reads as calm ink.
    $hasValue = ! in_array(trim((string) $value), ['', '0', '—'], true);

    $toneClasses = match (true) {
        $hasValue && in_array($tone, ['amber', 'warning'], true) => 'text-[#b45309]',
        $hasValue && in_array($tone, ['rose', 'red'], true) => 'text-[#e11d48]',
        default => 'text-ink',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center leading-none']) }}>
    <span class="hrm-num text-[20px] font-semibold tracking-[-0.03em] {{ $toneClasses }}">{{ $value }}</span>
    <span class="mt-1 whitespace-nowrap text-[11px] text-ink-faint">{{ $label }}</span>
</div>
