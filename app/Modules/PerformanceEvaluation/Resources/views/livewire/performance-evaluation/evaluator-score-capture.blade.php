<x-surface-card :title="__('performance_evaluation::dashboard.cards.score_capture')" icon="icons.profile-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
    <div class="grid gap-3 content-start">
        <div class="rounded-3xl border border-zinc-200 bg-gradient-to-br from-white to-sky-50 px-4 py-3">
            <p class="text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.assigned_score_form_hint') }}</p>
        </div>

        <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.evaluation_form')" placeholder="---" mode="gray" class="w-full" instance="perf-evaluator-form"
            wire:model.live="scoreForm.performance_form_id"
            :model="$this->formOptions"></x-ui.select-dropdown>
        @error('scoreForm.performance_form_id') <x-validation>{{ $message }}</x-validation> @enderror

        <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.item')" placeholder="---" mode="gray" class="w-full" instance="perf-evaluator-item"
            wire:model.live="scoreForm.performance_form_template_item_id"
            :model="$this->formItemOptions()"></x-ui.select-dropdown>
        @error('scoreForm.performance_form_template_item_id') <x-validation>{{ $message }}</x-validation> @enderror

        <div>
            <x-label for="evaluator-score">{{ __('performance_evaluation::dashboard.fields.score') }}</x-label>
            <x-livewire-input mode="gray" id="evaluator-score" type="number" step="0.01" wire:model.defer="scoreForm.score" />
            @error('scoreForm.score') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div>
            <x-label for="evaluator-comment">{{ __('performance_evaluation::dashboard.fields.comment') }}</x-label>
            <textarea id="evaluator-comment" wire:model.defer="scoreForm.comment" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
            @error('scoreForm.comment') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <x-button mode="black" wire:click="saveAssignedScore">{{ __('performance_evaluation::dashboard.actions.save_score') }}</x-button>
    </div>
</x-surface-card>
