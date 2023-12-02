@props([
     'name',
     'model',
     'value',
     'selected' => false,
     'hidden' => false
])

@php
     $extraClass = $selected || $hidden ? 'text-gray-900' : 'text-gray-500';
@endphp

<div class="max-w-sm flex">
     <label class="inline-flex items-center cursor-pointer {{ $hidden ? 'line-through' : '' }}">
       <input
          {{ $attributes->merge(['class' => "relative w-5 h-5 mr-2 bg-trueGray-100 text-green-400 border border-gray-300 rounded focus:ring-green-500 focus:ring-opacity-25"]) }}
           wire:model.live="{{ $model }}" 
           @if(!empty($value))  value="{{ $value }}"  @endif
           name="{{ $name }}"  
           type="checkbox" 
           {{ $hidden ? 'disabled' : '' }}
       />
       <span class="text-sm font-medium {{ $extraClass }}">{{ $slot }}</span>
     </label>
</div>