@props([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null
])

<x-icons.root animated="true" x-show="{{$show}}" :$size :$color :$hover>
    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
        <rect fill="currentColor" x="4" y="11" width="16" height="2" rx="1"/>
        <rect fill="currentColor" opacity="0.3" transform="translate(12.000000, 12.000000) rotate(-270.000000) translate(-12.000000, -12.000000) " x="4" y="11" width="16" height="2" rx="1"/>
    </g>
</x-icons.root>
