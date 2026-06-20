@props([
     'mode' => 'default'
])

<nav {{ $attributes->merge(['class' => 'flex flex-col items-start justify-start text-sm']) }}>
     <ul @class([
          'flex flex-wrap py-[1px] px-[1px] border font-medium rounded-lg gap-[1px]',
          'border-gray-200 bg-gray-100' => $mode == 'default',
          'border-slate-800 bg-slate-900' => $mode == 'dark'
     ])>
          {{ $slot }}
     </ul>
</nav>
