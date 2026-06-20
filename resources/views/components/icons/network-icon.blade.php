@props([
    'color' => 'text-zinc-600',
    'hover' => 'text-zinc-800',
    'size' => 'w-6 h-6',
    'show' => null,
])

<x-icons.root animated="false" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <rect x="3" y="17" width="7" height="5" rx="0.6" fill="none" stroke="currentColor" stroke-width="1.5" />
    <rect x="8.5" y="2" width="7" height="5" rx="0.6" fill="none" stroke="currentColor" stroke-width="1.5" />
    <rect x="14" y="17" width="7" height="5" rx="0.6" fill="none" stroke="currentColor" stroke-width="1.5" />
    <path d="M6.5 17V13.5C6.5 12.3954 7.39543 11.5 8.5 11.5H15.5C16.6046 11.5 17.5 12.3954 17.5 13.5V17" fill="none" stroke="currentColor" stroke-width="1.5" />
    <path d="M12 11.5V7" fill="none" stroke="currentColor" stroke-width="1.5" />
</x-icons.root>
