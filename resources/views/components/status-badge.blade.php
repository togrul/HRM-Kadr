@props([
    'valid' => false
])

<div @class([
    'px-3 py-1 text-xs rounded-lg font-medium w-max max-w-[120px] flex justify-center items-center space-x-2 shadow-sm',
    'bg-emerald-50 text-emerald-500' => $valid,
    'bg-rose-50 text-rose-500' => ! $valid
])>
     <span @class([
           'w-2 h-2 rounded-full shadow-sm flex',
           'bg-emerald-400' => $valid ,
           'bg-rose-400' => ! $valid ,
    ])>
     </span>
    <span class="uppercase">{{ $valid ? __('Active') : __('De-active') }}</span>
</div>
