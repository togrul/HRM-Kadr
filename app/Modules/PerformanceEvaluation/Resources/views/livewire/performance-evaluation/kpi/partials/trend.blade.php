{{-- Score per cycle, oldest first: bar height against a 130% ceiling, dashed line at 100%. --}}
<div class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
    <div class="border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5">
        <p class="text-[13px] font-semibold text-ink">{{ $title }}</p>
    </div>
    <div class="relative px-5 pb-4 pt-6">
        <div class="pointer-events-none absolute inset-x-5 border-t border-dashed border-ink/20" style="top: calc(1.5rem + {{ (1 - 100 / 130) * 9 }}rem)"></div>
        <div class="flex h-36 items-end gap-3">
            @foreach ($rows as $row)
                @php $score = $row['score']; @endphp
                <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-1">
                    <span class="hrm-num text-[11px] font-semibold {{ $score === null ? 'text-ink-faint' : 'text-ink' }}">{{ $score === null ? '—' : rtrim(rtrim(number_format((float) $score, 1, '.', ''), '0'), '.').'%' }}</span>
                    <div class="w-full max-w-[44px] rounded-t-md {{ $score === null ? 'bg-[#f4f4f5]' : ((float) $score >= 100 ? 'bg-emerald-500' : ((float) $score >= 80 ? 'bg-amber-400' : 'bg-rose-500')) }}" style="height: {{ $score === null ? 4 : max(4, min(130, (float) $score) / 130 * 100) }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex gap-3">
            @foreach ($rows as $row)
                <p class="min-w-0 flex-1 truncate text-center text-[11px] text-ink-faint" title="{{ $row['cycle'] }}">{{ $row['cycle'] }}</p>
            @endforeach
        </div>
    </div>
</div>
