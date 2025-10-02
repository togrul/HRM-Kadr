@props([
    'color' => 'text-slate-500',
    'hover' => 'text-slate-600',
    'size' => 'w-6 h-6',
    'show' => null
])

<x-icons.root :$size :$color :$hover x-show="{{ $show ?? 'true' }}">
    <path fill="currentColor" d="M5.293 9.293a1 1 0 0 1 1.414 0L12 14.586l5.293-5.293a1 1 0 1 1 1.414 1.414l-6 6a1 1 0 0 1-1.414 0l-6-6a1 1 0 0 1 0-1.414z"/>
</x-icons.root>
