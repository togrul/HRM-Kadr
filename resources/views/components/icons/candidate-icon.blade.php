@props([
    'color' => 'text-zinc-600',
    'hover' => 'text-zinc-800',
    'size' => 'w-6 h-6',
    'show' => null,
])

{{-- Module icon set: 24px grid, 1.6 stroke, round caps (Lucide geometry) — one weight across the rail. --}}
<x-icons.root animated="false" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="7" r="4"/><path d="M10.3 15H7a4 4 0 0 0-4 4v2"/><circle cx="17" cy="17" r="3"/><path d="m21 21-1.9-1.9"/></g>
</x-icons.root>
