@props([
    'rows' => 3,
    'disabled' => false,
])

<textarea rows="{{ $rows }}" @disabled($disabled) {{ $attributes->merge(['class' => \App\Support\Ui\FieldStyles::textarea()]) }}>{{ $slot }}</textarea>
