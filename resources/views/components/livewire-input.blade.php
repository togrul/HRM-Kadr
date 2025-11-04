@props([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default'
])

@php
     $extraClass = match($mode)
     {
          'default' => 'bg-white',
          'gray' => "bg-neutral-100",
          'disabled' => "bg-neutral-200"
     };
     $isError = $errors->has($name)?'bg-red-50':'';
@endphp

<input
     type="{{ $type }}"
     id="{{ $name }}"
     name="{{ $name }}"
     {{ $disabled ? 'disabled' : '' }}
     {!! $attributes->merge(['class' => "block border-none font-normal w-full mt-1 rounded-lg shadow-sm focus:ring-blue-500 px-3 py-2 focus:border-blue-500 sm:text-sm transition duration-100 ease-in-out transform {$extraClass} {$isError} "]) !!}

>
