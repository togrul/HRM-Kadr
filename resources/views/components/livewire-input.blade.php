@props([
     'disabled' => false,
     'type' => 'text',
     'name',
     'mode' => 'default'
])

@php
     // `mode` only decides the resting fill; geometry and focus come from FieldStyles.
     $extra = match ($mode) {
          'default', 'gray' => '',
          'disabled' => 'text-ink-faint',
          default => '',
     };
     $wireModel = collect($attributes->getAttributes())
          ->first(fn ($value, $key) => str_starts_with($key, 'wire:model'));
     $hasError = $errors->has($name) || (is_string($wireModel) && $errors->has($wireModel));
     $errorClasses = 'border-rose-300 bg-[#ffe4e6] focus:bg-[#fff1f2]';
     $isError = $hasError ? $errorClasses : '';
@endphp

@if ($type === 'date')
     {{-- one calendar app-wide instead of the browser's native date picker --}}
     <x-ui.date-input :disabled="$disabled" id="{{ $attributes->get('id', $name) }}" {{ $attributes->except('id')->merge(['class' => 'mt-1']) }} />
@elseif ($type === 'time')
     {{-- the app's 24-hour time field instead of the browser's own --}}
     <div class="mt-1"><x-ui.time-input :disabled="$disabled" id="{{ $attributes->get('id', $name) }}" {{ $attributes->except('id') }} /></div>
@else
<input
     type="{{ $type }}"
     id="{{ $name }}"
     name="{{ $name }}"
     @disabled($disabled)
     @if ($hasError) aria-invalid="true" data-error-classes="{{ $errorClasses }}" data-error-key="{{ is_string($wireModel) ? $wireModel : $name }}" @endif
     {!! $attributes->merge(['class' => 'mt-1 '.\App\Support\Ui\FieldStyles::input(trim($extra.' '.$isError))]) !!}
>
@endif
