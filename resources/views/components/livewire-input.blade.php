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
     $isError = $errors->has($name) ? 'border-rose-300 bg-[#ffe4e6] focus:bg-[#fff1f2]' : '';
@endphp

<input
     type="{{ $type }}"
     id="{{ $name }}"
     name="{{ $name }}"
     @disabled($disabled)
     @if ($errors->has($name)) aria-invalid="true" @endif
     {!! $attributes->merge(['class' => 'mt-1 '.\App\Support\Ui\FieldStyles::input(trim($extra.' '.$isError))]) !!}
>
