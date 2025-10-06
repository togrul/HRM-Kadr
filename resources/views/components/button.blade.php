@props([
    'mode' => 'default',
    'disabled' => false,
    'type' => 'button',
])

@php
    $extraClasses = match ($mode) {
        'default' => 'bg-white hover:bg-gray-200 active:bg-gray-200 focus:border-gray-200 text-gray-600',
        'gray' => 'bg-zinc-900/90 hover:bg-zinc-800/90 active:bg-zinc-900 focus:border-zinc-900/80 text-zinc-100',
        'primary' => 'border-blue-500 bg-blue-600 hover:bg-blue-600/90 active:bg-blue-500 focus:border-blue-500 text-blue-50',
        'success' => 'bg-green-500 hover:bg-green-600 active:bg-green-500 focus:border-green-500 text-white',
        'warning' => 'bg-yellow-500 hover:bg-yellow-600 active:bg-yellow-500 focus:border-yellow-500 text-white',
        'danger' => 'bg-red-500 hover:bg-red-600 active:bg-red-500 focus:border-red-500 text-white',
        'light-green'
            => 'bg-green-100 border border-green-500 hover:bg-green-200 active:bg-green-100 focus:bg-green-100 text-green-500',
        'teal'
            => 'bg-teal-100 border border-teal-500 hover:bg-teal-200 active:bg-teal-100 focus:bg-teal-100 text-teal-500',
        'slate'
            => 'bg-neutral-100 border border-neutral-200 hover:bg-neutral-200 active:bg-neutral-100 focus:bg-neutral-100 text-neutral-500',
        'light-red'
            => 'bg-red-100 border border-red-500 hover:bg-red-200 active:bg-red-100 focus:bg-red-100 text-red-500',
        'light-blue'
            => 'bg-blue-100 border border-blue-500 hover:bg-blue-200 active:bg-blue-100 focus:bg-blue-100 text-blue-500',
        'black'
            => 'bg-black border border-gray-900 hover:bg-gray-800 active:bg-gray-800 focus:bg-gray-800 text-gray-100',
        'rose'
            => 'bg-rose-50 border border-rose-500 hover:bg-rose-100 active:bg-rose-100 focus:bg-rose-100 text-rose-500',
    };
@endphp

@if ($type == 'button')
    <button @disabled($disabled)
        {{ $attributes->merge(['type' => 'submit', 'class' => 'camelcase inline-flex justify-center items-center px-3 py-2 border rounded-md font-medium shadow-md text-sm focus:outline-none focus:ring ring-gray-300 disabled:opacity-25 transition-all ease-in-out duration-150 ' . $extraClasses]) }}>
        {{ $slot }}
    </button>
@else
    <a
        {{ $attributes->merge(['type' => 'submit', 'class' => 'camelcase inline-flex justify-center items-center px-3 py-2 border border-transparent rounded-md font-medium shadow-md text-sm focus:outline-none focus:ring ring-gray-300 disabled:opacity-25 transition-all ease-in-out duration-150 ' . $extraClasses]) }}>
        {{ $slot }}
    </a>
@endif
