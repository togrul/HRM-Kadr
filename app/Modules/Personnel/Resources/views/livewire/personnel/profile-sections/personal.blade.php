<section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
    <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-5 py-3.5">
        <h2 class="text-[15px] font-semibold tracking-[-0.02em] text-ink">{{ __('personnel::profile.sections.personal') }}</h2>
        @if ($this->canEdit)
            <button type="button" wire:click="setSection('personal')" class="text-[13px] font-medium text-ink-muted transition hover:text-ink">{{ __('personnel::common.actions.edit') }}</button>
        @endif
    </div>

    {{-- A ruled grid sized by the CARD, not the viewport: a cell is at least 18rem, so a
         half-width card gets one column and a wide one two (the fixed label column never
         squeezes the value to nothing). Every cell draws its right and bottom rule;
         -mr-px/-mb-px tuck the outer ones under the card edge. --}}
    <dl class="-mb-px -mr-px grid grid-cols-[repeat(auto-fill,minmax(18rem,1fr))]">
        @foreach ($reader->personalRows($personnel) as $row)
            <div class="grid grid-cols-[8.5rem_minmax(0,1fr)] gap-3 border-b border-r border-hairline-subtle px-5 py-3">
                <dt class="break-words text-[13px] text-ink-muted">{{ $row['label'] }}</dt>
                @if ($row['empty'])
                    <dd class="text-[13px] text-ink-faint">—</dd>
                @else
                    <dd @class(['break-words text-[13px] font-medium text-ink', 'hrm-num' => $row['mono']])>{{ $row['value'] }}</dd>
                @endif
            </div>
        @endforeach
    </dl>
</section>
