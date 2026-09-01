@props([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
])

<x-icons.root :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 3v11" />
        <path d="M8 10.5 12 14.5 16 10.5" />
        <path d="M4 16.5v2A2.5 2.5 0 0 0 6.5 21h11a2.5 2.5 0 0 0 2.5-2.5v-2" />
    </g>
</x-icons.root>
