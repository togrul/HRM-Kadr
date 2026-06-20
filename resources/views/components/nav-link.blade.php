@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex h-10 items-center gap-2 rounded-full bg-neutral-900 px-4 text-sm font-medium leading-5 text-white  focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex h-10 items-center gap-2 rounded-full bg-white px-4 text-sm font-medium leading-5 text-neutral-700 hover:bg-white/95 hover:text-neutral-900 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
