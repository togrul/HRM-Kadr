<section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
    <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-5 py-3.5">
        <h2 class="text-[15px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::profile.sections.personal') }}</h2>
        @if ($this->canEdit)
            <button type="button" wire:click="setSection('personal')" class="text-[13px] font-medium text-ink-muted transition hover:text-ink">{{ __('personnel::common.actions.edit') }}</button>
        @endif
    </div>

    {{-- A ruled grid: every cell carries its bottom rule and the left column its right rule;
         -mb-px tucks the last row's rule under the card edge. --}}
    <dl class="-mb-px grid sm:grid-cols-2">
        @foreach ($reader->personalRows($personnel) as $row)
            <div class="grid grid-cols-2 gap-4 border-b border-hairline-subtle px-5 py-3 sm:odd:border-r">
                <dt class="truncate text-[13px] text-ink-muted">{{ $row['label'] }}</dt>
                @if ($row['empty'])
                    <dd class="truncate text-[13px] italic text-ink-faint">{{ __('personnel::profile.labels.not_set') }}</dd>
                @else
                    <dd @class(['truncate text-[13px] font-medium text-ink', 'hrm-num' => $row['mono']]) title="{{ $row['value'] }}">{{ $row['value'] }}</dd>
                @endif
            </div>
        @endforeach
    </dl>
</section>
