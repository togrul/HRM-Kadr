<x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_forms')" icon="icons.profile-icon">
    <div class="space-y-3">
        @forelse ($this->recentForms as $form)
            <x-ui.list-card>
                <div class="space-y-4">
                    <div class="min-w-0 space-y-1">
                        <p class="text-sm font-semibold leading-6 text-zinc-900">{{ $form->personnel_fullname ?? '-' }}</p>
                        <p class="text-xs leading-5 text-zinc-500">{{ $form->cycle_name }} • {{ $form->template_name }}</p>
                        <p class="text-xs leading-5 text-zinc-500">{{ __('performance_evaluation::dashboard.evaluators.manager') }}: {{ $form->manager_name ?? __('performance_evaluation::dashboard.labels.no_manager') }}</p>
                        <p class="text-xs leading-5 text-zinc-500">{{ __('performance_evaluation::dashboard.evaluators.hr') }}: {{ $form->hr_reviewer_name ?? __('performance_evaluation::dashboard.labels.no_hr_reviewer') }}</p>
                    </div>
                    <div class="space-y-2">
                        <div class="flex flex-wrap gap-2">
                            <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.evaluators.self') }}: {{ $form->self_status === 'submitted' ? __('performance_evaluation::dashboard.statuses.submitted') : __('performance_evaluation::dashboard.statuses.draft') }}</x-small-badge>
                            <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.evaluators.manager') }}: {{ $form->manager_status === 'submitted' ? __('performance_evaluation::dashboard.statuses.submitted') : __('performance_evaluation::dashboard.statuses.draft') }}</x-small-badge>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.evaluators.hr') }}: {{ $form->hr_status === 'submitted' ? __('performance_evaluation::dashboard.statuses.submitted') : __('performance_evaluation::dashboard.statuses.draft') }}</x-small-badge>
                            @if ($form->final_category)
                                <x-small-badge :mode="$form->final_category === 'weak' ? 'red' : ($form->final_category === 'high' ? 'green' : 'amber')">
                                    {{ __('performance_evaluation::dashboard.labels.final_category') }}: {{ __('performance_evaluation::dashboard.categories.'.$form->final_category) }}
                                </x-small-badge>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 border-t border-zinc-200/80 pt-3">
                        <x-small-badge mode="amber">{{ $form->final_score ?? '—' }}</x-small-badge>
                    </div>
                    <div class="flex flex-wrap gap-2 border-t border-zinc-200/80 pt-3">
                        <x-ui.action-pill class="self-start" wire:click="relayEditEvaluationForm({{ $form->id }})" icon="icons.edit-icon">{{ __('performance_evaluation::dashboard.actions.edit') }}</x-ui.action-pill>
                        <x-ui.action-pill class="self-start" mode="delete" wire:click="relayDeleteEvaluationForm({{ $form->id }})" icon="icons.delete-icon">{{ __('performance_evaluation::dashboard.actions.delete') }}</x-ui.action-pill>
                    </div>
                </div>
            </x-ui.list-card>
        @empty
            <x-ui.empty-state icon="icons.profile-icon" :message="__('performance_evaluation::dashboard.empty.recent_forms')" />
        @endforelse
    </div>
</x-surface-card>
