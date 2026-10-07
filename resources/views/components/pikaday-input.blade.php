@props([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default',
     'format' => 'Y-MM-DD',
     'script'
])

@php
     $wireModelKey = collect($attributes->getAttributes())
          ->keys()
          ->first(fn ($key) => str_starts_with($key, 'wire:model'));
     $wireModel = $wireModelKey ? $attributes->get($wireModelKey) : null;
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
    {{-- Sync on change only (blur after typing, or a picker pick — Pikaday fires change):
         a .live binding sent every keystroke ("1", "12.0"…) to the server mid-typing. --}}
    @if ($wireModel) wire:model.change="{{ $wireModel }}" @endif
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
    {!! $attributes->except(array_filter([$wireModelKey]))->merge(['class' => \App\Support\Ui\FieldStyles::input(trim('mt-1 block '.$isError))]) !!}

>
