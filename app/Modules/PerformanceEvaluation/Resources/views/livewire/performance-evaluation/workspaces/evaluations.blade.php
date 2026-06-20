    @if ($activeTab === 'evaluations')
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
            <div class="space-y-4">
                <x-surface-card :title="__('performance_evaluation::dashboard.cards.evaluation_assignment')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="md:col-span-2 rounded-2xl border border-zinc-200 bg-zinc-50/90 px-4 py-3 text-xs leading-6 text-zinc-500">
                            {{ __('performance_evaluation::dashboard.labels.evaluation_assignment_hint') }}
                        </div>
                        @if ($editingEvaluationFormId)
                            <div class="md:col-span-2">
                                <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.labels.editing') }}</x-small-badge>
                            </div>
                        @endif
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.cycle')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-cycle"
                                direction="up"
                                wire:model.live="evaluationForm.performance_cycle_id" :model="$this->cycleOptions()" search-model="searchCycle"></x-ui.select-dropdown>
                            @error('evaluationForm.performance_cycle_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.template')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-template"
                                direction="up"
                                wire:model.live="evaluationForm.performance_form_template_id" :model="$this->templateOptions()" search-model="searchTemplate"></x-ui.select-dropdown>
                            @error('evaluationForm.performance_form_template_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.personnel')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-personnel"
                                direction="up"
                                wire:model.live="evaluationForm.personnel_id" :model="$this->personnelOptions()" search-model="searchPersonnel"></x-ui.select-dropdown>
                            @error('evaluationForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.manager')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-manager"
                                direction="up"
                                wire:model.live="evaluationForm.manager_id" :model="$this->evaluatorOptions('searchManager', 'manager_id')" search-model="searchManager"></x-ui.select-dropdown>
                            @error('evaluationForm.manager_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.hr_reviewer')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-hr"
                                direction="up"
                                wire:model.live="evaluationForm.hr_reviewer_id" :model="$this->evaluatorOptions('searchHrReviewer', 'hr_reviewer_id')" search-model="searchHrReviewer"></x-ui.select-dropdown>
                            @error('evaluationForm.hr_reviewer_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-button mode="black" wire:click="storeEvaluationForm">{{ __('performance_evaluation::dashboard.actions.save_evaluation') }}</x-button>
                                @if ($editingEvaluationFormId)
                                    <x-button mode="secondary" wire:click="cancelEvaluationEdit">{{ __('performance_evaluation::dashboard.actions.cancel_edit') }}</x-button>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-surface-card>

                <x-surface-card :title="__('performance_evaluation::dashboard.cards.score_capture')" icon="icons.pending-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div class="md:col-span-2 rounded-2xl border border-zinc-200 bg-zinc-50/90 px-4 py-3 text-xs leading-6 text-zinc-500">
                            {{ __('performance_evaluation::dashboard.labels.score_capture_hint') }}
                        </div>
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.evaluation_form')" placeholder="---" mode="gray" class="w-full" instance="perf-score-form"
                                direction="up"
                                wire:model.live="scoreForm.performance_form_id" :model="$this->performanceFormOptions()" search-model="searchPerformanceForm"></x-ui.select-dropdown>
                            @error('scoreForm.performance_form_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.item')" placeholder="---" mode="gray" class="w-full" instance="perf-score-item"
                                direction="up"
                                wire:model.live="scoreForm.performance_form_template_item_id" :model="$this->templateItemOptions()" search-model="searchTemplateItem"></x-ui.select-dropdown>
                            @error('scoreForm.performance_form_template_item_id') <x-validation>{{ $message }}</x-validation> @enderror
                            @if ($this->selectedScoreItem && blank($this->selectedScoreItem->training_competency_id))
                                <x-validation>{{ __('performance_evaluation::dashboard.validation.item_without_competency') }}</x-validation>
                            @endif
                        </div>
                        <div>
                            <x-ui.select-dropdown :label="__('performance_evaluation::dashboard.fields.evaluator_type')" placeholder="---" mode="gray" class="w-full" instance="perf-score-evaluator-type" wire:model.live="scoreForm.evaluator_type"
                                direction="up"
                                :model="collect(['self','manager','hr'])->map(fn ($item) => ['id' => $item, 'label' => __('performance_evaluation::dashboard.evaluators.'.$item)])->values()->all()"></x-ui.select-dropdown>
                            @error('scoreForm.evaluator_type') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-label for="score-value">{{ __('performance_evaluation::dashboard.fields.score') }}</x-label>
                            <x-livewire-input mode="gray" id="score-value" type="number" step="0.01" wire:model.defer="scoreForm.score" />
                            @error('scoreForm.score') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-label for="score-comment">{{ __('performance_evaluation::dashboard.fields.comment') }}</x-label>
                            <textarea id="score-comment" wire:model.defer="scoreForm.comment" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                            @error('scoreForm.comment') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <x-button mode="black" wire:click="storeScore">{{ __('performance_evaluation::dashboard.actions.save_score') }}</x-button>
                        </div>
                    </div>
                </x-surface-card>
            </div>

            <livewire:performance-evaluation.evaluations-summary :key="'performance-evaluation-evaluations-summary-'.$evaluationsSummaryVersion" lazy />
        </div>
    @endif

