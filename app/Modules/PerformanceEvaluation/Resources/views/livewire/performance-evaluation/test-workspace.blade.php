<div class="flex flex-col space-y-4 px-4 py-4 lg:px-6" wire:poll.5s="heartbeat">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ $this->backUrl }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50">
            <span aria-hidden="true">←</span>
            <span>{{ __('performance_evaluation::dashboard.actions.back_to_performance_dashboard') }}</span>
        </a>
    </div>

    <x-surface-card :title="__('performance_evaluation::dashboard.cards.test_taking_workspace')" icon="icons.performance-icon" contentClass="overflow-visible p-5">
        <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr] xl:items-start">
            <div class="space-y-3">
                <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.test_taking_workspace_hint') }}</p>
                <div class="flex flex-wrap gap-2">
                    <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.test_sessions_count') }}: {{ $this->assignedSessions->count() }}</x-small-badge>
                    <x-small-badge mode="emerald">{{ __('performance_evaluation::dashboard.fields.answers_count') }}: {{ $this->attemptProgress['answered'] }}</x-small-badge>
                    <x-small-badge mode="amber">{{ __('performance_evaluation::dashboard.fields.question_count') }}: {{ $this->attemptProgress['total'] }}</x-small-badge>
                </div>
            </div>

            <div class="rounded-[26px] border border-zinc-200 bg-gradient-to-r from-white to-zinc-50 px-4 py-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-rose-600">{{ __('performance_evaluation::dashboard.fields.remaining_time') }}</p>
                        <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900">
                            @if ($this->sessionTimer['remaining_seconds'] !== null)
                                {{ gmdate('i:s', (int) $this->sessionTimer['remaining_seconds']) }}
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-right">
                        <p class="text-xs font-medium text-rose-700">
                            @if ($this->sessionTimer['finished'])
                                {{ __('performance_evaluation::dashboard.labels.test_timer_finished') }}
                            @elseif (! $this->sessionTimer['started'])
                                {{ __('performance_evaluation::dashboard.labels.test_timer_not_started') }}
                            @elseif ($this->sessionTimer['expired'])
                                {{ __('performance_evaluation::dashboard.labels.test_timer_expired') }}
                            @else
                                {{ __('performance_evaluation::dashboard.labels.test_timer_running') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-surface-card>

    <div class="grid gap-4 xl:grid-cols-[320px_minmax(0,1fr)]">
        <div class="space-y-4 xl:sticky xl:top-4 self-start">
            <x-surface-card :title="__('performance_evaluation::dashboard.cards.assigned_test_sessions')" icon="icons.clock-icon">
                <div class="space-y-3">
                    @forelse ($this->assignedSessions as $session)
                        <x-ui.list-card :tone="$selectedSessionId === $session->id ? 'sky' : 'default'">
                            <div class="space-y-2.5">
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-zinc-900">{{ $session->bank?->name ?? '—' }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.fields.status') }}: {{ __('performance_evaluation::dashboard.test_statuses.'.$session->status) }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.fields.available_until') }}: {{ $session->available_until?->format('d.m.Y') ?? '—' }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.max_attempts') }}: {{ $session->max_attempts ?: $session->bank?->max_attempts ?: 1 }}</x-small-badge>
                                    <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.pass_score') }}: {{ $session->pass_score ?: $session->bank?->pass_score ?: '—' }}</x-small-badge>
                                </div>
                                <x-ui.action-pill wire:click="openSession({{ $session->id }})" icon="icons.edit-icon">{{ __('performance_evaluation::dashboard.actions.open_test_session') }}</x-ui.action-pill>
                            </div>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.clock-icon" :message="__('performance_evaluation::dashboard.empty.assigned_test_sessions')" />
                    @endforelse
                </div>
            </x-surface-card>

            <x-surface-card :title="__('performance_evaluation::dashboard.cards.attempt_history')" icon="icons.pending-icon">
                <div class="space-y-3">
                    @forelse ($this->attemptHistory as $attempt)
                        <x-ui.list-card>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.fields.attempt_no') }} #{{ $attempt->attempt_no }}</p>
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.test_statuses.'.$attempt->status) }}</x-small-badge>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.score') }}: {{ number_format((float) ($attempt->score ?? 0), 2) }}</x-small-badge>
                                    <x-small-badge mode="emerald">{{ __('performance_evaluation::dashboard.fields.percentage') }}: {{ number_format((float) ($attempt->percentage ?? 0), 2) }}%</x-small-badge>
                                </div>
                                <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.fields.submitted_at') }}: {{ $attempt->submitted_at?->format('d.m.Y H:i') ?? '—' }}</p>
                            </div>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.clock-icon" :message="__('performance_evaluation::dashboard.empty.test_attempt_history')" />
                    @endforelse
                </div>
            </x-surface-card>
        </div>

        <x-surface-card :title="__('performance_evaluation::dashboard.cards.test_session_runner')" icon="icons.training-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
            @if ($this->selectedSession)
                <div class="space-y-4" wire:key="test-runner-{{ $selectedSessionId }}-{{ $runnerVersion }}">
                    <div class="rounded-[28px] border border-zinc-200 bg-gradient-to-r from-zinc-50 to-white px-4 py-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-2">
                                <p class="text-lg font-semibold text-zinc-900">{{ $this->selectedSession->bank?->name ?? '—' }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.status') }}: {{ __('performance_evaluation::dashboard.test_statuses.'.$this->selectedSession->status) }}</x-small-badge>
                                    <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.answers_count') }}: {{ $this->attemptProgress['answered'] }}/{{ $this->attemptProgress['total'] }}</x-small-badge>
                                    <x-small-badge mode="amber">{{ __('performance_evaluation::dashboard.fields.max_attempts') }}: {{ $this->selectedSession->max_attempts ?: $this->selectedSession->bank?->max_attempts ?: 1 }}</x-small-badge>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-rose-200 bg-white px-4 py-3 shadow-sm"
                                wire:key="test-runner-timer-{{ $selectedSessionId }}-{{ $runnerVersion }}"
                                x-data="{
                                    remaining: {{ (int) ($this->sessionTimer['remaining_seconds'] ?? 0) }},
                                    finished: @js((bool) $this->sessionTimer['finished']),
                                    tick() { if (!this.finished && this.remaining > 0) this.remaining--; },
                                    format() { const minutes = String(Math.floor(this.remaining / 60)).padStart(2, '0'); const seconds = String(this.remaining % 60).padStart(2, '0'); return `${minutes}:${seconds}`; }
                                }"
                                x-init="if (!finished && remaining > 0) { const interval = setInterval(() => tick(), 1000); $el._timerInterval = interval; }"
                                x-effect="if (finished && $el._timerInterval) { clearInterval($el._timerInterval); }"
                                x-on:destroy.window="if ($el._timerInterval) { clearInterval($el._timerInterval) }">
                                <p class="text-[11px] font-semibold uppercase text-rose-700">{{ __('performance_evaluation::dashboard.fields.remaining_time') }}</p>
                                @if ($this->sessionTimer['remaining_seconds'] !== null)
                                    <p class="mt-1 text-2xl font-semibold text-rose-900" x-text="format()"></p>
                                @else
                                    <p class="mt-1 text-2xl font-semibold text-rose-900">—</p>
                                @endif
                                <p class="mt-1 text-xs text-zinc-500">
                                    @if ($this->sessionTimer['finished'])
                                        {{ __('performance_evaluation::dashboard.labels.test_timer_finished') }}
                                    @elseif (! $this->sessionTimer['started'])
                                        {{ __('performance_evaluation::dashboard.labels.test_timer_not_started') }}
                                    @elseif ($this->sessionTimer['expired'])
                                        {{ __('performance_evaluation::dashboard.labels.test_timer_expired') }}
                                    @else
                                        {{ __('performance_evaluation::dashboard.labels.test_timer_running') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-zinc-200 bg-white p-4">
                        <div class="space-y-4">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->questionNavigation as $questionNav)
                                    <button type="button"
                                        wire:click="openQuestion({{ $questionNav['id'] }})"
                                        class="inline-flex h-11 min-w-11 items-center justify-center rounded-2xl border px-4 text-sm font-semibold transition {{ $questionNav['active'] ? 'border-sky-400 bg-sky-50 text-sky-700' : ($questionNav['flagged'] ? 'border-amber-300 bg-amber-50 text-amber-700' : ($questionNav['answered'] ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-zinc-50 text-zinc-600')) }}">
                                        {{ $questionNav['index'] }}
                                    </button>
                                @endforeach
                            </div>

                            @if ($lastAutoSavedAt)
                                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-700">
                                    {{ __('performance_evaluation::dashboard.labels.auto_saved_at', ['time' => \Illuminate\Support\Carbon::parse($lastAutoSavedAt)->format('H:i:s')]) }}
                                </div>
                            @elseif ($autoSavePending)
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700">
                                    {{ __('performance_evaluation::dashboard.labels.auto_save_pending') }}
                                </div>
                            @endif

                            @if ($this->canBeginAttempt)
                                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="space-y-1">
                                            <p class="text-sm font-semibold text-sky-900">{{ __('performance_evaluation::dashboard.labels.test_attempt_ready') }}</p>
                                            <p class="text-xs leading-6 text-sky-700">{{ __('performance_evaluation::dashboard.labels.test_attempt_ready_hint') }}</p>
                                        </div>
                                        <x-button mode="black" wire:click="beginAttempt">{{ __('performance_evaluation::dashboard.actions.start_test_attempt') }}</x-button>
                                    </div>
                                </div>
                            @endif

                            @if ($this->selectedSessionIsReadOnly && $this->selectedSessionAttemptSummary['status'])
                                <div class="space-y-4">
                                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                                        <div class="space-y-2">
                                            <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.labels.read_only_completed_attempt') }}</p>
                                            <div class="flex flex-wrap gap-2">
                                                <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.test_statuses.'.($this->selectedSessionAttemptSummary['status'] ?? 'completed')) }}</x-small-badge>
                                                <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.score') }}: {{ number_format((float) ($this->selectedSessionAttemptSummary['score'] ?? 0), 2) }}</x-small-badge>
                                                <x-small-badge mode="emerald">{{ __('performance_evaluation::dashboard.fields.percentage') }}: {{ number_format((float) ($this->selectedSessionAttemptSummary['percentage'] ?? 0), 2) }}%</x-small-badge>
                                            </div>
                                            <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.fields.submitted_at') }}: {{ $this->selectedSessionAttemptSummary['submitted_at']?->format('d.m.Y H:i') ?? '—' }}</p>
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @if ($this->selectedAttemptAnalytics['attempt'])
                                                <a href="{{ route('performance-evaluation.test-transcript', $this->selectedAttemptAnalytics['attempt']) }}" target="_blank" class="inline-flex h-11 items-center justify-center rounded-2xl bg-white px-4 text-sm font-medium text-zinc-900 shadow-sm ring-1 ring-zinc-200">
                                                    {{ __('performance_evaluation::dashboard.actions.open_test_transcript') }}
                                                </a>
                                            @endif
                                            @if ($this->hasNextActionableSession)
                                                <x-button mode="black" wire:click="openNextActionableSession">{{ __('performance_evaluation::dashboard.actions.open_next_test_session') }}</x-button>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($this->selectedAttemptAnalytics['question_rows'])
                                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                                            <div class="space-y-3">
                                                <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.labels.question_breakdown_title') }}</p>
                                                @foreach ($this->selectedAttemptAnalytics['question_rows'] as $row)
                                                    <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                            <div class="space-y-1">
                                                                <p class="text-sm font-semibold text-zinc-900">{{ $row['index'] }}. {{ $row['prompt'] }}</p>
                                                                <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.question_types.'.$row['question_type']) }}</p>
                                                            </div>
                                                            <div class="flex flex-wrap gap-2">
                                                                <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.review_statuses.'.$row['review_status']) }}</x-small-badge>
                                                                <x-small-badge mode="sky">{{ number_format((float) ($row['final_score'] ?? 0), 2) }}/{{ number_format((float) ($row['max_score'] ?? 0), 2) }}</x-small-badge>
                                                                @if ($row['is_correct'] !== null)
                                                                    <x-small-badge :mode="$row['is_correct'] ? 'emerald' : 'rose'">
                                                                        {{ $row['is_correct'] ? __('performance_evaluation::dashboard.labels.answer_correct') : __('performance_evaluation::dashboard.labels.answer_incorrect') }}
                                                                    </x-small-badge>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="mt-3 space-y-2 text-sm text-zinc-700">
                                                            <p><span class="font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.fields.answer_text') }}:</span> {{ $row['answer_text'] }}</p>
                                                            @if ($row['correct_answer'])
                                                                <p><span class="font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.labels.correct_answer') }}:</span> {{ $row['correct_answer'] }}</p>
                                                            @endif
                                                            @if ($row['feedback'])
                                                                <p><span class="font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.fields.feedback') }}:</span> {{ $row['feedback'] }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($this->selectedAttemptAnalytics['timeline'])
                                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                                            <div class="space-y-3">
                                                <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.labels.review_timeline_title') }}</p>
                                                @foreach ($this->selectedAttemptAnalytics['timeline'] as $event)
                                                    <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                                        <div class="flex flex-col gap-1 lg:flex-row lg:items-center lg:justify-between">
                                                            <p class="text-sm font-semibold text-zinc-900">{{ $event['title'] }}</p>
                                                            <span class="text-xs text-zinc-500">{{ optional($event['meta'])->format('d.m.Y H:i') ?? '—' }}</span>
                                                        </div>
                                                        @if ($event['description'])
                                                            <p class="mt-2 text-sm text-zinc-600">{{ $event['description'] }}</p>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @elseif ($this->currentQuestion)
                                <div class="space-y-3">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="space-y-1">
                                            <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.question_types.'.$this->currentQuestion->question_type) }}</p>
                                            <p class="text-sm leading-7 text-zinc-700">{{ $this->currentQuestion->prompt }}</p>
                                        </div>
                                        <x-small-badge mode="secondary">
                                            {{ __('performance_evaluation::dashboard.fields.current_question') }}:
                                            {{ $this->questionNavigation->firstWhere('id', $this->currentQuestion->id)['index'] ?? 1 }}/{{ $this->questionNavigation->count() }}
                                        </x-small-badge>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <x-button mode="{{ ($this->questionFlags[$this->currentQuestion->id] ?? false) ? 'secondary' : 'secondary' }}" wire:click="toggleQuestionFlag({{ $this->currentQuestion->id }})">
                                            {{ ($this->questionFlags[$this->currentQuestion->id] ?? false)
                                                ? __('performance_evaluation::dashboard.actions.unflag_question')
                                                : __('performance_evaluation::dashboard.actions.flag_question') }}
                                        </x-button>
                                        @if ($this->questionFlags[$this->currentQuestion->id] ?? false)
                                            <x-small-badge mode="amber">{{ __('performance_evaluation::dashboard.labels.flagged_question') }}</x-small-badge>
                                        @endif
                                    </div>

                                    @if ($this->currentQuestion->isAutoScored())
                                        <x-ui.select-dropdown
                                            :label="__('performance_evaluation::dashboard.fields.option')"
                                            placeholder="---"
                                            mode="gray"
                                            class="w-full"
                                            :instance="'performance-test-taking-option-'.$this->currentQuestion->id"
                                            wire:model.live="answers.{{ $this->currentQuestion->id }}.selected_option_id"
                                            :model="$this->currentQuestion->options->map(fn ($option) => ['id' => $option->id, 'label' => $option->label])->values()->all()"
                                        ></x-ui.select-dropdown>
                                        @error("answers.{$this->currentQuestion->id}.selected_option_id") <x-validation>{{ $message }}</x-validation> @enderror
                                    @else
                                        <div>
                                            <x-label for="test-answer-{{ $this->currentQuestion->id }}">{{ __('performance_evaluation::dashboard.fields.answer_text') }}</x-label>
                                            <textarea id="test-answer-{{ $this->currentQuestion->id }}" wire:model.live.debounce.750ms="answers.{{ $this->currentQuestion->id }}.answer_text" class="min-h-28 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                                            @error("answers.{$this->currentQuestion->id}.answer_text") <x-validation>{{ $message }}</x-validation> @enderror
                                        </div>
                                    @endif

                                    <div class="flex flex-wrap gap-2">
                                        <x-button mode="secondary" wire:click="goToPreviousQuestion" :disabled="($this->questionNavigation->search(fn ($row) => $row['id'] === $this->currentQuestion?->id) ?? 0) === 0">
                                            {{ __('performance_evaluation::dashboard.actions.previous_question') }}
                                        </x-button>
                                        <x-button mode="secondary" wire:click="goToNextQuestion" :disabled="($this->questionNavigation->search(fn ($row) => $row['id'] === $this->currentQuestion?->id) ?? 0) >= ($this->questionNavigation->count() - 1)">
                                            {{ __('performance_evaluation::dashboard.actions.next_question') }}
                                        </x-button>
                                    </div>
                                </div>
                            @else
                                <x-ui.empty-state icon="icons.training-icon" :message="__('performance_evaluation::dashboard.empty.test_questions')" />
                            @endif

                            @if (! $this->selectedSessionIsReadOnly)
                                <div class="flex flex-wrap gap-2">
                                    <x-button mode="secondary" wire:click="saveDraft" :disabled="! $this->canWriteSelectedSession">{{ __('performance_evaluation::dashboard.actions.save_test_draft') }}</x-button>
                                    <x-button mode="black" wire:click="submitAttempt" :disabled="! $this->canWriteSelectedSession">{{ __('performance_evaluation::dashboard.actions.submit_attempt') }}</x-button>
                                </div>
                            @endif

                            @if ($this->hasNoRemainingAttempts && $this->selectedSessionIsReadOnly)
                                <x-validation>{{ __('performance_evaluation::dashboard.messages.test_attempt_limit_reached') }}</x-validation>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <x-ui.empty-state icon="icons.training-icon" :message="__('performance_evaluation::dashboard.empty.select_test_session')" />
            @endif
        </x-surface-card>
    </div>
</div>
