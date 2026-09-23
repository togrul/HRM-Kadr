@props([
    'variant' => 'secondary',
    'fullWidth' => false,
    'size' => 'md',
    'type' => 'button',
])

@php
    $base = 'inline-flex items-center justify-center rounded-[10px] font-semibold tracking-[-0.01em] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    $width = $fullWidth ? 'w-full' : '';
    $sizes = [
        'sm' => 'min-h-10 px-3 text-[14px]',
        'md' => 'min-h-10 px-4 text-[14px]',
        'lg' => 'min-h-11 px-5 text-[14px]',
    ];
    $variants = [
        'primary' => 'bg-ink text-white hover:bg-ink-hover',
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
