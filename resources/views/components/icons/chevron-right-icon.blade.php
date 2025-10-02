@props([
    'color' => 'text-slate-500',
    'hover' => 'text-slate-600',
    'size' => 'w-6 h-6',
    'show' => null
])

<x-icons.root :$size :$color :$hover x-show="{{ $show ?? 'true' }}">
    <path fill="currentColor" d="M9.293 18.707a1 1 0 0 1 0-1.414L14.586 12L9.293 6.707a1 1 0 0 1 1.414-1.414l6 6a1 1 0 0 1 0 1.414l-6 6a1 1 0 0 1-1.414 0z"/>
</x-icons.root>
