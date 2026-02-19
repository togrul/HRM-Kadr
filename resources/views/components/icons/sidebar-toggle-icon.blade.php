@props([
    'color' => 'text-zinc-700',
    'hover' => 'text-zinc-900',
    'size' => 'w-5 h-5',
    'show' => null,
])

<x-icons.root :$size :$color :$hover x-show="{{ $show ?? 'true' }}">
    <rect x="3.5" y="3.5" width="17" height="17" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8" />
    <path d="M11.5 3.8v16.4" fill="none" stroke="currentColor" stroke-width="1.8" />
    <rect x="5.5" y="5.5" width="4.2" height="12.8" rx="1" fill="currentColor" opacity="0.18" />
</x-icons.root>
