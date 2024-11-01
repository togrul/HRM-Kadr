@props([
    'color' => 'text-gray-500',
    'hover' => 'text-gray-700',
    'size' => 'w-8 h-8',
    'animated' => false
])

<svg {{ $attributes->merge(['class' => "$size $color transition-all duration-300 hover:$hover"]) }}
     xmlns="http://www.w3.org/2000/svg"
     xmlns:xlink="http://www.w3.org/1999/xlink"
     width="24px"
     height="24px"
     viewBox="0 0 24 24"
     @if($animated)
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90"
         x-transition:enter-end="opacity-100 scale-100"
     @endif
>
    <defs/>
    {{ $slot }}
</svg>
