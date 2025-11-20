@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex bg-white shadow-sm h-8 items-center px-2 py-2 rounded-lg shadow-black/5 text-sm font-medium leading-5 text-neutral-900 dark:text-neutral-100 focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center h-8 hover:shadow-sm rounded-lg px-2 py-2 text-sm font-medium leading-5 text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-300 hover:bg-white/80 dark:hover:bg-neutral-700 focus:outline-none focus:text-neutral-700 dark:focus:text-neutral-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
