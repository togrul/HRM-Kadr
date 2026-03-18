@props([
    'for' => null,
    'as' => 'label',
    'class' => '',
])

@php
    $tag = in_array($as, ['label', 'div', 'span', 'p'], true) ? $as : 'label';
    $classes = trim('text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400 '.$class);
@endphp

<{{ $tag }} @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $tag }}>
