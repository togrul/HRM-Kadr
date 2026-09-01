<section class="rounded-2xl border border-hairline bg-white shadow-card">
    <div class="border-b border-hairline-subtle px-4 py-2.5">
        <p class="hrm-eyebrow">{{ __('personnel::profile.sections.personal') }}</p>
    </div>

    <dl class="grid gap-x-8 sm:grid-cols-2">
        @foreach ($reader->personalRows($personnel) as $row)
            <div class="flex items-baseline justify-between gap-4 border-b border-hairline-subtle px-4 py-2.5 last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0">
                <dt class="shrink-0 text-[12.5px] text-ink-muted">{{ $row['label'] }}</dt>
                <dd @class(['min-w-0 truncate text-right text-[12.5px] font-medium text-ink', 'hrm-num' => $row['mono']]) title="{{ $row['value'] }}">{{ $row['value'] }}</dd>
            </div>
        @endforeach
    </dl>
</section>
