@props([
    'size' => 'lg',
])

@php
    // Müştəriyə xas loqo. `env()` burada işlədilə bilməz — konfiqurasiya keşlənəndə
    // .env yüklənmir və çağırış null qaytarır; ona görə dəyər config-dən oxunur.
    $file = config('database.connections.mysql.database') === 'dmx_hr'
        ? 'assets/images/logo_dmx.png'
        : 'assets/images/logo3.png';
@endphp

<img
    src="{{ asset($file) }}"
    alt="{{ config('app.name') }}"
    @class([
        'h-36' => $size === 'lg',
        'h-24' => $size === 'sm',
        'h-12' => $size === 'xs',
        // Kvadrat nişan: ətrafındakı işıq effekti səbəbindən görünən hissə
        // faktiki hündürlükdən kiçikdir, ona görə söz-loqodan bir qədər böyükdür.
        'h-11 w-auto object-contain' => $size === 'wordmark',
    ])
/>
