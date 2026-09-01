@props([
    'color' => 'text-slate-500',
    'hover' => 'text-slate-600',
    'size' => 'w-6 h-6',
])

<x-icons.root :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <rect x="8" y="2" width="8" height="4" rx="1"/>
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
        <path d="M8 11h8M8 15h8M8 19h5"/>
    </g>
</x-icons.root>
