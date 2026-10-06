@props([
    'color' => 'text-zinc-600',
    'hover' => 'text-zinc-800',
    'size' => 'w-6 h-6',
    'show' => null,
])

{{-- Module icon set: 24px grid, 1.6 stroke, round caps (Lucide geometry) — one weight across the rail. --}}
<x-icons.root animated="false" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 7 13.5 15.5 8.5 10.5 2 17"/><path d="M16 7h6v6"/></g>
</x-icons.root>
