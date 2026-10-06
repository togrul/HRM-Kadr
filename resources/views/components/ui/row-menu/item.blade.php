@props([
    'href' => null,
    'danger' => false,
])

@php
    $classes = 'flex w-full items-center gap-2.5 px-3 py-2 text-left text-[13px] transition disabled:pointer-events-none disabled:opacity-50 '
        .($danger ? 'text-rose-600 hover:bg-rose-50' : 'text-ink-soft hover:bg-[#f4f4f5] hover:text-ink');
@endphp

@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="button" role="menuitem" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
