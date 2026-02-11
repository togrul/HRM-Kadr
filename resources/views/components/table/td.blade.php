@props([
    'isButton' => false,
    'extraClasses' => '',
    'standartWidth' => false,
])

@php
    $baseClasses = $isButton
        ? 'text-sm font-medium text-right px-3'
        : 'px-6';

    if (! $standartWidth) {
        $baseClasses .= ' whitespace-nowrap';
    }

    $cellClasses = trim("{$baseClasses} {$extraClasses}");
@endphp

<td {{ $attributes->merge(['class' => $cellClasses]) }}>
    {{ $slot }}
</td>
