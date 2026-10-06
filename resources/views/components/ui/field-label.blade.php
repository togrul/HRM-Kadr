@props([
    'for' => null,
    'as' => 'label',
    'class' => '',
])

@php
    $tag = in_array($as, ['label', 'div', 'span', 'p'], true) ? $as : 'label';
    // Sentence case, medium weight: an uppercase semibold label shouted over the value it names.
    $classes = trim('text-[12.5px] font-medium text-ink-muted '.$class);
@endphp

<{{ $tag }} @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</{{ $tag }}>
