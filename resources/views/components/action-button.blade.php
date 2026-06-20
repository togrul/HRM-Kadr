@props([
    'title' => '',
    'label' => null,
    'tooltip' => null,
])

@php
    $attributeLabel = $attributes->get('aria-label');
    $accessibleLabel = trim((string) ($label ?: $attributeLabel ?: $title));
    $tooltipText = trim((string) ($tooltip ?: $accessibleLabel));
@endphp

<button
    {{ $attributes->except(['aria-label', 'title', 'data-tooltip'])->merge([
        'class' => 'inline-flex h-10 w-10 items-center justify-center rounded-xl border border-transparent text-slate-500 transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 disabled:pointer-events-none disabled:opacity-50',
        'type' => 'button',
    ]) }}
    @if($accessibleLabel !== '') aria-label="{{ $accessibleLabel }}" @endif
    @if($tooltipText !== '') title="{{ $tooltipText }}" data-tooltip="{{ $tooltipText }}" @endif
>
    {{ $slot }}
</button>
