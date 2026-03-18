<div class="space-y-4">
    <x-surface-card :title="__('training_needs::dashboard.cards.recent_feedback_forms')" icon="icons.profile-icon">
        <div class="space-y-3">
            @forelse ($this->recentFeedbackForms as $form)
                <x-ui.list-card>
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-zinc-900">{{ $form->title }}</p>
                            <p class="text-xs text-zinc-500">{{ $form->session?->title ?? '—' }}</p>
                        </div>
                        <x-small-badge mode="sky">{{ __('training_needs::dashboard.feedback_statuses.'.$form->status) }}</x-small-badge>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.feedback_form_meta', ['count' => $form->responses_count, 'questions' => count($form->questions ?? [])]) }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <x-ui.action-pill mode="secondary" wire:click="relayEditFeedbackForm({{ $form->id }})" icon="icons.edit-icon">{{ __('training_needs::dashboard.actions.edit') }}</x-ui.action-pill>
                        <x-ui.action-pill mode="delete" wire:click="relayDeleteFeedbackForm({{ $form->id }})" icon="icons.delete-icon">{{ __('training_needs::dashboard.actions.delete') }}</x-ui.action-pill>
                    </div>
                </x-ui.list-card>
            @empty
                <x-ui.empty-state icon="icons.comment-icon" :message="__('training_needs::dashboard.empty.feedback_forms')" />
            @endforelse
        </div>
    </x-surface-card>

    <x-surface-card :title="__('training_needs::dashboard.cards.feedback_session_summary')" icon="icons.pending-icon">
        <div class="space-y-3">
            @forelse ($this->feedbackSessionSummaries as $summary)
                <x-ui.list-card>
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-zinc-900">{{ $summary->title }}</p>
                            <p class="text-xs text-zinc-500">{{ $summary->scheduled_start_at ? \Illuminate\Support\Carbon::parse($summary->scheduled_start_at)->format('d.m.Y H:i') : '---' }}</p>
                        </div>
                        <x-small-badge mode="sky">{{ __('training_needs::dashboard.session_statuses.'.$summary->status) }}</x-small-badge>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.feedback_session_meta', ['forms' => $summary->feedback_forms_count, 'responses' => $summary->feedback_responses_count, 'score' => $summary->average_feedback_score]) }}</p>
                </x-ui.list-card>
            @empty
                <x-ui.empty-state icon="icons.pending-icon" :message="__('training_needs::dashboard.empty.feedback_forms')" />
            @endforelse
        </div>
    </x-surface-card>

    <x-surface-card :title="__('training_needs::dashboard.cards.export_reports')" icon="icons.pending-icon">
        <div class="grid gap-3">
            <x-button mode="black" wire:click="exportDeliveryReport">{{ __('training_needs::dashboard.actions.export_delivery_report') }}</x-button>
            <x-button mode="success" wire:click="exportFeedbackReport">{{ __('training_needs::dashboard.actions.export_feedback_report') }}</x-button>
            <x-button mode="default" wire:click="exportDeliverySummaryReport">{{ __('training_needs::dashboard.actions.export_delivery_summary_report') }}</x-button>
            <x-button mode="default" wire:click="exportDeliveryPivotReport">{{ __('training_needs::dashboard.actions.export_delivery_pivot_report') }}</x-button>
            <x-button mode="default" wire:click="exportAuditReport">{{ __('training_needs::dashboard.actions.export_audit_report') }}</x-button>
            <a href="{{ route('training-needs.print-summary') }}" target="_blank" class="inline-flex min-w-max items-center justify-center rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-100">
                {{ __('training_needs::dashboard.actions.open_print_summary') }}
            </a>
            <p class="text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.export_report_hint') }}</p>
            <p class="text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.print_report_hint') }}</p>
        </div>
    </x-surface-card>
</div>
