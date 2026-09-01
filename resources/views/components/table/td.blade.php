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

<td {{ $attributes->merge(['class' => 'py-2.5 align-middle text-[13px] text-ink-soft ' . $cellClasses]) }}>
    {{ $slot }}
</td>
