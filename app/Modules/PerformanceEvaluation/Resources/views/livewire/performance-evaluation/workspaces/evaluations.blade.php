    @if ($activeTab === 'evaluations')
        @php
            $d = 'performance_evaluation::dashboard';
            $hint = 'rounded-xl bg-[#fafafa] px-3 py-2 text-[12px] leading-5 text-ink-muted';
            $textarea = 'w-full rounded-xl border border-hairline bg-[#fafafa] px-3 py-2 text-[13px] text-ink focus:border-zinc-400 focus:bg-white focus:outline-none focus:ring-0';
            $canManage = auth()->user()?->can('manage-performance-evaluation');
        @endphp

        <div class="mx-auto flex max-w-6xl flex-col gap-4">
            {{-- ───────────── toolbar ───────────── --}}
            <div class="flex flex-col gap-3 rounded-2xl border border-hairline bg-white px-5 py-4 shadow-card sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[15px] font-semibold tracking-[-0.01em] text-ink">{{ __($d.'.tabs.evaluations') }}</p>
                    <p class="mt-0.5 max-w-2xl text-[12.5px] leading-5 text-ink-muted">{{ __($d.'.labels.evaluation_assignment_hint') }}</p>
                </div>
                @if ($canManage)
                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <button type="button" wire:click="openScoreForm" class="inline-flex h-10 items-center gap-2 rounded-xl border border-hairline bg-white px-4 text-[14px] font-semibold text-ink-soft transition hover:border-zinc-300 hover:text-ink">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            {{ __($d.'.cards.score_capture') }}
                        </button>
                        <button type="button" wire:click="openAssignForm" class="inline-flex h-10 items-center gap-2 rounded-xl bg-ink px-4 text-[14px] font-semibold text-white transition hover:bg-ink-hover">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                            {{ __($d.'.panel.assign_form') }}
                        </button>
                    </div>
                @endif
            </div>

            {{-- ───────────── assigned forms ───────────── --}}
            <livewire:performance-evaluation.evaluations-summary :key="'performance-evaluation-evaluations-summary-'.$evaluationsSummaryVersion" lazy />
        </div>

        {{-- ───────────── editors ───────────── --}}
        @if ($canManage)
            <x-side-modal size="large">
                @if ($showSideMenu === 'form-assign')
                    <div class="flex h-full flex-col">
                        <p class="hrm-eyebrow">{{ __($d.'.tabs.evaluations') }}</p>
                        <h2 class="mt-1 text-[18px] font-semibold tracking-[-0.02em] text-ink">
                            {{ $editingEvaluationFormId ? __($d.'.labels.editing') : __($d.'.cards.evaluation_assignment') }}
                        </h2>
                        <p class="{{ $hint }} mt-3">{{ __($d.'.labels.evaluation_assignment_hint') }}</p>

                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <x-ui.select-dropdown :label="__($d.'.fields.cycle')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-cycle" direction="auto"
                                    wire:model.live="evaluationForm.performance_cycle_id" :model="$this->cycleOptions()" search-model="searchCycle"></x-ui.select-dropdown>
                                @error('evaluationForm.performance_cycle_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <x-ui.select-dropdown :label="__($d.'.fields.template')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-template" direction="auto"
                                    wire:model.live="evaluationForm.performance_form_template_id" :model="$this->templateOptions()" search-model="searchTemplate"></x-ui.select-dropdown>
                                @error('evaluationForm.performance_form_template_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <x-ui.select-dropdown :label="__($d.'.fields.personnel')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-personnel" direction="auto"
                                    wire:model.live="evaluationForm.personnel_id" :model="$this->personnelOptions()" search-model="searchPersonnel"></x-ui.select-dropdown>
                                @error('evaluationForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-ui.select-dropdown :label="__($d.'.fields.manager')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-manager" direction="auto"
                                    wire:model.live="evaluationForm.manager_id" :model="$this->evaluatorOptions('searchManager', 'manager_id')" search-model="searchManager"></x-ui.select-dropdown>
                                @error('evaluationForm.manager_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-ui.select-dropdown :label="__($d.'.fields.hr_reviewer')" placeholder="---" mode="gray" class="w-full" instance="perf-eval-hr" direction="auto"
                                    wire:model.live="evaluationForm.hr_reviewer_id" :model="$this->evaluatorOptions('searchHrReviewer', 'hr_reviewer_id')" search-model="searchHrReviewer"></x-ui.select-dropdown>
                                @error('evaluationForm.hr_reviewer_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                        </div>

                        <div class="mt-auto flex items-center justify-end gap-2.5 border-t border-hairline-subtle pt-5">
                            <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-hairline px-5 text-sm font-medium text-ink-soft hover:bg-[#fafafa]">{{ __($d.'.actions.cancel_edit') }}</button>
                            <button type="button" wire:click="saveAssignment" class="h-11 rounded-xl bg-ink px-6 text-sm font-semibold text-white hover:bg-ink-hover">{{ __($d.'.actions.save_evaluation') }}</button>
                        </div>
                    </div>
                @endif

                @if ($showSideMenu === 'form-score')
                    <div class="flex h-full flex-col">
                        <p class="hrm-eyebrow">{{ __($d.'.tabs.evaluations') }}</p>
                        <h2 class="mt-1 text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ __($d.'.cards.score_capture') }}</h2>
                        <p class="{{ $hint }} mt-3">{{ __($d.'.labels.score_capture_hint') }}</p>

                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <x-ui.select-dropdown :label="__($d.'.fields.evaluation_form')" placeholder="---" mode="gray" class="w-full" instance="perf-score-form" direction="auto"
                                    wire:model.live="scoreForm.performance_form_id" :model="$this->performanceFormOptions()" search-model="searchPerformanceForm"></x-ui.select-dropdown>
                                @error('scoreForm.performance_form_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <x-ui.select-dropdown :label="__($d.'.fields.item')" placeholder="---" mode="gray" class="w-full" instance="perf-score-item" direction="auto"
                                    wire:model.live="scoreForm.performance_form_template_item_id" :model="$this->templateItemOptions()" search-model="searchTemplateItem"></x-ui.select-dropdown>
                                @error('scoreForm.performance_form_template_item_id') <x-validation>{{ $message }}</x-validation> @enderror
                                @if ($this->selectedScoreItem && blank($this->selectedScoreItem->training_competency_id))
                                    <x-validation>{{ __($d.'.validation.item_without_competency') }}</x-validation>
                                @endif
                            </div>
                            <div>
                                <x-ui.select-dropdown :label="__($d.'.fields.evaluator_type')" placeholder="---" mode="gray" class="w-full" instance="perf-score-evaluator-type" direction="auto"
                                    wire:model.live="scoreForm.evaluator_type"
                                    :model="collect(['self', 'manager', 'hr'])->map(fn ($item) => ['id' => $item, 'label' => __($d.'.evaluators.'.$item)])->values()->all()"></x-ui.select-dropdown>
                                @error('scoreForm.evaluator_type') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <x-label for="score-value">{{ __($d.'.fields.score') }}</x-label>
                                <x-livewire-input mode="gray" id="score-value" type="number" step="0.01" min="0" max="100" wire:model="scoreForm.score" />
                                @error('scoreForm.score') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="md:col-span-2">
                                <x-label for="score-comment">{{ __($d.'.fields.comment') }}</x-label>
                                <textarea id="score-comment" wire:model="scoreForm.comment" rows="3" class="{{ $textarea }}"></textarea>
                                @error('scoreForm.comment') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                        </div>

                        <div class="mt-auto flex items-center justify-end gap-2.5 border-t border-hairline-subtle pt-5">
                            <button type="button" wire:click="closeSideMenu" class="h-11 rounded-xl border border-hairline px-5 text-sm font-medium text-ink-soft hover:bg-[#fafafa]">{{ __($d.'.actions.cancel_edit') }}</button>
                            <button type="button" wire:click="saveScore" class="h-11 rounded-xl bg-ink px-6 text-sm font-semibold text-white hover:bg-ink-hover">{{ __($d.'.actions.save_score') }}</button>
                        </div>
                    </div>
                @endif
            </x-side-modal>
        @endif
    @endif
