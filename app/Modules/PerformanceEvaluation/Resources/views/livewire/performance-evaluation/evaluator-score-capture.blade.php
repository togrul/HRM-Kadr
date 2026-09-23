<div class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
    <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5">
        <p class="text-[13px] font-semibold text-ink">{{ __('performance_evaluation::dashboard.cards.score_capture') }}</p>
        <svg class="h-4 w-4 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9"/></svg>
    </div>

    <div class="grid content-start gap-4 p-5">
        <p class="rounded-xl bg-[#fafafa] px-3 py-2.5 text-[12.5px] leading-5 text-ink-muted">{{ __('performance_evaluation::dashboard.labels.assigned_score_form_hint') }}</p>

        <div>
            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.evaluation_form')" placeholder="---" mode="gray" class="w-full" instance="perf-evaluator-form"
                wire:model.live="scoreForm.performance_form_id"
                :model="$this->formOptions"></x-ui.select-dropdown>
            @error('scoreForm.performance_form_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div>
            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.item')" placeholder="---" mode="gray" class="w-full" instance="perf-evaluator-item"
                wire:model.live="scoreForm.performance_form_template_item_id"
                :model="$this->formItemOptions()"></x-ui.select-dropdown>
            @error('scoreForm.performance_form_template_item_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div>
            <x-label for="evaluator-score">{{ __('performance_evaluation::dashboard.fields.score') }}</x-label>
            <x-livewire-input mode="gray" id="evaluator-score" type="number" step="0.01" wire:model="scoreForm.score" />
            @error('scoreForm.score') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div>
            <x-label for="evaluator-comment">{{ __('performance_evaluation::dashboard.fields.comment') }}</x-label>
            <textarea id="evaluator-comment" wire:model="scoreForm.comment" rows="3" class="w-full rounded-xl border border-hairline bg-[#fafafa] px-3 py-2 text-[13px] text-ink focus:border-zinc-400 focus:bg-white focus:outline-none focus:ring-0"></textarea>
            @error('scoreForm.comment') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div class="flex justify-end">
            <button type="button" wire:click="saveAssignedScore" wire:loading.attr="disabled" wire:target="saveAssignedScore" class="h-10 rounded-xl bg-ink px-5 text-[14px] font-semibold text-white transition hover:bg-ink-hover">{{ __('performance_evaluation::dashboard.actions.save_score') }}</button>
        </div>
    </div>
</div>
