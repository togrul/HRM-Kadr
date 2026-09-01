@props([
    'color' => 'text-rose-500',
    'hover' => 'text-rose-700',
    'size' => 'w-6 h-6',
])

{{-- Line icon on the design system's 24px grid; the old Sketch export cost 2.1KB per use. --}}
<x-icons.root :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 6h18"/>
        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
        <path d="M10 11v6M14 11v6"/>
    </g>
</x-icons.root>
