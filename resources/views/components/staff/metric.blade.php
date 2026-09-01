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

<div class="hrm-metric">
    @if ($showLabel && $label)
        <span class="text-[10px] font-medium uppercase tracking-wide text-zinc-400">{{ $label }}</span>
    @endif
    {{-- an empty vacancy reads as a dash, not a zero, so a real opening stands out --}}
    <span class="hrm-metric-value {{ $valueClass }}">{{ $tone === 'vacant' && $v === 0 ? '—' : $v }}</span>
</div>
