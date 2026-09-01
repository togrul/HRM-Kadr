@props([
    'items' => [],  // [['label' => ..., 'value' => ..., 'dot' => 'bg-...'], ...]
    'columns' => 2,
])

<div @class([
    'grid gap-1.5',
    'grid-cols-2' => $columns === 2,
    'grid-cols-3' => $columns === 3,
])>
    @foreach ($items as $item)
        <div class="rounded-xl border border-hairline bg-[#fafafa] px-2.5 py-2">
            <div class="flex items-center justify-between gap-1">
                <span class="truncate text-[10.5px] uppercase tracking-[0.06em] text-ink-faint">{{ $item['label'] }}</span>
                @if (! empty($item['dot']))
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $item['dot'] }}"></span>
                @endif
            </div>
            <p class="hrm-num mt-1 text-[15px] font-semibold text-ink">{{ $item['value'] }}</p>
        </div>
    @endforeach
</div>
