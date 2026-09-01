@props([
    'name' => null,       // full name — initials are derived from its first two words
    'initials' => null,   // or pass them explicitly
    'tone' => 'neutral',  // neutral | green | blue | amber | rose | violet
    'size' => 'md',       // sm (28px) | md (34px) | lg (48px)
])

@php
    use Illuminate\Support\Str;

    $resolved = $initials;

    if ($resolved === null) {
        $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $resolved = collect($words)
            ->take(2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }

    // Spelled out so Tailwind keeps them; the tone mirrors the row's status chip.
    $toneClasses = match ($tone) {
        'green', 'emerald' => 'bg-[#d1fae5] text-[#047857]',
        'blue', 'sky' => 'bg-[#e0f2fe] text-[#0369a1]',
        'amber' => 'bg-[#fef3c7] text-[#b45309]',
        'rose', 'red' => 'bg-[#ffe4e6] text-[#be123c]',
        'violet', 'purple' => 'bg-[#ede9fe] text-[#6d28d9]',
        default => 'bg-[#f4f4f5] text-[#52525b]',
    };

    $sizeClasses = match ($size) {
        'sm' => 'h-7 w-7 rounded-[9px] text-[10.5px]',
        'lg' => 'h-12 w-12 rounded-2xl text-[15px]',
        default => 'h-[34px] w-[34px] rounded-[11px] text-[12px]',
    };
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex shrink-0 select-none items-center justify-center font-semibold tracking-[-0.01em] '.$sizeClasses.' '.$toneClasses]) }}
    @if ($name) title="{{ $name }}" @endif
    aria-hidden="true"
>{{ $resolved }}</span>
