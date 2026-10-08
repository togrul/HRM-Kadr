@props([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default',
     'format' => 'Y-MM-DD',
     'min' => null,
     'max' => null,
     'script'
])

@php
     $wireModelKey = collect($attributes->getAttributes())
          ->keys()
          ->first(fn ($key) => str_starts_with($key, 'wire:model'));
     $wireModel = $wireModelKey ? $attributes->get($wireModelKey) : null;
     $hasError = $errors->has($name) || (is_string($wireModel) && $errors->has($wireModel));
     $errorClasses = 'border-rose-300 bg-rose-50';
     $isError = $hasError ? $errorClasses : '';

     // Livewire keeps dates as Y-MM-DD; people read and type them as DD.MM.YYYY. Any other
     // format a caller passes is used as given.
     $format = $format === 'Y-MM-DD' ? 'DD.MM.Y' : $format;
     $masked = $format === 'DD.MM.Y';
     $minDate = filled($min) ? \Carbon\Carbon::parse($min)->toDateString() : null;
     $maxDate = filled($max) ? \Carbon\Carbon::parse($max)->toDateString() : null;
     $placeholder = $attributes->get('placeholder') ?? ($masked ? __('ui::date.placeholder') : null);
@endphp

{{-- The calendar's language, Monday-first weeks and keyboard come from the shared wrapper in
     resources/js/date-picker.js; this component only adds the field-specific bits. --}}
<input
    type="{{ $type }}"
    id="{{ $name }}"
    name="{{ $name }}"
    data-date-input
    autocomplete="off"
    @if ($masked) inputmode="numeric" maxlength="10" x-on:input="window.hrmMaskDateInput && window.hrmMaskDateInput($event)" @endif
    x-data="{ picker: null, destroy() { if (this.picker) this.picker.destroy(); this.picker = null; } }"
    x-ref="input"
    {{-- Sync on change only (blur after typing, or a picker pick — Pikaday fires change):
         a .live binding sent every keystroke ("1", "12.0"…) to the server mid-typing. --}}
    @if ($wireModel) wire:model.change="{{ $wireModel }}" data-error-key="{{ $wireModel }}" @endif
    x-init="picker = (function (pikaday, $el) {
          pikaday.defaultDate = $el.value;
          {{ $script ?? '' }} ;
          return pikaday;
        })(new Pikaday({
          field: $el,
          format: '{{ $format }}',
          yearRange: 100,
          minDate: {{ $minDate ? "new Date('".$minDate."T00:00:00')" : 'null' }},
          maxDate: {{ $maxDate ? "new Date('".$maxDate."T00:00:00')" : 'null' }},
          onSelect: function (date) { $el.value = moment(date.toString()).format('{{ $format }}'); }
         }), $el)"
    @disabled($disabled)
    @if ($hasError) aria-invalid="true" data-error-classes="{{ $errorClasses }}" @endif
    {!! $attributes->except(array_filter([$wireModelKey, 'placeholder']))->merge(['class' => \App\Support\Ui\FieldStyles::input(trim('mt-1 block '.$isError))]) !!}
    @if (filled($placeholder)) placeholder="{{ $placeholder }}" @endif

>
