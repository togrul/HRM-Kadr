@props([
    'color' => 'text-zinc-600',
    'hover' => 'text-zinc-800',
    'size' => 'w-6 h-6',
    'show' => null,
])

{{-- Module icon set: 24px grid, 1.6 stroke, round caps (Lucide geometry) — one weight across the rail. --}}
<x-icons.root animated="false" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12.5 22H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v9.5"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M13.38 15.63a1 1 0 1 0-3-3l-5.01 5a2 2 0 0 0-.51.86l-.84 2.87a.5.5 0 0 0 .62.62l2.87-.84a2 2 0 0 0 .86-.51z"/></g>
</x-icons.root>
