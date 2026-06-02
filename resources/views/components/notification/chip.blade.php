@props([
    'mode' => 'neutral',
    'uppercase' => true,
    'size' => 'md',
    'as' => 'span',
])

@php
    $tag = in_array($as, ['span', 'div', 'a', 'button'], true) ? $as : 'span';
    $base = 'inline-flex items-center rounded-full border font-semibold whitespace-nowrap';
    $sizes = [
        'sm' => 'px-2.5 py-1 text-[11px]',
        'md' => 'px-3 py-1.5 text-xs',
    ];
    $modes = [
        'neutral' => 'border-zinc-200 bg-white text-zinc-600',
        'muted' => 'border-zinc-200 bg-zinc-50 text-zinc-600',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
        'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
        'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'inactive' => 'border-zinc-200 bg-zinc-100 text-zinc-500',
    ];
    $caps = $uppercase ? 'uppercase tracking-tight font-semibold' : '';
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => trim($base.' '.($sizes[$size] ?? $sizes['md']).' '.($modes[$mode] ?? $modes['neutral']).' '.$caps)]) }}>
    {{ $slot }}
</{{ $tag }}>
