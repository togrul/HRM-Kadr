@props([
    'color' => null,
    'hover' => null,
    'size' => 'w-6 h-6',
])

<x-icons.root :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="16" rx="2"/>
        <circle cx="9" cy="10" r="2"/>
        <path d="M5.8 16.4c.6-1.6 1.9-2.4 3.2-2.4s2.6.8 3.2 2.4"/>
        <path d="M15 9.5h3.5M15 13.5h3.5"/>
    </g>
</x-icons.root>
