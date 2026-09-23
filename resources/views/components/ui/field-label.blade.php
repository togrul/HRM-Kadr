@props([
    'for' => null,
    'as' => 'label',
    'class' => '',
])

@php
    $tag = in_array($as, ['label', 'div', 'span', 'p'], true) ? $as : 'label';
    $classes = trim('text-[13px] font-semibold uppercase tracking-tight text-zinc-600 '.$class);
@endphp

<{{ $tag }} @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $tag }}>
