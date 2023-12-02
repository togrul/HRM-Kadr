@props([
     'mode' => 'default'
])

<nav class="items-center justify-between flex-col text-sm sm:flex">
     <ul @class([
          'flex py-1 px-2 border font-medium rounded-lg',
          'border-gray-200 bg-gray-100' => $mode == 'default',
          'border-slate-800 bg-slate-900' => $mode == 'dark'
     ])>
          {{ $slot }}
     </ul>
</nav>
