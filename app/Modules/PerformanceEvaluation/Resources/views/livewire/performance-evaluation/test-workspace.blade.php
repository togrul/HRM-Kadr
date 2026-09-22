@php
    $d = 'performance_evaluation::dashboard';
    $section = 'overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    $sectionHead = 'flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5';
    $primary = 'inline-flex h-10 items-center justify-center rounded-xl bg-ink px-5 text-[13px] font-semibold text-white transition hover:bg-ink-hover disabled:cursor-not-allowed disabled:opacity-40';
    $secondary = 'inline-flex h-10 items-center justify-center rounded-xl border border-hairline bg-white px-4 text-[13px] font-semibold text-ink-soft transition hover:border-zinc-300 hover:text-ink disabled:cursor-not-allowed disabled:opacity-40';
    $num = fn ($value) => number_format((float) ($value ?? 0), 2);
    $timerState = $this->sessionTimer['finished']
        ? __($d.'.labels.test_timer_finished')
        : (! $this->sessionTimer['started']
            ? __($d.'.labels.test_timer_not_started')
            : ($this->sessionTimer['expired'] ? __($d.'.labels.test_timer_expired') : __($d.'.labels.test_timer_running')));
    $progress = $this->attemptProgress;
    $progressPct = $progress['total'] > 0 ? (int) round($progress['answered'] / $progress['total'] * 100) : 0;
@endphp

@php
    // Autosave and expiry only matter while an attempt is running; idle viewers never poll.
    $attemptRunning = $this->sessionTimer['started'] && ! $this->sessionTimer['finished'];
@endphp

