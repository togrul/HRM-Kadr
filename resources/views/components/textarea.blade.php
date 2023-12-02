@props([
     'disabled' => false,
     'name',
     'mode' => 'default',
     'placeholder'
])

@php
     $extraClass = match($mode)
     {
          'default' => 'bg-white',
          'gray' => 'bg-gray-100'
     };
     $isError = $errors->has($name)?'bg-red-50':'';
@endphp

<textarea 
     id="{{ $name }}" 
     rows="3" 
     {{ $disabled ? 'disabled' : '' }}
     {!! $attributes->merge(['class' => 'p-2.5 w-full border-none mt-1 rounded-lg shadow-sm text-sm font-normal text-gray-900 block focus:ring-blue-500 focus:border-blue-500 '.$extraClass]) !!}
     placeholder="{{$placeholder}}">
</textarea>
