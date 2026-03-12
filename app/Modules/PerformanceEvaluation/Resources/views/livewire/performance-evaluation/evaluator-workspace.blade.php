<div class="flex flex-col space-y-4 px-6 py-4">
    <x-surface-card :title="__('performance_evaluation::dashboard.cards.evaluator_workspace')" icon="icons.performance-icon">
        <div class="space-y-4">
            <p class="text-sm text-zinc-500">{{ __('performance_evaluation::dashboard.labels.evaluator_workspace_hint') }}</p>

            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-sky-700">{{ __('performance_evaluation::dashboard.labels.evaluator_summary_total') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-sky-900">{{ $this->assignedFormsSummary['total'] }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-amber-700">{{ __('performance_evaluation::dashboard.labels.evaluator_summary_pending') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $this->assignedFormsSummary['pending'] }}</p>
                </div>
                <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-violet-700">{{ __('performance_evaluation::dashboard.labels.evaluator_summary_reviews') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-violet-900">{{ $this->assignedFormsSummary['reviews'] }}</p>
                </div>
            </div>
        </div>
    </x-surface-card>

    <div class="grid gap-4 2xl:grid-cols-[360px_minmax(0,1fr)_minmax(340px,0.95fr)]">
        <div class="space-y-4">
            <x-surface-card :title="__('performance_evaluation::dashboard.cards.assigned_forms')" icon="icons.profile-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="space-y-4">
                    <div class="rounded-3xl border border-zinc-200 bg-gradient-to-b from-zinc-50 to-white p-4">
                        <div class="space-y-4">
                            <div>
                                <x-label for="assigned-form-search">{{ __('performance_evaluation::dashboard.fields.search') }}</x-label>
                                <x-livewire-input mode="gray" id="assigned-form-search" wire:model.live.debounce.300ms="searchAssignedForms" />
                            </div>
                            <div>
                                <x-label for="assigned-role-filter">{{ __('performance_evaluation::dashboard.fields.role_filter') }}</x-label>
                                <select id="assigned-role-filter" wire:model.live="assignedRoleFilter" class="mt-2 h-11 w-full rounded-xl border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                                    <option value="all">{{ __('performance_evaluation::dashboard.labels.all_roles') }}</option>
                                    <option value="manager">{{ __('performance_evaluation::dashboard.labels.only_manager_assignments') }}</option>
                                    <option value="hr">{{ __('performance_evaluation::dashboard.labels.only_hr_assignments') }}</option>
                                </select>
                            </div>
                            <div>
                                <x-label for="assigned-status-filter">{{ __('performance_evaluation::dashboard.fields.status_filter') }}</x-label>
                                <select id="assigned-status-filter" wire:model.live="assignedStatusFilter" class="mt-2 h-11 w-full rounded-xl border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                                    <option value="all">{{ __('performance_evaluation::dashboard.labels.all_statuses') }}</option>
                                    <option value="pending">{{ __('performance_evaluation::dashboard.labels.pending_only') }}</option>
                                    <option value="submitted">{{ __('performance_evaluation::dashboard.labels.submitted_only') }}</option>
                                </select>
                            </div>
                            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('performance_evaluation::dashboard.labels.visible_records') }}</p>
                                <p class="mt-2 text-lg font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.labels.forms_count_value', ['count' => $this->assignedForms->count()]) }}</p>
                            </div>
                            <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-4 py-3 text-xs leading-6 text-zinc-500">
                                {{ __('performance_evaluation::dashboard.labels.assigned_filter_hint') }}
                            </div>
                        </div>
                    </div>

                    @forelse ($this->assignedForms as $form)
                        <x-ui.list-card tone="{{ $form->final_category === 'weak' ? 'red' : ($form->final_category === 'high' ? 'green' : 'default') }}">
                            <div class="space-y-4">
                                <div class="space-y-1">
                                    <p class="text-lg font-semibold leading-8 text-zinc-900 break-words">{{ $form->personnel_fullname ?: '-' }}</p>
                                    <p class="text-sm text-zinc-500 break-words">{{ $form->cycle_name ?: '-' }} • {{ $form->template_name ?: $form->template_code ?: '-' }}</p>
                                    <p class="text-sm text-zinc-500">{{ __('performance_evaluation::dashboard.fields.manager') }}: {{ $form->manager_name ?? '-' }}</p>
                                    <p class="text-sm text-zinc-500">{{ __('performance_evaluation::dashboard.fields.hr_reviewer') }}: {{ $form->hr_reviewer_name ?? '-' }}</p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.labels.current_role', ['role' => $form->manager_id === auth()->id() ? __('performance_evaluation::dashboard.evaluators.manager') : __('performance_evaluation::dashboard.evaluators.hr')]) }}</x-small-badge>
                                    @php($progress = data_get($this->assignedFormProgress, $form->id))
                                    @if ($progress)
                                        <x-small-badge :mode="$progress['remaining'] > 0 ? 'amber' : 'green'">
                                            {{ $progress['remaining'] > 0
                                                ? __('performance_evaluation::dashboard.labels.criteria_remaining', ['count' => $progress['remaining']])
                                                : __('performance_evaluation::dashboard.labels.all_criteria_scored') }}
                                        </x-small-badge>
                                    @endif
                                    @if ($form->final_category)
                                        <x-small-badge :mode="$form->final_category === 'weak' ? 'red' : ($form->final_category === 'high' ? 'green' : 'amber')">
                                            {{ __('performance_evaluation::dashboard.labels.final_category') }}: {{ __('performance_evaluation::dashboard.categories.'.$form->final_category) }}
                                        </x-small-badge>
                                    @endif
                                </div>

                                <div class="border-t border-zinc-200/80 pt-3">
                                    <x-ui.action-pill wire:click="openScoreCapture({{ $form->id }})" icon="icons.edit-icon">{{ __('performance_evaluation::dashboard.actions.open_score_form') }}</x-ui.action-pill>
                                </div>
                            </div>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.profile-icon" :message="__('performance_evaluation::dashboard.empty.assigned_forms')" />
                    @endforelse
                </div>
            </x-surface-card>
        </div>

        <livewire:performance-evaluation.evaluator-score-capture :form-catalog="$this->scoreCaptureFormCatalog" />

        <div class="space-y-4">
            <x-surface-card :title="__('performance_evaluation::dashboard.cards.assigned_reviews')" icon="icons.comment-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="space-y-4">
                    <div class="rounded-3xl border border-zinc-200 bg-gradient-to-b from-zinc-50 to-white p-4">
                        <div class="space-y-4">
                            <div>
                                <x-label for="pending-answer-search">{{ __('performance_evaluation::dashboard.fields.search') }}</x-label>
                                <x-livewire-input mode="gray" id="pending-answer-search" wire:model.live.debounce.300ms="searchPendingAnswers" />
                            </div>
                            <div>
                                <x-label for="pending-question-type">{{ __('performance_evaluation::dashboard.fields.question_type_filter') }}</x-label>
                                <select id="pending-question-type" wire:model.live="pendingQuestionTypeFilter" class="mt-2 h-11 w-full rounded-xl border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                                    <option value="all">{{ __('performance_evaluation::dashboard.labels.all_question_types') }}</option>
                                    <option value="multiple_choice">{{ __('performance_evaluation::dashboard.question_types.multiple_choice') }}</option>
                                    <option value="open_answer">{{ __('performance_evaluation::dashboard.question_types.open_answer') }}</option>
                                    <option value="case_study">{{ __('performance_evaluation::dashboard.question_types.case_study') }}</option>
                                    <option value="behavioral">{{ __('performance_evaluation::dashboard.question_types.behavioral') }}</option>
                                </select>
                            </div>
                            <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-4 py-3 text-xs leading-6 text-zinc-500">
                                {{ __('performance_evaluation::dashboard.labels.review_queue_hint') }}
                            </div>
                        </div>
                    </div>

                    @forelse ($this->pendingAnswers as $answer)
                        <x-ui.list-card tone="amber">
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-zinc-900 break-words">{{ $answer->personnel_fullname ?: '-' }}</p>
                                <p class="text-xs text-zinc-500 break-words">{{ $answer->bank_name ?? '-' }}</p>
                                <p class="text-xs text-zinc-500 break-words">{{ \Illuminate\Support\Str::limit((string) $answer->question_prompt, 120) }}</p>
                                <x-ui.action-pill wire:click="startReviewAnswer({{ $answer->id }})" icon="icons.edit-icon">{{ __('performance_evaluation::dashboard.actions.open_review_form') }}</x-ui.action-pill>
                            </div>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.comment-icon" :message="__('performance_evaluation::dashboard.empty.pending_review_answers')" />
                    @endforelse
                </div>
            </x-surface-card>

            <x-surface-card :title="__('performance_evaluation::dashboard.cards.open_answer_review')" icon="icons.comment-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 content-start">
                    <div class="rounded-3xl border border-zinc-200 bg-gradient-to-br from-white to-amber-50 px-4 py-3">
                        <p class="text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.review_queue_hint') }}</p>
                    </div>

                    <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.answer')" placeholder="---" mode="gray" class="w-full" instance="perf-evaluator-answer"
                        wire:model.live="reviewForm.performance_test_attempt_answer_id"
                        :model="$this->pendingAnswers->map(fn ($answer) => ['id' => $answer->id, 'label' => '#' . $answer->attempt_id . ' / ' . \Illuminate\Support\Str::limit((string) $answer->question_prompt, 50)])->values()->all()"></x-ui.select-dropdown>
                    @error('reviewForm.performance_test_attempt_answer_id') <x-validation>{{ $message }}</x-validation> @enderror

                    <div>
                        <x-label for="assigned-review-score">{{ __('performance_evaluation::dashboard.fields.review_score') }}</x-label>
                        <x-livewire-input mode="gray" id="assigned-review-score" type="number" step="0.01" wire:model.defer="reviewForm.score" />
                        @error('reviewForm.score') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>

                    <div>
                        <x-label for="assigned-review-feedback">{{ __('performance_evaluation::dashboard.fields.feedback') }}</x-label>
                        <textarea id="assigned-review-feedback" wire:model.defer="reviewForm.feedback" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('reviewForm.feedback') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>

                    <x-button mode="black" wire:click="saveAnswerReview">{{ __('performance_evaluation::dashboard.actions.review_answer') }}</x-button>
                </div>
            </x-surface-card>
        </div>
    </div>
</div>
