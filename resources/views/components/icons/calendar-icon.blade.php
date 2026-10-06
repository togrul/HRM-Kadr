@props([
    'color' => 'text-zinc-600',
    'hover' => 'text-zinc-800',
    'size' => 'w-6 h-6',
    'show' => null,
])

{{-- Module icon set: 24px grid, 1.6 stroke, round caps (Lucide geometry) — one weight across the rail. --}}
<x-icons.root animated="false" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5"/><path d="M16 2v4M8 2v4M3 10h5"/><path d="M17.5 17.5 16 16.3V14"/><circle cx="16" cy="16" r="6"/></g>
</x-icons.root>
