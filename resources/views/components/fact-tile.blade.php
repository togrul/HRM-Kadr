@props([
    'label',
    'value' => null,   // main line; falls back to the slot
    'note' => null,    // secondary line
    'tone' => 'ink',   // ink | green | rose | blue | amber
])

@php
    $valueTone = match ($tone) {
        'green', 'emerald' => 'text-[#047857]',
        'rose', 'red' => 'text-[#be123c]',
        'blue', 'sky' => 'text-[#0369a1]',
        'amber' => 'text-[#b45309]',
        default => 'text-ink',
    };
@endphp

{{-- Label + fact + optional note. The detail screens' standard read-only cell. --}}
<div {{ $attributes->merge(['class' => 'rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3']) }}>
    <p class="hrm-eyebrow">{{ $label }}</p>
    <p class="mt-1.5 text-[14px] font-semibold tracking-[-0.02em] {{ $valueTone }}">{{ $value ?? $slot }}</p>
    @if ($note !== null)
        <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ $note }}</p>
    @endif
</div>
