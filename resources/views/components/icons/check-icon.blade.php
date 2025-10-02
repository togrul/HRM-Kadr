@props([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null,
])

<x-icons.root animated="true" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g fill="none">
        <circle cx="12" cy="12" r="9" fill="currentColor" fill-opacity=".25" />
        <path stroke="currentColor" stroke-linecap="round" stroke-width="1.2" d="M12 21a9 9 0 1 0-6.364-2.636" />
        <path stroke="currentColor" stroke-linecap="round" stroke-width="1.2"
            d="m16 10l-3.598 4.318c-.655.786-.983 1.18-1.424 1.2c-.44.02-.803-.343-1.527-1.067L8 13" />
    </g>
</x-icons.root>
