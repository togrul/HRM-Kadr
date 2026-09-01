{{--
    The summary strip every şəxsi kabinet tab opens with.
    $metrics: [['label' => string, 'value' => int|string, 'dot' => 'bg-…'|null], ...]
--}}
<div class="grid gap-3 p-3 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($metrics as $metric)
        <div class="rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3">
            <div class="flex items-center justify-between gap-2">
                <span class="hrm-eyebrow">{{ $metric['label'] }}</span>
                @if (! empty($metric['dot']))
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $metric['dot'] }}"></span>
                @endif
            </div>
            <p class="hrm-num mt-1.5 text-[20px] font-semibold tracking-[-0.02em] text-ink">{{ $metric['value'] }}</p>
        </div>
    @endforeach
</div>
