@props([
    'size' => 'lg',
])

<img src="{{ asset(env('DB_DATABASE') == 'dmx_hr' ? 'assets/images/logo_dmx.png' : 'assets/images/logo2.png') }}" alt="logo" @class([
    'h-36' => $size == 'lg',
    'h-24' => $size == 'sm',
    'h-12' => $size == 'xs',
]) />
