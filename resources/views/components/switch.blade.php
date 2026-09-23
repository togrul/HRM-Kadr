@props([
    'color' => 'default'
])
@php
    $classes = $color == 'default' ? 'bg-zinc-100' : 'bg-white';
@endphp
<div class="hs-tooltip flex items-center">
     <input type="checkbox" {!! $attributes->merge(['class' => "hs-tooltip-toggle $classes relative shrink-0 w-[3.25rem] h-7 checked:bg-none checked:bg-blue-600 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 border border-transparent ring-1 ring-transparent focus:border-blue-600 focus:ring-zinc-400 ring-offset-white focus:outline-none appearance-none dark:bg-zinc-700 dark:checked:bg-blue-600 dark:focus:ring-offset-zinc-800
     before:inline-block before:w-6 before:h-6 before:bg-white checked:before:bg-blue-200 before:translate-x-0 checked:before:translate-x-full before:shadow before:rounded-full before:transform before:ring-0 before:transition before:ease-in-out before:duration-200 dark:before:bg-zinc-400 dark:checked:before:bg-blue-200"]) !!} id="hs-tooltip-example">
     <label for="hs-tooltip-example" class="text-sm font-medium text-zinc-500 ml-3 dark:text-zinc-400">{{$slot}}</label>
 </div>

 