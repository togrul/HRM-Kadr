@props([
     'disabled' => false,
     'name',
     'mode' => 'default',
     'placeholder'
])

@php
     $wireModel = collect($attributes->getAttributes())
          ->first(fn ($value, $key) => str_starts_with($key, 'wire:model'));
     $hasError = $errors->has($name) || (is_string($wireModel) && $errors->has($wireModel));
     $errorClasses = 'border-rose-300 bg-rose-50';
     $isError = $hasError ? $errorClasses : '';
@endphp

<textarea
     id="{{ $name }}"
     rows="3"
     @disabled($disabled)
     @if ($hasError) aria-invalid="true" data-error-classes="{{ $errorClasses }}" @endif
     {!! $attributes->merge(['class' => \App\Support\Ui\FieldStyles::textarea(trim('mt-1 block '.$isError))]) !!}
     placeholder="{{$placeholder}}">
</textarea>
