@props([
    'color' => 'text-gray-700',
    'hover' => 'text-gray-900',
    'size' => 'w-6 h-6',
    'show' => null,
])

<x-icons.root animated="false" x-show="{{ $show ?? 'true' }}" :$size :$color :$hover>
    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2">
        <path d="M4.5 10.5C4.5 8.61438 4.5 7.67157 5.08579 7.08579C5.67157 6.5 6.61438 6.5 8.5 6.5H15.5C17.3856 6.5 18.3284 6.5 18.9142 7.08579C19.5 7.67157 19.5 8.61438 19.5 10.5V16C19.5 17.8856 19.5 18.8284 18.9142 19.4142C18.3284 20 17.3856 20 15.5 20H8.5C6.61438 20 5.67157 20 5.08579 19.4142C4.5 18.8284 4.5 17.8856 4.5 16V10.5Z"/>
        <path d="M9 6.5V6C9 4.89543 9.89543 4 11 4H13C14.1046 4 15 4.89543 15 6V6.5"/>
        <path d="M4.5 11.5H19.5"/>
        <path d="M10.5 11.5V13C10.5 13.2761 10.7239 13.5 11 13.5H13C13.2761 13.5 13.5 13.2761 13.5 13V11.5"/>
    </g>
</x-icons.root>
