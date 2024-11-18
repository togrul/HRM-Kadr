@props([
    'color' => 'text-rose-500',
    'hover' => 'text-rose-600',
    'size' => 'w-6 h-6'
])

<x-icons.root :$size :$color :$hover>
    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
        <rect x="0" y="0" width="24" height="24"/>
        <circle fill="currentColor" opacity="0.3" cx="12" cy="12" r="10"/>
        <rect fill="currentColor" x="11" y="10" width="2" height="7" rx="1"/>
        <rect fill="currentColor" x="11" y="7" width="2" height="2" rx="1"/>
    </g>
</x-icons.root>
