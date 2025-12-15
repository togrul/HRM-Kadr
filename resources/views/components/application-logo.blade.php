@props([
    'size' => 'lg',
])

<img src="{{ asset(env('DB_DATABASE') == 'dmx_hr' ? 'assets/images/logo_dmx.png' : 'assets/images/logo3.png') }}" alt="logo" @class([
    'h-40' => $size == 'lg',
    'h-24' => $size == 'sm',
    'h-16' => $size == 'xs',
]) />
