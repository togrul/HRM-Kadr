@props([
    'size' => 'lg',
])

<img src="{{ asset('assets/images/logo3.png') }}" alt="logo" @class([
    'h-40' => $size == 'lg',
    'h-24' => $size == 'sm',
    'h-16' => $size == 'xs',
]) />
