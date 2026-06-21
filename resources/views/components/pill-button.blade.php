@props([
    'variant' => 'primary', // primary | secondary | emerald | danger
    'href' => null,
    'icon' => false,        // true → square icon-only button
])

@php
    $tone = match ($variant) {
        'primary' => 'bg-zinc-900 text-white shadow-sm hover:bg-zinc-700 active:scale-[0.98]',
        'emerald' => 'border border-zinc-200 bg-white text-zinc-700 hover:border-emerald-300 hover:bg-emerald-50',
        'danger' => 'border border-zinc-200 bg-white text-zinc-700 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600',
        default => 'border border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50',
    };
    $shape = $icon ? 'h-10 w-10 justify-center' : 'h-10 gap-2 px-4';
    $classes = "inline-flex items-center rounded-xl text-[13px] font-semibold transition {$shape} {$tone}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
