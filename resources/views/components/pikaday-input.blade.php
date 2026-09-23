@props([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default',
     'format' => 'Y-MM-DD',
     'script'
])

@php
     $wireModel = collect($attributes->getAttributes())
          ->first(fn ($value, $key) => str_starts_with($key, 'wire:model'));
     $hasError = $errors->has($name) || (is_string($wireModel) && $errors->has($wireModel));
     $isError = $hasError ? 'border-rose-300 bg-rose-50' : '';

     // Livewire keeps dates as Y-MM-DD; people read and type them as DD.MM.YYYY. Any other
     // format a caller passes is used as given.
     $format = $format === 'Y-MM-DD' ? 'DD.MM.Y' : $format;
     $currentYear = \Carbon\Carbon::now()->format('Y');
@endphp

<input
    type="{{ $type }}"
    id="{{ $name }}"
    name="{{ $name }}"
    x-data="{ picker: null, destroy() { if (this.picker) this.picker.destroy(); this.picker = null; } }"
    x-ref="input"
    x-on:change="$dispatch('input', $el.value)"
    x-init="picker = (function (pikaday, $el) {
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
    @if ($hasError) aria-invalid="true" @endif
    {!! $attributes->merge(['class' => \App\Support\Ui\FieldStyles::input(trim('mt-1 block '.$isError))]) !!}

>
