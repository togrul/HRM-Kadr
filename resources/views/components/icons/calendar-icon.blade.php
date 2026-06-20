@props([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null,
    'active' => false,
])

<x-icons.root animated="true" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g transform="scale(0.75)">
        <path d="M8.75 5V3.125" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" class="opacity-60" />
        <path d="M21.25 5V3.125" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" class="opacity-60" />
        <path d="M3.125 11.25h23.75" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" class="opacity-60" />
        <path d="M2.5 15c0 -4.71405 0 -7.0710625 1.4644625 -8.5355375C5.4289375 5 7.785950000000001 5 12.5 5h5c4.7139999999999995 0 7.071125 0 8.5355 1.4644625C27.5 7.9289375 27.5 10.28595 27.5 15v2.5c0 4.7139999999999995 0 7.071125 -1.4645 8.5355C24.571125000000002 27.5 22.214 27.5 17.5 27.5h-5c-4.71405 0 -7.0710625 0 -8.5355375 -1.4645C2.5 24.571125000000002 2.5 22.214 2.5 17.5v-2.5Z" fill="none" stroke="currentColor" stroke-width="2" />
        <path d="M22.5 21.25c0 0.690375 -0.559625 1.25 -1.25 1.25s-1.25 -0.559625 -1.25 -1.25 0.559625 -1.25 1.25 -1.25 1.25 0.559625 1.25 1.25Z" fill="currentColor" stroke-width="2" />
        <path d="M22.5 16.25c0 0.690375 -0.559625 1.25 -1.25 1.25s-1.25 -0.559625 -1.25 -1.25 0.559625 -1.25 1.25 -1.25 1.25 0.559625 1.25 1.25Z" fill="currentColor" stroke-width="2" />
        <path d="M16.25 21.25c0 0.690375 -0.559625 1.25 -1.25 1.25s-1.25 -0.559625 -1.25 -1.25 0.559625 -1.25 1.25 -1.25 1.25 0.559625 1.25 1.25Z" fill="currentColor" stroke-width="2" />
        <path d="M16.25 16.25c0 0.690375 -0.559625 1.25 -1.25 1.25s-1.25 -0.559625 -1.25 -1.25 0.559625 -1.25 1.25 -1.25 1.25 0.559625 1.25 1.25Z" fill="currentColor" stroke-width="2" />
        <path d="M10 21.25c0 0.690375 -0.55965 1.25 -1.25 1.25s-1.25 -0.559625 -1.25 -1.25 0.55965 -1.25 1.25 -1.25 1.25 0.559625 1.25 1.25Z" fill="currentColor" stroke-width="2" />
        <path d="M10 16.25c0 0.690375 -0.55965 1.25 -1.25 1.25s-1.25 -0.559625 -1.25 -1.25 0.55965 -1.25 1.25 -1.25 1.25 0.559625 1.25 1.25Z" fill="currentColor" stroke-width="2" />
    </g>
</x-icons.root>
