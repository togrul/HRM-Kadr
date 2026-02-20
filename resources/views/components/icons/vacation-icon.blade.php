@props([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null,
    'active' => false,
])

<x-icons.root animated="true" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g transform="scale(0.5)">
        <path d="M7 38c1.368 -5.675 8.458 -10 17 -10s15.632 4.325 17 10" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M35 11c4.771 0 9 3.907 9 9" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M35.14 21c2.572 -2.773 2.431 -7.227 -0.14 -10" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M33.39 2.648C36.228 4.286 36.638 8.162 35 11" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M35 11c-3.005 -4.52 -8.356 -5.396 -12 -1.669" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M19 28c0 -9.389 7.611 -17 17 -17 -5.007 0 -9.067 7.611 -9.067 17" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M3 45c1.89 0 3.78 -0.556 5.42 -1.67a2.858 2.858 0 0 1 3.16 0A9.643 9.643 0 0 0 17 45c1.89 0 3.78 -0.556 5.42 -1.67a2.858 2.858 0 0 1 3.16 0A9.643 9.643 0 0 0 31 45c1.89 0 3.78 -0.556 5.42 -1.67a2.858 2.858 0 0 1 3.16 0A9.643 9.643 0 0 0 45 45" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
    </g>
</x-icons.root>
