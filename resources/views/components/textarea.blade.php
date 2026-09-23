@props([
     'disabled' => false,
     'name',
     'mode' => 'default',
     'placeholder'
])

@php
     $isError = $errors->has($name) ? 'border-rose-300 bg-rose-50' : '';
@endphp

<textarea
     id="{{ $name }}"
     rows="3"
     @disabled($disabled)
     @if ($errors->has($name)) aria-invalid="true" @endif
     {!! $attributes->merge(['class' => \App\Support\Ui\FieldStyles::textarea(trim('mt-1 block '.$isError))]) !!}
     placeholder="{{$placeholder}}">
</textarea>
