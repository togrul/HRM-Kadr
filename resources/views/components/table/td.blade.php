@props([
     'isButton' => false,
     'extraClasses',
     'standartWidth' => false
])

@php
     $extraClasses = $isButton ? 'text-sm font-medium text-right px-3' : 'px-6';
     $extraClasses .= !$standartWidth ? ' whitespace-nowrap':'';
@endphp

<td {{ $attributes->merge(['class' => "py-4 {$extraClasses}"]) }}>
     {{ $slot }}
</td>
