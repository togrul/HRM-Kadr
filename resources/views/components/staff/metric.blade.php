@props([
  'label' => null,
  'value' => 0,
  'tone' => 'total',     // total | filled | vacant
  'showLabel' => true,
])

@php
    $v = (int) $value;

    // Cəmi neutral, Dolu green, Vakant red only when there is an open slot.
    $valueClass = match ($tone) {
        'total'  => 'text-zinc-900',
        'filled' => $v > 0 ? 'text-emerald-600' : 'text-zinc-300',
        'vacant' => $v > 0 ? 'text-rose-600' : 'text-zinc-300',
        default  => 'text-zinc-700',
    };
@endphp

<div class="flex w-12 shrink-0 flex-col items-center gap-1">
    @if ($showLabel && $label)
        <span class="text-[10px] font-medium uppercase tracking-wide text-zinc-400">{{ $label }}</span>
    @endif
    <span class="text-[14px] font-semibold tabular-nums leading-none {{ $valueClass }}">{{ $v }}</span>
</div>
