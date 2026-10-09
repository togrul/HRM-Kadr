@props([
    'title' => '',
    'label' => null,
    'tooltip' => null,
])

@php
    // The attribute bag already holds escaped values; decode so the echo below escapes once.
    $attributeLabel = html_entity_decode((string) $attributes->get('aria-label'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $accessibleLabel = trim((string) ($label ?: $attributeLabel ?: $title));
    $tooltipText = trim((string) ($tooltip ?: $accessibleLabel));
@endphp

<button
    {{ $attributes->except(['aria-label', 'title', 'data-tooltip'])->merge([
        'class' => 'inline-flex h-10 w-10 items-center justify-center rounded-xl border border-transparent text-zinc-500 transition-all duration-200 hover:bg-zinc-100 hover:text-zinc-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300 disabled:pointer-events-none disabled:opacity-50',
        'type' => 'button',
    ]) }}
    @if($accessibleLabel !== '') aria-label="{{ $accessibleLabel }}" @endif
    @if($tooltipText !== '') title="{{ $tooltipText }}" data-tooltip="{{ $tooltipText }}" @endif
>
    {{ $slot }}
</button>
