@props([
    'color' => 'text-rose-500',
    'hover' => 'text-rose-600',
    'size' => 'w-6 h-6'
])

<x-icons.root :$size :$color :$hover>
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
</x-icons.root>
