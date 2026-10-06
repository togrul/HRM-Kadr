@props([
    'color' => null,
    'hover' => null,
    'size' => null
])

{{-- Same geometry as the module icon set: 24px grid, 1.6 stroke, round caps. --}}
<x-icons.root :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></g>
</x-icons.root>
