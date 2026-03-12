@props([
    'mode' => 'sky',
    'icon' => null,
])

@php
    $variant = strtolower((string) $mode);

    $baseClasses = 'inline-flex min-w-max items-center justify-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-1';

    $modeClasses = match ($variant) {
        'rose', 'danger', 'delete' => 'border-rose-200 bg-rose-50 text-rose-700 hover:border-rose-300 hover:bg-rose-100 focus:ring-rose-200',
        'secondary' => 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-100 focus:ring-zinc-200',
        default => 'border-sky-200 bg-sky-50 text-sky-700 hover:border-sky-300 hover:bg-sky-100 focus:ring-sky-200',
    };
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $baseClasses.' '.$modeClasses]) }}>
    @if ($icon)
        <x-dynamic-component :component="$icon" size="w-4 h-4" />
    @endif
    <span>{{ $slot }}</span>
</button>
