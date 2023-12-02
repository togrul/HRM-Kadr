@props([
     'active' => false,
     'mode' => 'default'
])

<li @class([
     'px-3 py-1 font-medium transition duration-150 ease-in  rounded-lg',
     'bg-white shadow-md' =>  $active && $mode == 'default',
     'bg-slate-700 shadow-md' =>  $active && $mode == 'dark',
     'text-black hover:bg-white' => $mode == 'default',
     'text-slate-100 hover:bg-slate-600' => $mode == 'dark'
 ])>
     <a href="#"
     {{ $attributes->merge(['class' => 'pb-3']) }}
     >
         {{ $slot }}
     </a>
 </li>