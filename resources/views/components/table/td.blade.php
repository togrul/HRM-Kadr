@props([
    'isButton' => false,
    'extraClasses' => '',
    'standartWidth' => false,
])

@php
    $baseClasses = $isButton
        ? 'text-right px-4'
        : 'px-5';

    if (! $standartWidth) {
        $baseClasses .= ' whitespace-nowrap';
    }

    $cellClasses = trim("{$baseClasses} {$extraClasses}");
@endphp

<td {{ $attributes->merge(['class' => 'py-3 align-middle text-sm text-zinc-700 ' . $cellClasses]) }}>
    {{ $slot }}
</td>
