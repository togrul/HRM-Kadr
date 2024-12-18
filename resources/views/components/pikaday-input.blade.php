@props([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default',
     'format',
     'script'
])

@php
     $extraClass = match($mode)
     {
          'default' => 'bg-white',
          'gray' => "bg-gray-100"
     };
     $isError = $errors->has($name)?'bg-red-50':'';

     $format = "Y-MM-DD" ? 'DD.MM.Y' : $format;
     $currentYear = \Carbon\Carbon::now()->format('Y');
@endphp

<input
    type="{{ $type }}"
    id="{{ $name }}"
    name="{{ $name }}"
    x-data
    x-ref="input"
    x-on:change="$dispatch('input', $el.value)"
    x-init="(function (pikaday, $el) {
          pikaday.defaultDate = $el.value;
          {{ $script ?? '' }} ;
          return pikaday;
        })(new Pikaday({
          field: $el,
          format: '{{ $format }}',
          yearRange: [1900, {{ $currentYear }}],
          onSelect: function (date) { $el.value = moment(date.toString()).format('{{ $format }}'); }
         }), $el)"
    {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge(['class' => "block border-none font-normal w-full mt-1 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-100 ease-in-out transform {$extraClass} {$isError} "]) !!}

>
