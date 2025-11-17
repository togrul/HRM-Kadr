@props([
     'mode' => 'default'
])

<nav class="flex-col items-center justify-between text-sm sm:flex">
     <ul @class([
          'flex py-[1px] px-[1px] border font-medium rounded-lg space-x-[1px]',
          'border-gray-200 bg-gray-100' => $mode == 'default',
          'border-slate-800 bg-slate-900' => $mode == 'dark'
     ])>
          {{ $slot }}
     </ul>
</nav>
