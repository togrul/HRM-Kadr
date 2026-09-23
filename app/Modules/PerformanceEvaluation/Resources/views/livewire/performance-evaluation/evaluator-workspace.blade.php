@php
    $d = 'performance_evaluation::dashboard';
    $section = 'overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    $sectionHead = 'flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5';
    $select = 'h-10 w-full rounded-xl border border-hairline bg-[#fafafa] px-3 text-[13px] text-ink focus:border-zinc-400 focus:bg-white focus:outline-none focus:ring-0';
    $categoryPill = [
        'high' => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'medium' => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
        'weak' => ['bg-rose-50 text-rose-700', 'bg-rose-500'],
    ];
@endphp

<div class="mx-auto flex max-w-shell flex-col gap-4 px-4 py-4 sm:px-6">
    <div class="flex items-center justify-between gap-3">
        <x-pill-button :href="$this->backUrl">
            <span aria-hidden="true">←</span>
            <span>{{ __($d.'.actions.back_to_performance_dashboard') }}</span>
        </x-pill-button>
    </div>

    {{-- ───────────── summary ───────────── --}}
    <div class="{{ $section }}">
        <div class="{{ $sectionHead }}">
            <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.evaluator_workspace') }}</p>
        </div>
        <div class="grid grid-cols-1 divide-y divide-hairline-subtle sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            @foreach ([
                'total' => ['evaluator_summary_total', 'bg-sky-500'],
                'pending' => ['evaluator_summary_pending', 'bg-amber-500'],
                'reviews' => ['evaluator_summary_reviews', 'bg-violet-500'],
            ] as $key => [$label, $dot])
                <div class="px-5 py-4">
                    <div class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
                        <p class="hrm-eyebrow">{{ __($d.'.labels.'.$label) }}</p>
                    </div>
                    <p class="hrm-num mt-1.5 text-[24px] font-semibold leading-none tracking-[-0.03em] text-ink">{{ $this->assignedFormsSummary[$key] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 2xl:grid-cols-[380px_minmax(0,1fr)_minmax(340px,0.95fr)]">
        {{-- ───────────── assigned forms ───────────── --}}
        <div class="{{ $section }} self-start">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.assigned_forms') }}</p>
                <span class="hrm-num rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-ink-muted ring-1 ring-hairline" title="{{ __($d.'.labels.visible_records') }}">{{ $this->assignedForms->count() }}</span>
            </div>

            <div class="grid gap-3 border-b border-hairline-subtle p-4">
                <div>
                    <x-label for="assigned-form-search">{{ __($d.'.fields.search') }}</x-label>
                    <x-livewire-input mode="gray" id="assigned-form-search" wire:model.live.debounce.300ms="searchAssignedForms" />
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <x-label for="assigned-role-filter">{{ __($d.'.fields.role_filter') }}</x-label>
                        <select id="assigned-role-filter" wire:model.live="assignedRoleFilter" class="mt-1 {{ $select }}">
                            <option value="all">{{ __($d.'.labels.all_roles') }}</option>
                            <option value="manager">{{ __($d.'.labels.only_manager_assignments') }}</option>
                            <option value="hr">{{ __($d.'.labels.only_hr_assignments') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-label for="assigned-status-filter">{{ __($d.'.fields.status_filter') }}</x-label>
                        <select id="assigned-status-filter" wire:model.live="assignedStatusFilter" class="mt-1 {{ $select }}">
                            <option value="all">{{ __($d.'.labels.all_statuses') }}</option>
                            <option value="pending">{{ __($d.'.labels.pending_only') }}</option>
                            <option value="submitted">{{ __($d.'.labels.submitted_only') }}</option>
                        </select>
                    </div>
                </div>
                <p class="text-[11.5px] leading-5 text-ink-faint">{{ __($d.'.labels.assigned_filter_hint') }}</p>
            </div>

            <div class="hrm-scroll max-h-[70vh] divide-y divide-hairline-subtle overflow-y-auto">
                @forelse ($this->assignedForms as $form)
                    @php
                        $progress = data_get($this->assignedFormProgress, $form->id);
                        [$catTone, $catDot] = $categoryPill[$form->final_category] ?? ['bg-[#f4f4f5] text-ink-muted', 'bg-zinc-400'];
                    @endphp
                    <div wire:key="assigned-form-{{ $form->id }}" class="px-4 py-3.5 transition-colors hover:bg-[#fafafa]">
                        <div class="flex items-start gap-3">
                            <x-avatar size="sm" :name="$form->personnel_fullname ?: '—'" />
                            <div class="min-w-0 flex-1">
                                <p class="break-words text-[13.5px] font-semibold text-ink">{{ $form->personnel_fullname ?: '-' }}</p>
                                <p class="break-words text-[12px] text-ink-muted">{{ $form->cycle_name ?: '-' }} · {{ $form->template_name ?: $form->template_code ?: '-' }}</p>
                                <p class="mt-0.5 text-[11.5px] text-ink-faint">
                                    {{ __($d.'.fields.manager') }}: {{ $form->manager_name ?? '-' }} · {{ __($d.'.fields.hr_reviewer') }}: {{ $form->hr_reviewer_name ?? '-' }}
                                </p>

                                <div class="mt-2 flex flex-wrap gap-1.5 text-[11.5px]">
                                    <span class="rounded-md bg-[#f4f4f5] px-2 py-0.5 text-ink-muted">{{ __($d.'.labels.current_role', ['role' => $form->manager_id === auth()->id() ? __($d.'.evaluators.manager') : __($d.'.evaluators.hr')]) }}</span>
                                    @if ($progress)
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 font-medium {{ $progress['remaining'] > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $progress['remaining'] > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                                            {{ $progress['remaining'] > 0
                                                ? __($d.'.labels.criteria_remaining', ['count' => $progress['remaining']])
                                                : __($d.'.labels.all_criteria_scored') }}
                                        </span>
                                    @endif
                                    @if ($form->final_category)
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 font-medium {{ $catTone }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $catDot }}"></span>
                                            {{ __($d.'.labels.final_category') }}: {{ __($d.'.categories.'.$form->final_category) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button type="button" wire:click="openScoreCapture({{ $form->id }})" class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-hairline bg-white px-3 text-[14px] font-semibold text-ink-soft transition hover:border-zinc-300 hover:text-ink">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                {{ __($d.'.actions.open_score_form') }}
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-12 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.assigned_forms') }}</p>
                @endforelse
            </div>
        </div>

        {{-- ───────────── score capture ───────────── --}}
        <livewire:performance-evaluation.evaluator-score-capture :form-catalog="$this->scoreCaptureFormCatalog" />

        {{-- ───────────── test answers to review ───────────── --}}
        <div class="flex flex-col gap-4">
            <div class="{{ $section }}">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.assigned_reviews') }}</p>
                    <span class="hrm-num rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-ink-muted ring-1 ring-hairline">{{ $this->pendingAnswers->count() }}</span>
                </div>

                <div class="grid gap-3 border-b border-hairline-subtle p-4">
                    <div>
                        <x-label for="pending-answer-search">{{ __($d.'.fields.search') }}</x-label>
                        <x-livewire-input mode="gray" id="pending-answer-search" wire:model.live.debounce.300ms="searchPendingAnswers" />
                    </div>
                    <div>
                        <x-label for="pending-question-type">{{ __($d.'.fields.question_type_filter') }}</x-label>
                        <select id="pending-question-type" wire:model.live="pendingQuestionTypeFilter" class="mt-1 {{ $select }}">
                            <option value="all">{{ __($d.'.labels.all_question_types') }}</option>
                            <option value="multiple_choice">{{ __($d.'.question_types.multiple_choice') }}</option>
                            <option value="open_answer">{{ __($d.'.question_types.open_answer') }}</option>
                            <option value="case_study">{{ __($d.'.question_types.case_study') }}</option>
                            <option value="behavioral">{{ __($d.'.question_types.behavioral') }}</option>
                        </select>
                    </div>
                    <p class="text-[11.5px] leading-5 text-ink-faint">{{ __($d.'.labels.review_queue_hint') }}</p>
                </div>

                <div class="hrm-scroll max-h-96 divide-y divide-hairline-subtle overflow-y-auto">
                    @forelse ($this->pendingAnswers as $answer)
                        <div wire:key="pending-answer-{{ $answer->id }}" class="px-4 py-3 transition-colors hover:bg-[#fafafa]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="break-words text-[13px] font-semibold text-ink">{{ $answer->personnel_fullname ?: '-' }}</p>
                                    <p class="break-words text-[11.5px] text-ink-faint">{{ $answer->bank_name ?? '-' }}</p>
                                </div>
                                <span class="h-1.5 w-1.5 shrink-0 translate-y-2 rounded-full bg-amber-500"></span>
                            </div>
                            <p class="mt-1.5 break-words text-[12.5px] leading-5 text-ink-muted">{{ \Illuminate\Support\Str::limit((string) $answer->question_prompt, 120) }}</p>
                            <div class="mt-2 flex justify-end">
                                <button type="button" wire:click="startReviewAnswer({{ $answer->id }})" class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-hairline bg-white px-3 text-[14px] font-semibold text-ink-soft transition hover:border-zinc-300 hover:text-ink">
                                    {{ __($d.'.actions.open_review_form') }}
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.pending_review_answers') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="{{ $section }}">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.open_answer_review') }}</p>
                </div>
                <div class="grid content-start gap-4 p-5">
                    <div>
                        <x-ui.select-dropdown :label="__($d.'.fields.answer')" placeholder="---" mode="gray" class="w-full" instance="perf-evaluator-answer"
                            wire:model.live="reviewForm.performance_test_attempt_answer_id"
                            :model="$this->pendingAnswers->map(fn ($answer) => ['id' => $answer->id, 'label' => '#' . $answer->attempt_id . ' / ' . \Illuminate\Support\Str::limit((string) $answer->question_prompt, 50)])->values()->all()"></x-ui.select-dropdown>
                        @error('reviewForm.performance_test_attempt_answer_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>

                    <div>
                        <x-label for="assigned-review-score">{{ __($d.'.fields.review_score') }}</x-label>
                        <x-livewire-input mode="gray" id="assigned-review-score" type="number" step="0.01" wire:model="reviewForm.score" />
                        @error('reviewForm.score') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>

                    <div>
                        <x-label for="assigned-review-feedback">{{ __($d.'.fields.feedback') }}</x-label>
                        <x-ui.textarea id="assigned-review-feedback" wire:model="reviewForm.feedback" :rows="4" />
                        @error('reviewForm.feedback') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="button" wire:click="saveAnswerReview" wire:loading.attr="disabled" wire:target="saveAnswerReview" class="h-10 rounded-xl bg-ink px-5 text-[14px] font-semibold text-white transition hover:bg-ink-hover">{{ __($d.'.actions.review_answer') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
