@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => \App\Support\Ui\FieldStyles::input()]) }}>
