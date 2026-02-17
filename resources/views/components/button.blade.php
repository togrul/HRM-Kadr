@props([
    'mode' => 'default',
    'disabled' => false,
    'type' => 'button',
])

@php
    $extraClasses = match ($mode) {
        'default' => 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 active:bg-gray-100',
        'gray' => 'border-zinc-800 bg-zinc-900 text-zinc-100 hover:bg-zinc-800 active:bg-zinc-900',
        'primary' => 'border-blue-600 bg-blue-600 text-blue-50 hover:bg-blue-700 active:bg-blue-700',
        'success' => 'border-green-600 bg-green-600 text-white hover:bg-green-700 active:bg-green-700',
        'warning' => 'border-yellow-500 bg-yellow-500 text-white hover:bg-yellow-600 active:bg-yellow-600',
        'danger' => 'border-red-600 bg-red-600 text-white hover:bg-red-700 active:bg-red-700',
        'light-green'
            => 'border-emerald-200 bg-emerald-100 text-emerald-800 hover:bg-emerald-200 active:bg-emerald-200',
        'teal'
            => 'border-teal-500 bg-teal-100 text-teal-600 hover:bg-teal-200 active:bg-teal-200',
        'slate'
            => 'border-slate-200 bg-slate-100 text-slate-700 hover:bg-slate-200 active:bg-slate-200',
        'light-red'
            => 'border-red-500 bg-red-100 text-red-600 hover:bg-red-200 active:bg-red-200',
        'light-blue'
            => 'border-blue-500 bg-blue-100 text-blue-600 hover:bg-blue-200 active:bg-blue-200',
        'black'
            => 'border-gray-900 bg-gray-900 text-gray-100 hover:bg-gray-800 active:bg-gray-800',
        'rose'
            => 'border-rose-500 bg-rose-50 text-rose-600 hover:bg-rose-100 active:bg-rose-100',
        'step-prev'
            => 'border-transparent bg-slate-100 text-slate-700 hover:bg-slate-200 active:bg-slate-200',
        'step-next'
            => 'border-transparent bg-emerald-700 text-white hover:bg-emerald-800 active:bg-emerald-800',
    };
@endphp

@if ($type !== 'link')
    <button @disabled($disabled)
        {{ $attributes->merge(['type' => $type, 'class' => 'camelcase inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-md border px-4 py-2 text-sm font-medium shadow-sm transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 disabled:pointer-events-none disabled:opacity-50 ' . $extraClasses]) }}>
        {{ $slot }}
    </button>
@else
    <a
        {{ $attributes->merge(['class' => 'camelcase inline-flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-md border px-4 py-2 text-sm font-medium shadow-sm transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 disabled:pointer-events-none disabled:opacity-50 ' . $extraClasses]) }}>
        {{ $slot }}
    </a>
@endif
