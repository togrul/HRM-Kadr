@props([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null,
    'active' => false,
])

<x-icons.root animated="true" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g transform="scale(0.75)">
        <path d="M20 7.5c0 -2.357025 0 -3.5355375 -0.73225 -4.2677625C18.5355 2.5 17.357 2.5 15 2.5c-2.3569999999999998 0 -3.5355375 0 -4.2677625 0.7322375000000001C10 3.9644625 10 5.142975 10 7.5" fill="none" stroke="currentColor" stroke-width="2" class="opacity-60" />
        <path d="M7.5 8.125V18.75m0 8.125v-3.75" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" class="opacity-60" />
        <path d="M22.5 8.125v18.75" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" class="opacity-60" />
        <path d="M2.5 17.5c0 -4.7139999999999995 0 -7.0710625 1.4644625 -8.5355375C5.4289375 7.5 7.785950000000001 7.5 12.5 7.5h5c4.7139999999999995 0 7.071125 0 8.5355 1.4644625C27.5 10.4289375 27.5 12.786 27.5 17.5s0 7.071125 -1.4645 8.5355C24.571125000000002 27.5 22.214 27.5 17.5 27.5h-5c-4.71405 0 -7.0710625 0 -8.5355375 -1.4645C2.5 24.571125000000002 2.5 22.214 2.5 17.5Z" fill="none" stroke="currentColor" stroke-width="2" />
        <path d="M12.5 18.75H7.5c-0.5892499999999999 0 -0.8838875 0 -1.0669375 0.183C6.25 19.116125 6.25 19.41075 6.25 20v1.25c0 0.5892499999999999 0 0.883875 0.1830625 1.067C6.6161125 22.5 6.91075 22.5 7.5 22.5h5c0.5892499999999999 0 0.883875 0 1.067 -0.183C13.75 22.133875 13.75 21.83925 13.75 21.25v-1.25c0 -0.5892499999999999 0 -0.883875 -0.183 -1.067C13.383875 18.75 13.08925 18.75 12.5 18.75Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="2" />
    </g>
</x-icons.root>
