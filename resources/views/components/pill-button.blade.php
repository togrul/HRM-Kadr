@props([
    'variant' => 'primary', // primary | secondary | emerald | danger | ghost
    'href' => null,
    'icon' => false,        // true → square icon-only button
    'type' => 'button',     // a duplicated type= attribute is ignored by the browser, so it is a prop
])

@php
    // Controls sit on white cards, so they carry a gray fill — a white button on a white
    // surface only reads as a button once you hover it.
    $tone = match ($variant) {
        'primary' => 'bg-ink text-white hover:bg-ink-hover active:scale-[0.98]',
        'emerald' => 'border border-hairline bg-[#f4f4f5] text-ink-soft hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700',
        'danger' => 'border border-hairline bg-[#f4f4f5] text-ink-soft hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600',
        'ghost' => 'text-ink-muted hover:bg-[#f4f4f5] hover:text-ink',
        default => 'border border-hairline bg-[#f4f4f5] text-ink-soft hover:border-zinc-300 hover:bg-[#e4e4e7] hover:text-ink',
    };
    $shape = $icon ? 'h-10 w-10 justify-center' : 'h-10 gap-2 px-4';
    $classes = "inline-flex items-center whitespace-nowrap rounded-[10px] text-[14px] font-semibold tracking-[-0.01em] transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 {$shape} {$tone}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
