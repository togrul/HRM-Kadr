@props([
    'label',
    'value',        // 0-100
    'caption' => null,
])

@php $pct = max(0, min(100, (float) $value)); @endphp

<div>
    <div class="flex items-baseline justify-between gap-2">
        <span class="truncate text-[12px] font-medium text-ink-soft">{{ $label }}</span>
        <span class="hrm-num shrink-0 text-[12px] font-semibold text-ink">{{ $caption ?? number_format($pct, 1).'%' }}</span>
    </div>
    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-[#f4f4f5]">
        <div class="h-full rounded-full bg-ink" style="width: {{ $pct }}%"></div>
    </div>
</div>
