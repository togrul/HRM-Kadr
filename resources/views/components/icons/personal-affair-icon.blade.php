@props([
    'color' => 'text-zinc-600',
    'hover' => 'text-zinc-800',
    'size' => 'w-6 h-6',
    'show' => null,
])

<x-icons.root animated="false" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <path d="M4 19V5C4 3.89543 4.89543 3 6 3H19.4C19.7314 3 20 3.26863 20 3.6V16.7143" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    <path d="M8 3V11L10.5 9.4L13 11V3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M6 17L20 17" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    <path d="M6 21L20 21" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    <path d="M6 21C4.89543 21 4 20.1046 4 19C4 17.8954 4.89543 17 6 17" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
</x-icons.root>
