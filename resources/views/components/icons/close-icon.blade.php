@props([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null
])

<x-icons.root animated="true" x-show="{{$show}}" :$size :$color :$hover>
    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
        <g transform="translate(12.000000, 12.000000) rotate(-45.000000) translate(-12.000000, -12.000000) translate(4.000000, 4.000000)" fill="currentColor">
            <rect x="0" y="7" width="16" height="2" rx="1"/>
            <rect opacity="0.3" transform="translate(8.000000, 8.000000) rotate(-270.000000) translate(-8.000000, -8.000000) " x="0" y="7" width="16" height="2" rx="1"/>
        </g>
    </g>
</x-icons.root>
