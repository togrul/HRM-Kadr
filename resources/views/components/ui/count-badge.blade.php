@props([
    'count' => 0,
    'max' => 9,     // an icon-sized badge shows at most "9+"; the exact number is in the label
    'label' => null, // spoken / tooltip text, e.g. "12 unread notifications"
])

@php
    $count = max(0, (int) $count);
    $display = $count > $max ? $max.'+' : (string) $count;
@endphp

@if ($count > 0)
    {{-- Compact pill for an icon: 16px tall, grows sideways only for "9+", with a ring in the
         rail's colour so it reads as sitting on top of the icon rather than colliding with it. --}}
    <span
        {{ $attributes->class('pointer-events-none absolute right-0.5 top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-semibold leading-none tabular-nums text-white ring-2 ring-white dark:ring-neutral-900') }}
        @if (filled($label)) title="{{ $label }}" @endif
        aria-hidden="true"
        data-count="{{ $count }}"
    >{{ $display }}</span>
    @if (filled($label))
        <span class="sr-only">{{ $label }}</span>
    @endif
@endif
