@props([
    'tone' => 'neutral',
    'active' => false,
])

@php
    $variant = strtolower((string) $tone);

    $baseClasses = 'rounded-2xl border px-4 py-4 transition-all';

    $toneClasses = match ($variant) {
        'sky' => 'border-sky-200 bg-sky-50/70',
        'emerald', 'green' => 'border-emerald-200 bg-emerald-50/70',
        'amber' => 'border-amber-200 bg-amber-50/70',
        'violet', 'purple' => 'border-violet-200 bg-violet-50/70',
        'rose', 'red' => 'border-rose-200 bg-rose-50/70',
        default => 'border-hairline bg-white',
    };

    $activeClasses = $active
        ? ' border-ink shadow-card'
        : ' shadow-card hover:border-zinc-300';
@endphp

<div {{ $attributes->merge(['class' => $baseClasses.' '.$toneClasses.' '.$activeClasses]) }}>
    {{ $slot }}
</div>
