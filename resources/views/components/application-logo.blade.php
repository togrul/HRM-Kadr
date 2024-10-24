@props([
    'size' => 'lg'
])

<img src="{{ asset('assets/images/logo.png') }}" alt="logo" @class([
        'h-40' => $size == 'lg',
        'h-20' => $size == 'sm'
]) />
