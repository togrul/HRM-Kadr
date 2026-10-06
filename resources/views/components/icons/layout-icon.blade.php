@props([
    'color' => 'text-zinc-700',
    'hover' => 'text-zinc-900',
    'size' => 'w-6 h-6',
    'show' => null
])

{{-- Same geometry as the module icon set: 24px grid, 1.6 stroke, round caps. --}}
<x-icons.root animated="true"
              :$size
              :$color
              :$hover
              x-show="{{ $show ?? 'true' }}"
>
    <g fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></g>
</x-icons.root>
