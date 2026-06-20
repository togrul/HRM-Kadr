    @if ($activeTab === 'planning')
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
            <x-surface-card :title="__('training_needs::dashboard.cards.annual_planning_board')" icon="icons.training-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                @if ($editingPlanId)
                    <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        {{ __('training_needs::dashboard.labels.editing_plan_hint') }}
                    </div>
                @endif
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-label for="plan-title">{{ __('training_needs::dashboard.fields.plan_title') }}</x-label>
                        <x-livewire-input mode="gray" id="plan-title" wire:model.defer="planForm.title" />
                        @error('planForm.title') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="plan-year">{{ __('training_needs::dashboard.fields.plan_year') }}</x-label>
                        <x-livewire-input mode="gray" id="plan-year" type="number" wire:model.defer="planForm.plan_year" />
                        @error('planForm.plan_year') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="plan-quarter">{{ __('training_needs::dashboard.fields.plan_quarter') }}</x-label>
                        <select id="plan-quarter" wire:model.defer="planForm.plan_quarter" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="">{{ __('training_needs::dashboard.labels.all_year') }}</option>
                            <option value="1">Q1</option>
                            <option value="2">Q2</option>
                            <option value="3">Q3</option>
                            <option value="4">Q4</option>
                        </select>
                        @error('planForm.plan_quarter') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="plan-status">{{ __('training_needs::dashboard.fields.status') }}</x-label>
                        <select id="plan-status" wire:model.defer="planForm.status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="draft">{{ __('training_needs::dashboard.plan_statuses.draft') }}</option>
                            <option value="review">{{ __('training_needs::dashboard.plan_statuses.review') }}</option>
                            <option value="approved">{{ __('training_needs::dashboard.plan_statuses.approved') }}</option>
                            <option value="published">{{ __('training_needs::dashboard.plan_statuses.published') }}</option>
                        </select>
                        @error('planForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="mt-6 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="planForm.auto_generate" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('training_needs::dashboard.fields.auto_generate_plan_items') }}
                    </label>
                    <div class="md:col-span-2">
                        <x-label for="plan-notes">{{ __('training_needs::dashboard.fields.notes') }}</x-label>
                        <textarea id="plan-notes" wire:model.defer="planForm.notes" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('planForm.notes') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex flex-wrap gap-2">
                            <x-button mode="black" wire:click="storePlan">
                                {{ $editingPlanId ? __('training_needs::dashboard.actions.update_plan') : __('training_needs::dashboard.actions.save_plan') }}
                            </x-button>
                            @if ($editingPlanId)
                                <x-button mode="secondary" wire:click="cancelPlanEdit">{{ __('training_needs::dashboard.actions.cancel_edit') }}</x-button>
                            @endif
                        </div>
                        <p class="mt-3 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.plan_status_hint') }}</p>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.recent_plans')" icon="icons.pending-icon">
                <div class="space-y-3">
                    @forelse ($this->recentPlans as $plan)
                        <x-ui.list-card>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $plan->title }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.plan_scope', ['year' => $plan->plan_year, 'quarter' => $plan->plan_quarter ? 'Q'.$plan->plan_quarter : __('training_needs::dashboard.labels.all_year')]) }}</p>
                                </div>
                                <x-small-badge mode="sky">{{ __('training_needs::dashboard.plan_statuses.'.$plan->status) }}</x-small-badge>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.plan_summary', ['count' => $plan->items_count, 'participants' => $plan->planned_participants, 'budget' => number_format((float) $plan->estimated_budget, 2)]) }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <x-ui.action-pill mode="secondary" wire:click="editPlan({{ $plan->id }})" icon="icons.edit-icon">{{ __('training_needs::dashboard.actions.edit') }}</x-ui.action-pill>
                                <x-ui.action-pill mode="delete" wire:click="confirmDeletePlan({{ $plan->id }})" icon="icons.delete-icon">{{ __('training_needs::dashboard.actions.delete') }}</x-ui.action-pill>
                            </div>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.training-icon" :message="__('training_needs::dashboard.empty.recent_plans')" />
                    @endforelse
                </div>
            </x-surface-card>
        </div>

        <x-surface-card :title="__('training_needs::dashboard.cards.suggested_plan_board')" icon="icons.performance-icon">
            <div class="grid gap-3 xl:grid-cols-2">
                @forelse ($this->suggestedPlanGroups as $suggestion)
                    <x-ui.list-card tone="violet">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-zinc-900">{{ $suggestion['training_program_title'] ?? __('training_needs::dashboard.labels.no_program') }}</p>
                                    <x-small-badge mode="sky">{{ __('training_needs::dashboard.labels.system_suggested') }}</x-small-badge>
                                </div>
                                <p class="mt-1 text-sm text-zinc-600">{{ $suggestion['training_competency_name'] ?? __('training_needs::dashboard.labels.no_competency') }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $suggestion['position_name'] ?? __('training_needs::dashboard.labels.no_position') }} • {{ __('training_needs::dashboard.priorities.'.$suggestion['priority']) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-small-badge mode="green">{{ __('training_needs::dashboard.labels.suggested_score', ['score' => number_format((float) $suggestion['suggested_score'], 1)]) }}</x-small-badge>
                                <x-small-badge mode="blue">{{ __('training_needs::dashboard.labels.participant_count', ['count' => $suggestion['participant_count']]) }}</x-small-badge>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($suggestion['suggested_reasons'] as $reason)
                                <x-small-badge :mode="in_array($reason, ['mandatory', 'overdue'], true) ? 'red' : (in_array($reason, ['high_gap', 'role_critical'], true) ? 'green' : 'secondary')">
                                    {{ __('training_needs::dashboard.suggestion_reasons.'.$reason) }}
                                </x-small-badge>
                            @endforeach
                        </div>
                        <p class="mt-3 text-xs text-zinc-500">
                            {{ __('training_needs::dashboard.labels.suggestion_meta', [
                                'needs' => $suggestion['need_count'],
                                'budget' => number_format((float) $suggestion['estimated_budget'], 2),
                                'level' => $suggestion['target_level_name'] ?? __('training_needs::dashboard.labels.no_target_level'),
                                'sources' => $suggestion['source_mix'] ?: __('training_needs::dashboard.sources.manual'),
                                'date' => $suggestion['latest_due_date'] ? \Illuminate\Support\Carbon::parse($suggestion['latest_due_date'])->format('d.m.Y') : __('training_needs::dashboard.labels.no_date'),
                            ]) }}
                        </p>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.performance-icon" :message="__('training_needs::dashboard.empty.suggested_plan_groups')" />
                @endforelse
            </div>
        </x-surface-card>

        @if ($this->selectedPlanItem)
            <x-surface-card :title="__('training_needs::dashboard.cards.plan_item_review')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-zinc-900">{{ $this->selectedPlanItem->program?->title ?? __('training_needs::dashboard.labels.no_program') }}</p>
                                <x-small-badge :mode="$this->selectedPlanItem->review_status === 'approved' ? 'green' : ($this->selectedPlanItem->review_status === 'hr_adjusted' ? 'sky' : 'secondary')">
                                    {{ __('training_needs::dashboard.review_statuses.'.$this->selectedPlanItem->review_status) }}
                                </x-small-badge>
                            </div>
                            <p class="mt-1 text-sm text-zinc-600">{{ $this->selectedPlanItem->competency?->name ?? __('training_needs::dashboard.labels.no_competency') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $this->selectedPlanItem->position?->name ?? __('training_needs::dashboard.labels.no_position') }} • {{ $this->selectedPlanItem->plan?->title }}</p>
                        </div>
                    </div>
                    <div>
                        <x-label for="review-participant-count">{{ __('training_needs::dashboard.fields.participant_count') }}</x-label>
                        <x-livewire-input mode="gray" id="review-participant-count" type="number" wire:model.defer="planItemReviewForm.participant_count" />
                        @error('planItemReviewForm.participant_count') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="review-budget">{{ __('training_needs::dashboard.fields.planned_budget') }}</x-label>
                        <x-livewire-input mode="gray" id="review-budget" type="number" step="0.01" wire:model.defer="planItemReviewForm.estimated_budget" />
                        @error('planItemReviewForm.estimated_budget') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="review-priority">{{ __('training_needs::dashboard.fields.priority') }}</x-label>
                        <select id="review-priority" wire:model.defer="planItemReviewForm.priority" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="low">{{ __('training_needs::dashboard.priorities.low') }}</option>
                            <option value="medium">{{ __('training_needs::dashboard.priorities.medium') }}</option>
                            <option value="high">{{ __('training_needs::dashboard.priorities.high') }}</option>
                        </select>
                        @error('planItemReviewForm.priority') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="review-note">{{ __('training_needs::dashboard.fields.review_note') }}</x-label>
                        <textarea id="review-note" wire:model.defer="planItemReviewForm.review_note" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('planItemReviewForm.review_note') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2 flex flex-wrap gap-2">
                        <x-button mode="default" wire:click="cancelPlanItemReview">{{ __('training_needs::dashboard.actions.cancel_review') }}</x-button>
                        <x-button mode="light-blue" wire:click="savePlanItemReview('hr_adjusted')">{{ __('training_needs::dashboard.actions.mark_hr_adjusted') }}</x-button>
                        <x-button mode="black" wire:click="savePlanItemReview('approved')">{{ __('training_needs::dashboard.actions.approve_plan_item') }}</x-button>
                    </div>
                </div>
            </x-surface-card>
        @endif

        <x-surface-card :title="__('training_needs::dashboard.cards.plan_items_board')" icon="icons.profile-outline-icon">
            <div class="space-y-3">
                @forelse ($this->recentPlanItems as $item)
                    <x-ui.list-card :tone="$item->review_status === 'approved' ? 'emerald' : ($item->review_status === 'hr_adjusted' ? 'sky' : 'neutral')">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-zinc-900">{{ $item->program?->title ?? __('training_needs::dashboard.labels.no_program') }}</p>
                                <p class="text-sm text-zinc-600">{{ $item->competency?->name ?? __('training_needs::dashboard.labels.no_competency') }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $item->position?->name ?? __('training_needs::dashboard.labels.no_position') }} • {{ $item->plan?->title }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-small-badge :mode="$item->review_status === 'approved' ? 'green' : ($item->review_status === 'hr_adjusted' ? 'sky' : 'secondary')">{{ __('training_needs::dashboard.review_statuses.'.$item->review_status) }}</x-small-badge>
                                <x-small-badge mode="green">{{ __('training_needs::dashboard.labels.participant_count', ['count' => $item->participant_count]) }}</x-small-badge>
                                <x-small-badge mode="blue">{{ __('training_needs::dashboard.labels.need_count', ['count' => $item->need_count]) }}</x-small-badge>
                                <x-small-badge :mode="$item->priority === 'high' ? 'red' : ($item->priority === 'medium' ? 'green' : 'secondary')">{{ __('training_needs::dashboard.priorities.'.$item->priority) }}</x-small-badge>
                                <x-small-badge mode="sky">{{ __('training_needs::dashboard.labels.suggested_score', ['score' => number_format((float) $item->suggested_score, 1)]) }}</x-small-badge>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.plan_item_meta', ['level' => $item->targetLevel?->name ?? __('training_needs::dashboard.labels.no_target_level'), 'budget' => number_format((float) $item->estimated_budget, 2), 'sources' => $item->source_mix ?: __('training_needs::dashboard.sources.manual')]) }}</p>
                        @if ($item->review_note)
                            <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.review_note_meta', ['note' => $item->review_note]) }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-button mode="black" wire:click="selectPlanItemForReview({{ $item->id }})">{{ __('training_needs::dashboard.actions.review_plan_item') }}</x-button>
                            @if ($item->review_status !== 'approved')
                                <x-button mode="black" wire:click="selectPlanItemForReview({{ $item->id }})">{{ __('training_needs::dashboard.actions.open_review') }}</x-button>
                            @endif
                        </div>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.profile-outline-icon" :message="__('training_needs::dashboard.empty.plan_items')" />
                @endforelse
            </div>
        </x-surface-card>
    @endif

