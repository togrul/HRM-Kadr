@props([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null,
])

<x-icons.root animated="true" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g transform="scale(0.8)">
        <path
            d="M15 27.5c6.9035 0 12.5 -5.5965 12.5 -12.5 0 -6.9035625 -5.5965 -12.5 -12.5 -12.5C8.0964375 2.5 2.5 8.0964375 2.5 15c0 6.9035 5.5964375 12.5 12.5 12.5Z"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="opacity-60"
        />
        <path
            d="M15 10v5l3.125 3.125"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </g>
</x-icons.root>
