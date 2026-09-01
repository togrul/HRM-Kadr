@props([
    'mode' => 'default',
    'disabled' => false,
    'type' => 'button',
])

@php
    // "Nazik rail" design tokens: ink for primary, soft semantic fills for state actions.
    $extraClasses = match ($mode) {
        'default' => 'border-hairline bg-white text-ink-soft hover:bg-[#fafafa] hover:text-ink active:bg-hairline-subtle',
        'secondary', 'slate' => 'border-hairline bg-[#f4f4f5] text-ink-soft hover:bg-hairline active:bg-hairline',
        'gray', 'black', 'primary' => 'border-transparent bg-ink text-white hover:bg-ink-hover active:bg-ink-hover',
        'success', 'approve', 'step-next', 'light-green'
            => 'border-transparent bg-[#d1fae5] text-[#047857] hover:bg-emerald-200 active:bg-emerald-200',
        'warning' => 'border-transparent bg-[#fef3c7] text-[#b45309] hover:bg-amber-200 active:bg-amber-200',
        'danger', 'light-red', 'rose', 'reject'
            => 'border-transparent bg-[#ffe4e6] text-[#be123c] hover:bg-rose-200 active:bg-rose-200',
        'light-blue', 'teal' => 'border-transparent bg-[#e0f2fe] text-[#0369a1] hover:bg-sky-200 active:bg-sky-200',
        'step-prev' => 'border-transparent bg-[#f4f4f5] text-ink-muted hover:bg-hairline active:bg-hairline',
        default => 'border-hairline bg-white text-ink-soft hover:bg-[#fafafa] hover:text-ink active:bg-hairline-subtle',
    };

    $baseClasses = 'camelcase inline-flex h-9 items-center justify-center gap-2 whitespace-nowrap rounded-[10px] border px-3.5 text-[12.5px] font-semibold tracking-[-0.01em] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300 disabled:pointer-events-none disabled:opacity-50 ';
@endphp

@if ($type !== 'link')
    <button @disabled($disabled)
        {{ $attributes->merge(['type' => $type, 'class' => $baseClasses . $extraClasses]) }}>
        {{ $slot }}
    </button>
@else
    <a {{ $attributes->merge(['class' => $baseClasses . $extraClasses]) }}>
        {{ $slot }}
    </a>
@endif
