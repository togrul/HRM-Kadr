@props([
    'value',
    'label',
    'tone' => 'ink',   // ink | green | amber | rose | blue | violet
])

@php
    $toneClasses = match ($tone) {
        'green', 'emerald' => 'text-[#059669]',
        'amber', 'warning' => 'text-[#b45309]',
        'rose', 'red' => 'text-[#e11d48]',
        'blue', 'sky' => 'text-[#0369a1]',
        'violet', 'purple' => 'text-[#6d28d9]',
        default => 'text-ink',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center leading-none']) }}>
    <span class="hrm-num text-[20px] font-semibold tracking-[-0.03em] {{ $toneClasses }}">{{ $value }}</span>
    <span class="mt-1 whitespace-nowrap text-[11px] text-ink-faint">{{ $label }}</span>
</div>
