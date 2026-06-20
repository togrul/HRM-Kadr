@props([
    'variant' => 'secondary',
    'fullWidth' => false,
    'size' => 'md',
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center rounded-2xl font-semibold transition disabled:cursor-not-allowed disabled:opacity-60';
    $width = $fullWidth ? 'w-full' : '';
    $sizes = [
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-5 py-3 text-sm',
    ];
    $variants = [
        'primary' => 'bg-zinc-950 text-white hover:bg-zinc-900',
        'secondary' => 'border border-zinc-200 bg-white text-zinc-800 shadow-sm hover:border-zinc-300 hover:bg-zinc-950 hover:text-white',
        'danger' => 'border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100',
        'warning' => 'border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100',
        'success' => 'border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
        'info' => 'border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100',
    ];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => trim($base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['secondary']).' '.$width),
    ]) }}
>
    {{ $slot }}
</button>
