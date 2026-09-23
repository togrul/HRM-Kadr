@props([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default',
     'format',
     'script'
])

@php
     $isError = $errors->has($name) ? 'border-rose-300 bg-rose-50' : '';

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
          yearRange: 100,
          onSelect: function (date) { $el.value = moment(date.toString()).format('{{ $format }}'); }
         }), $el)"
    @disabled($disabled)
    @if ($errors->has($name)) aria-invalid="true" @endif
    {!! $attributes->merge(['class' => \App\Support\Ui\FieldStyles::input(trim('mt-1 block '.$isError))]) !!}

>
