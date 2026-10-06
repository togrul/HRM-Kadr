@props([
    'color' => 'text-zinc-500',
    'hover' => 'text-zinc-700',
    'size' => 'w-8 h-8'
])

<svg 
  xmlns="http://www.w3.org/2000/svg" 
  {!!  $attributes->merge(['class' => "$size $color transition-all duration-300 hover:{$hover}"]) !!} 
  fill="none" 
  viewBox="0 0 24 24" 
  stroke="currentColor"
>
  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
</svg>

