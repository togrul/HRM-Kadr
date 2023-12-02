@props([
     'label',
     'id',
     'type' => 'text',
     'color' => 'light'
])

@php
     $extraClass = $color == 'light' ?'bg-slate-100' : 'bg-slate-900 text-white'; 
@endphp

<div class="flex flex-col {{ $color == 'light' ? 'bg-slate-100' : 'bg-slate-900' }} px-2 py-3 rounded-xl">
     <label for="{{$id}}" class="block font-medium text-sm ml-3 text-gray-400">{{$label}}</label>
     <input id="{{$id}}" name="{{$id}}" type="{{$type}}" {!! $attributes->merge(['class' =>  'py-0 border-none rounded-lg text-sm font-medium focus:ring-0 ' . $extraClass]) !!} autocomplete="off">
 </div>
