@props([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null,
    'active' => false,
])

<x-icons.root animated="true" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g transform="scale(0.5)">
        <path d="M22 25h4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M32 25h4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M16 25h-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M22 35h4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M32 35h4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M16 35h-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M11.003 8.563c-1.137 0.11 -2.12 0.225 -2.952 0.336 -2.199 0.293 -3.877 1.948 -4.192 4.144C3.451 15.887 3 20.41 3 26.5c0 6.09 0.451 10.614 0.859 13.458 0.315 2.196 1.993 3.85 4.192 4.144 3.13 0.417 8.38 0.898 15.949 0.898 7.568 0 12.818 -0.48 15.949 -0.898 2.199 -0.293 3.877 -1.948 4.192 -4.144 0.408 -2.844 0.859 -7.368 0.859 -13.458 0 -6.089 -0.451 -10.613 -0.859 -13.457 -0.315 -2.196 -1.993 -3.851 -4.192 -4.144a82.173 82.173 0 0 0 -2.952 -0.336" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M29 8.074A164.037 164.037 0 0 0 24 8c-1.796 0 -3.462 0.027 -5 0.074" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M29.013 9.27c0.043 2.08 1.409 3.694 3.489 3.726a32.976 32.976 0 0 0 0.996 0c2.08 -0.032 3.446 -1.646 3.489 -3.726a61.262 61.262 0 0 0 0 -2.54c-0.043 -2.08 -1.409 -3.694 -3.489 -3.726a32.444 32.444 0 0 0 -0.996 0c-2.08 0.032 -3.446 1.646 -3.489 3.726a61.262 61.262 0 0 0 0 2.54Z" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M11.013 9.27c0.043 2.08 1.409 3.694 3.489 3.726a32.976 32.976 0 0 0 0.996 0c2.08 -0.032 3.446 -1.646 3.489 -3.726a61.262 61.262 0 0 0 0 -2.54c-0.043 -2.08 -1.409 -3.694 -3.489 -3.726a32.444 32.444 0 0 0 -0.996 0c-2.08 0.032 -3.446 1.646 -3.489 3.726a61.262 61.262 0 0 0 0 2.54Z" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
    </g>
</x-icons.root>
