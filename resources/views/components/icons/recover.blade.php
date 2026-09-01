@props([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-600',
    'size' => 'w-6 h-6',
])

<x-icons.root :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3.5 12a8.5 8.5 0 1 0 2.9-6.4"/>
        <path d="M3 4v5h5"/>
        <path d="M12 8v4.2l3 1.8"/>
    </g>
</x-icons.root>