<div class="mx-auto flex max-w-shell flex-col gap-4 px-4 py-4 lg:px-6" @if ($attemptRunning) wire:poll.5s="heartbeat" @endif>
    <div class="flex items-center justify-between gap-3">
        <x-pill-button :href="$this->backUrl">
            <span aria-hidden="true">←</span>
            <span>{{ __($d.'.actions.back_to_performance_dashboard') }}</span>
        </x-pill-button>
    </div>

    {{-- ───────────── overview ───────────── --}}
    <div class="{{ $section }}">
        <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:items-center">
            <div class="min-w-0">
                <p class="hrm-eyebrow">{{ __($d.'.tabs.tests') }}</p>
                <h1 class="mt-1 text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ __($d.'.cards.test_taking_workspace') }}</h1>
                <p class="mt-1.5 max-w-3xl text-[13px] leading-6 text-ink-muted">{{ __($d.'.labels.test_taking_workspace_hint') }}</p>
            </div>

            <div class="grid grid-cols-3 gap-2">
                @foreach ([
                    [__($d.'.fields.test_sessions_count'), $this->assignedSessions->count(), 'bg-sky-500'],
                    [__($d.'.fields.answers_count'), $progress['answered'], 'bg-emerald-500'],
                    [__($d.'.fields.question_count'), $progress['total'], 'bg-amber-500'],
                ] as [$label, $value, $dot])
                    <div class="rounded-xl border border-hairline-subtle bg-[#fafafa] px-3 py-2.5">
                        <div class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}"></span>
                            <p class="hrm-eyebrow truncate">{{ $label }}</p>
                        </div>
                        <p class="hrm-num mt-1 text-[20px] font-semibold leading-none text-ink">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-hairline-subtle bg-[#fafafa] px-5 py-3">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-rose-600 ring-1 ring-hairline">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2M9 2h6"/></svg>
                </span>
                <div>
                    <p class="hrm-eyebrow">{{ __($d.'.fields.remaining_time') }}</p>
                    <p class="hrm-num text-[18px] font-semibold leading-tight text-ink">
                        @if ($this->sessionTimer['remaining_seconds'] !== null)
                            {{ gmdate('i:s', (int) $this->sessionTimer['remaining_seconds']) }}
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>
            <span class="rounded-full bg-white px-2.5 py-1 text-[11.5px] font-medium text-ink-muted ring-1 ring-hairline">{{ $timerState }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[320px_minmax(0,1fr)]">
        {{-- ───────────── sessions + history ───────────── --}}
        <div class="flex flex-col gap-4 self-start xl:sticky xl:top-4">
            <div class="{{ $section }}">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.assigned_test_sessions') }}</p>
                    <span class="hrm-num text-[11.5px] text-ink-faint">{{ $this->assignedSessions->count() }}</span>
                </div>
                <div class="p-1.5">
                    @forelse ($this->assignedSessions as $session)
                        @php $isSelected = $selectedSessionId === $session->id; @endphp
                        <button type="button" wire:key="assigned-session-{{ $session->id }}" wire:click="openSession({{ $session->id }})"
                            class="relative flex w-full flex-col gap-1.5 rounded-xl px-3.5 py-3 text-left transition {{ $isSelected ? 'bg-[#f4f4f5]' : 'hover:bg-[#fafafa]' }}">
                            @if ($isSelected)
                                <span class="absolute inset-y-2.5 left-0 w-[3px] rounded-full bg-ink"></span>
                            @endif
                            <span class="flex items-start justify-between gap-2">
                                <span class="text-[13.5px] {{ $isSelected ? 'font-semibold text-ink' : 'font-medium text-ink-soft' }}">{{ $session->bank?->name ?? '—' }}</span>
                                <span class="shrink-0 rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-ink-muted ring-1 ring-hairline">{{ __($d.'.test_statuses.'.$session->status) }}</span>
                            </span>
                            <span class="flex flex-wrap gap-x-3 gap-y-0.5 text-[11.5px] text-ink-faint">
                                <span>{{ __($d.'.fields.available_until') }}: <span class="hrm-num">{{ $session->available_until?->format('d.m.Y') ?? '—' }}</span></span>
                                <span>{{ __($d.'.fields.max_attempts') }}: <span class="hrm-num">{{ $session->max_attempts ?: $session->bank?->max_attempts ?: 1 }}</span></span>
                                <span>{{ __($d.'.fields.pass_score') }}: <span class="hrm-num">{{ $session->pass_score ?: $session->bank?->pass_score ?: '—' }}</span></span>
                            </span>
                            <span class="sr-only">{{ __($d.'.actions.open_test_session') }}</span>
                        </button>
                    @empty
                        <p class="px-4 py-8 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.assigned_test_sessions') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="{{ $section }}">
                <div class="{{ $sectionHead }}">
                    <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.attempt_history') }}</p>
                </div>
                <div class="divide-y divide-hairline-subtle">
                    @forelse ($this->attemptHistory as $attempt)
                        <div wire:key="attempt-history-{{ $attempt->id }}" class="px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.fields.attempt_no') }} <span class="hrm-num">#{{ $attempt->attempt_no }}</span></p>
                                <span class="rounded-full bg-[#f4f4f5] px-2 py-0.5 text-[11px] font-medium text-ink-muted">{{ __($d.'.test_statuses.'.$attempt->status) }}</span>
                            </div>
                            <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-[12px] text-ink-muted">
                                <span>{{ __($d.'.fields.score') }}: <span class="hrm-num font-semibold text-ink">{{ $num($attempt->score) }}</span></span>
                                <span>{{ __($d.'.fields.percentage') }}: <span class="hrm-num font-semibold text-ink">{{ $num($attempt->percentage) }}%</span></span>
                            </div>
                            <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __($d.'.fields.submitted_at') }}: <span class="hrm-num">{{ $attempt->submitted_at?->format('d.m.Y H:i') ?? '—' }}</span></p>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.test_attempt_history') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ───────────── runner ───────────── --}}
        <div class="{{ $section }} self-start">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.test_session_runner') }}</p>
            </div>

            @if ($this->selectedSession)
                <div class="flex flex-col gap-5 p-5" wire:key="test-runner-{{ $selectedSessionId }}-{{ $runnerVersion }}">
                    {{-- session head + live timer --}}
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-[17px] font-semibold tracking-[-0.02em] text-ink">{{ $this->selectedSession->bank?->name ?? '—' }}</p>
                            <div class="mt-2 flex flex-wrap gap-1.5 text-[11.5px]">
                                <span class="rounded-md bg-[#f4f4f5] px-2 py-0.5 text-ink-muted">{{ __($d.'.fields.status') }}: {{ __($d.'.test_statuses.'.$this->selectedSession->status) }}</span>
                                <span class="rounded-md bg-[#f4f4f5] px-2 py-0.5 text-ink-muted">{{ __($d.'.fields.max_attempts') }}: <span class="hrm-num">{{ $this->selectedSession->max_attempts ?: $this->selectedSession->bank?->max_attempts ?: 1 }}</span></span>
                            </div>
                        </div>

                        <div class="shrink-0 rounded-xl border border-rose-100 bg-rose-50/60 px-4 py-2.5 sm:text-right"
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
                            <p class="text-[10.5px] font-semibold uppercase tracking-[0.08em] text-rose-700">{{ __($d.'.fields.remaining_time') }}</p>
                            @if ($this->sessionTimer['remaining_seconds'] !== null)
                                <p class="hrm-num mt-0.5 text-[24px] font-semibold leading-none text-rose-900" x-text="format()"></p>
                            @else
                                <p class="hrm-num mt-0.5 text-[24px] font-semibold leading-none text-rose-900">—</p>
                            @endif
                            <p class="mt-1 text-[11px] text-ink-muted">{{ $timerState }}</p>
                        </div>
                    </div>

                    {{-- progress --}}
                    <div>
                        <div class="flex items-center justify-between text-[12px]">
                            <span class="text-ink-muted">{{ __($d.'.fields.answers_count') }}</span>
                            <span class="hrm-num font-semibold text-ink">{{ $progress['answered'] }} / {{ $progress['total'] }}</span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[#f4f4f5]">
                            <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $progressPct }}%"></div>
                        </div>
                    </div>

                    {{-- question navigation --}}
                    @if ($this->questionNavigation->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($this->questionNavigation as $questionNav)
                                <button type="button"
                                    wire:key="question-nav-{{ $questionNav['id'] }}"
                                    wire:click="openQuestion({{ $questionNav['id'] }})"
                                    @class([
                                        'hrm-num relative inline-flex h-10 min-w-10 items-center justify-center rounded-xl border px-3 text-[13px] font-semibold transition',
                                        'border-ink bg-ink text-white' => $questionNav['active'],
                                        'border-amber-300 bg-amber-50 text-amber-800' => ! $questionNav['active'] && $questionNav['flagged'],
                                        'border-emerald-200 bg-emerald-50 text-emerald-700' => ! $questionNav['active'] && ! $questionNav['flagged'] && $questionNav['answered'],
                                        'border-hairline bg-white text-ink-muted hover:border-zinc-300 hover:text-ink' => ! $questionNav['active'] && ! $questionNav['flagged'] && ! $questionNav['answered'],
                                    ])>
                                    {{ $questionNav['index'] }}
                                    @if ($questionNav['flagged'] && ! $questionNav['active'])
                                        <span class="absolute -right-0.5 -top-0.5 h-2 w-2 rounded-full bg-amber-500 ring-2 ring-white"></span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if ($lastAutoSavedAt)
                        <p class="flex items-center gap-1.5 text-[12px] text-emerald-700">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                            {{ __($d.'.labels.auto_saved_at', ['time' => \Illuminate\Support\Carbon::parse($lastAutoSavedAt)->format('H:i:s')]) }}
                        </p>
                    @elseif ($autoSavePending)
                        <p class="flex items-center gap-1.5 text-[12px] text-amber-700">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span>
                            {{ __($d.'.labels.auto_save_pending') }}
                        </p>
                    @endif

                    @if ($this->canBeginAttempt)
                        <div class="flex flex-col gap-3 rounded-xl border border-sky-100 bg-sky-50/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-[13.5px] font-semibold text-sky-900">{{ __($d.'.labels.test_attempt_ready') }}</p>
                                <p class="mt-0.5 text-[12.5px] leading-5 text-sky-800/80">{{ __($d.'.labels.test_attempt_ready_hint') }}</p>
                            </div>
                            <button type="button" wire:click="beginAttempt" class="{{ $primary }} shrink-0">{{ __($d.'.actions.start_test_attempt') }}</button>
                        </div>
                    @endif

                    @if ($this->selectedSessionIsReadOnly && $this->selectedSessionAttemptSummary['status'])
                        {{-- completed attempt, read only --}}
                        <div class="rounded-xl border border-hairline bg-[#fafafa] p-4">
                            <p class="text-[13.5px] font-semibold text-ink">{{ __($d.'.labels.read_only_completed_attempt') }}</p>
                            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-hairline">
                                    <p class="hrm-eyebrow">{{ __($d.'.fields.status') }}</p>
                                    <p class="mt-0.5 text-[13px] font-semibold text-ink">{{ __($d.'.test_statuses.'.($this->selectedSessionAttemptSummary['status'] ?? 'completed')) }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-hairline">
                                    <p class="hrm-eyebrow">{{ __($d.'.fields.score') }}</p>
                                    <p class="hrm-num mt-0.5 text-[16px] font-semibold text-ink">{{ $num($this->selectedSessionAttemptSummary['score'] ?? 0) }}</p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 ring-1 ring-hairline">
                                    <p class="hrm-eyebrow">{{ __($d.'.fields.percentage') }}</p>
                                    <p class="hrm-num mt-0.5 text-[16px] font-semibold text-ink">{{ $num($this->selectedSessionAttemptSummary['percentage'] ?? 0) }}%</p>
                                </div>
                            </div>
                            <p class="mt-2 text-[11.5px] text-ink-faint">{{ __($d.'.fields.submitted_at') }}: <span class="hrm-num">{{ $this->selectedSessionAttemptSummary['submitted_at']?->format('d.m.Y H:i') ?? '—' }}</span></p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @if ($this->selectedAttemptAnalytics['attempt'])
                                    <a href="{{ route('performance-evaluation.test-transcript', $this->selectedAttemptAnalytics['attempt']) }}" target="_blank" class="{{ $secondary }}">
                                        {{ __($d.'.actions.open_test_transcript') }}
                                    </a>
                                @endif
                                @if ($this->hasNextActionableSession)
                                    <button type="button" wire:click="openNextActionableSession" class="{{ $primary }}">{{ __($d.'.actions.open_next_test_session') }}</button>
                                @endif
                            </div>
                        </div>

                        @if ($this->selectedAttemptAnalytics['question_rows'])
                            <div>
                                <p class="text-[13.5px] font-semibold text-ink">{{ __($d.'.labels.question_breakdown_title') }}</p>
                                <div class="mt-2 flex flex-col gap-2">
                                    @foreach ($this->selectedAttemptAnalytics['question_rows'] as $row)
                                        <div wire:key="question-row-{{ $row['index'] }}" class="rounded-xl border border-hairline bg-white p-4">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0">
                                                    <p class="text-[13.5px] font-semibold leading-6 text-ink"><span class="hrm-num text-ink-faint">{{ $row['index'] }}.</span> {{ $row['prompt'] }}</p>
                                                    <p class="text-[11.5px] text-ink-faint">{{ __($d.'.question_types.'.$row['question_type']) }}</p>
                                                </div>
                                                <div class="flex shrink-0 flex-wrap gap-1.5 text-[11.5px]">
                                                    <span class="rounded-full bg-[#f4f4f5] px-2 py-0.5 text-ink-muted">{{ __($d.'.review_statuses.'.$row['review_status']) }}</span>
                                                    <span class="hrm-num rounded-full bg-[#f4f4f5] px-2 py-0.5 text-ink">{{ $num($row['final_score']) }}/{{ $num($row['max_score']) }}</span>
                                                    @if ($row['is_correct'] !== null)
                                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 font-medium {{ $row['is_correct'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                            <span class="h-1.5 w-1.5 rounded-full {{ $row['is_correct'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                                            {{ $row['is_correct'] ? __($d.'.labels.answer_correct') : __($d.'.labels.answer_incorrect') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <dl class="mt-3 grid gap-1.5 text-[13px] text-ink-soft">
                                                <div><dt class="inline font-semibold text-ink">{{ __($d.'.fields.answer_text') }}:</dt> <dd class="inline">{{ $row['answer_text'] }}</dd></div>
                                                @if ($row['correct_answer'])
                                                    <div><dt class="inline font-semibold text-ink">{{ __($d.'.labels.correct_answer') }}:</dt> <dd class="inline">{{ $row['correct_answer'] }}</dd></div>
                                                @endif
                                                @if ($row['feedback'])
                                                    <div><dt class="inline font-semibold text-ink">{{ __($d.'.fields.feedback') }}:</dt> <dd class="inline">{{ $row['feedback'] }}</dd></div>
                                                @endif
                                            </dl>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($this->selectedAttemptAnalytics['timeline'])
                            <div>
                                <p class="text-[13.5px] font-semibold text-ink">{{ __($d.'.labels.review_timeline_title') }}</p>
                                <ol class="mt-3">
                                    @foreach ($this->selectedAttemptAnalytics['timeline'] as $event)
                                        <li class="relative border-l border-hairline pb-3 pl-4 last:pb-0">
                                            <span class="absolute -left-[4.5px] top-1.5 h-2 w-2 rounded-full bg-zinc-400"></span>
                                            <div class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:justify-between">
                                                <p class="text-[13px] font-semibold text-ink">{{ $event['title'] }}</p>
                                                <span class="hrm-num text-[11.5px] text-ink-faint">{{ optional($event['meta'])->format('d.m.Y H:i') ?? '—' }}</span>
                                            </div>
                                            @if ($event['description'])
                                                <p class="mt-0.5 text-[12.5px] text-ink-muted">{{ $event['description'] }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    @elseif ($this->currentQuestion)
                        @php
                            $question = $this->currentQuestion;
                            $position = $this->questionNavigation->search(fn ($row) => $row['id'] === $question->id) ?? 0;
                            $isFlagged = $this->questionFlags[$question->id] ?? false;
                            $selectedOption = (int) data_get($answers, $question->id.'.selected_option_id');
                        @endphp

                        {{-- current question --}}
                        <div class="rounded-2xl border border-hairline bg-white p-5 sm:p-6">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="rounded-full bg-[#f4f4f5] px-2.5 py-1 text-[11.5px] font-medium text-ink-muted">{{ __($d.'.question_types.'.$question->question_type) }}</span>
                                <span class="text-[12px] text-ink-muted">
                                    {{ __($d.'.fields.current_question') }}:
                                    <span class="hrm-num font-semibold text-ink">{{ $this->questionNavigation->firstWhere('id', $question->id)['index'] ?? 1 }}/{{ $this->questionNavigation->count() }}</span>
                                </span>
                            </div>

                            <p class="mt-4 text-[16px] font-medium leading-7 text-ink sm:text-[17px]">{{ $question->prompt }}</p>

                            <div class="mt-5">
                                @if ($question->isAutoScored())
                                    <fieldset>
                                        <legend class="sr-only">{{ __($d.'.fields.option') }}</legend>
                                        <div class="flex flex-col gap-2">
                                            @foreach ($question->options as $option)
                                                @php $isChosen = $selectedOption === (int) $option->id; @endphp
                                                <label wire:key="option-{{ $question->id }}-{{ $option->id }}"
                                                    @class([
                                                        'flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-3.5 transition',
                                                        'border-ink bg-[#f4f4f5] ring-1 ring-ink' => $isChosen,
                                                        'border-hairline bg-white hover:border-zinc-300 hover:bg-[#fafafa]' => ! $isChosen,
                                                    ])>
                                                    <input type="radio" class="sr-only" name="question-{{ $question->id }}" value="{{ $option->id }}"
                                                        wire:model.live="answers.{{ $question->id }}.selected_option_id">
                                                    <span @class([
                                                        'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition',
                                                        'border-ink bg-ink' => $isChosen,
                                                        'border-zinc-300 bg-white' => ! $isChosen,
                                                    ])>
                                                        @if ($isChosen)
                                                            <span class="h-2 w-2 rounded-full bg-white"></span>
                                                        @endif
                                                    </span>
                                                    <span class="text-[14px] leading-6 {{ $isChosen ? 'font-semibold text-ink' : 'text-ink-soft' }}">{{ $option->label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                    @error("answers.{$question->id}.selected_option_id") <x-validation>{{ $message }}</x-validation> @enderror
                                @else
                                    <x-label for="test-answer-{{ $question->id }}">{{ __($d.'.fields.answer_text') }}</x-label>
                                    <textarea id="test-answer-{{ $question->id }}" wire:model.live.debounce.750ms="answers.{{ $question->id }}.answer_text" rows="6"
                                        class="w-full rounded-xl border border-hairline bg-[#fafafa] px-4 py-3 text-[14px] leading-6 text-ink focus:border-zinc-400 focus:bg-white focus:outline-none focus:ring-0"></textarea>
                                    @error("answers.{$question->id}.answer_text") <x-validation>{{ $message }}</x-validation> @enderror
                                @endif
                            </div>

                            <div class="mt-5 flex flex-col gap-3 border-t border-hairline-subtle pt-4 sm:flex-row sm:items-center sm:justify-between">
                                <button type="button" wire:click="toggleQuestionFlag({{ $question->id }})"
                                    class="inline-flex h-9 items-center gap-1.5 self-start rounded-xl border px-3 text-[12.5px] font-semibold transition {{ $isFlagged ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-hairline bg-white text-ink-muted hover:border-zinc-300 hover:text-ink' }}">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="{{ $isFlagged ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4a1 1 0 0 1 1-1h12l-2 4 2 4H5"/></svg>
                                    {{ $isFlagged ? __($d.'.actions.unflag_question') : __($d.'.actions.flag_question') }}
                                </button>
                                @if ($isFlagged)
                                    <span class="sr-only">{{ __($d.'.labels.flagged_question') }}</span>
                                @endif

                                <div class="flex gap-2">
                                    <button type="button" wire:click="goToPreviousQuestion" class="{{ $secondary }} flex-1 sm:flex-none" @disabled($position === 0)>
                                        ← {{ __($d.'.actions.previous_question') }}
                                    </button>
                                    <button type="button" wire:click="goToNextQuestion" class="{{ $secondary }} flex-1 sm:flex-none" @disabled($position >= $this->questionNavigation->count() - 1)>
                                        {{ __($d.'.actions.next_question') }} →
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="rounded-xl border border-dashed border-hairline px-4 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.test_questions') }}</p>
                    @endif

                    @if (! $this->selectedSessionIsReadOnly)
                        <div class="flex flex-col-reverse gap-2 border-t border-hairline-subtle pt-4 sm:flex-row sm:justify-end">
                            <button type="button" wire:click="saveDraft" class="{{ $secondary }}" @disabled(! $this->canWriteSelectedSession)>{{ __($d.'.actions.save_test_draft') }}</button>
                            <button type="button"
                                x-on:click="$dispatch('confirm-action', { tone: 'emerald', message: @js(__($d.'.confirmations.submit_attempt')), run: () => $wire.submitAttempt() })"
                                class="{{ $primary }}" @disabled(! $this->canWriteSelectedSession)>{{ __($d.'.actions.submit_attempt') }}</button>
                        </div>
                    @endif

                    @if ($this->hasNoRemainingAttempts && $this->selectedSessionIsReadOnly)
                        <x-validation>{{ __($d.'.messages.test_attempt_limit_reached') }}</x-validation>
                    @endif
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f4f4f5] text-ink-muted">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6M10 3v6L5 19a1.5 1.5 0 0 0 1.3 2h11.4a1.5 1.5 0 0 0 1.3-2l-5-10V3"/></svg>
                    </div>
                    <p class="mt-4 text-[13px] text-ink-muted">{{ __($d.'.empty.select_test_session') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
