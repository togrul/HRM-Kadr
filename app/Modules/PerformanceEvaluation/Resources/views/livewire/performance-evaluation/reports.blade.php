@php
    $d = 'performance_evaluation::dashboard';
    $section = 'overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    $sectionHead = 'flex items-center justify-between gap-3 border-b border-hairline-subtle bg-[#fafafa] px-5 py-2.5';
    $exportButton = 'inline-flex h-8 items-center gap-1.5 rounded-lg border border-hairline bg-white px-2.5 text-[12px] font-medium text-ink-soft transition hover:border-zinc-300 hover:text-ink';
    $row = 'px-5 py-3 transition-colors hover:bg-[#fafafa]';
    $chip = 'rounded-md bg-[#f4f4f5] px-1.5 py-0.5 text-[11px] text-ink-muted';
    $pct = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $downloadIcon = '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>';
    $stats = [
        ['forms_count', $this->reportStats['forms'], 'bg-sky-500'],
        ['links_count', $this->reportStats['weak_links'], 'bg-violet-500'],
        ['test_sessions_count', $this->reportStats['test_sessions'], 'bg-amber-500'],
        ['test_attempts_count', $this->reportStats['test_attempts'], 'bg-emerald-500'],
        ['test_answers_count', $this->reportStats['test_answers'], 'bg-rose-500'],
    ];
@endphp

<div class="mx-auto flex max-w-6xl flex-col gap-4">
    {{-- ───────────── headline numbers ───────────── --}}
    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
        @foreach ($stats as [$label, $value, $dot])
            <div class="rounded-2xl border border-hairline bg-white px-4 py-3 shadow-card">
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}"></span>
                    <p class="hrm-eyebrow truncate">{{ __($d.'.fields.'.$label) }}</p>
                </div>
                <p class="hrm-num mt-1.5 text-[22px] font-semibold leading-none tracking-[-0.03em] text-ink">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- ───────────── exports ───────────── --}}
    <div class="{{ $section }}">
        <div class="{{ $sectionHead }}">
            <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.reports') }}</p>
        </div>
        <div class="grid grid-cols-1 divide-y divide-hairline-subtle lg:grid-cols-3 lg:divide-x lg:divide-y-0">
            <div class="px-5 py-4">
                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.reporting_summary') }}</p>
                <p class="mt-1 text-[12px] leading-5 text-ink-muted">{{ __($d.'.labels.export_report_hint') }}</p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <button type="button" wire:click="exportPerformanceFormsReport" class="{{ $exportButton }}">{!! $downloadIcon !!}{{ __($d.'.actions.export_forms_report') }}</button>
                    <button type="button" wire:click="exportPerformanceSummaryReport" class="{{ $exportButton }}">{!! $downloadIcon !!}{{ __($d.'.actions.export_summary_report') }}</button>
                    <button type="button" wire:click="exportPerformanceWeakLinksReport" class="{{ $exportButton }}">{!! $downloadIcon !!}{{ __($d.'.actions.export_weak_links_report') }}</button>
                    <button type="button" wire:click="exportPerformanceWeakPivotReport" class="{{ $exportButton }}">{!! $downloadIcon !!}{{ __($d.'.actions.export_weak_pivot_report') }}</button>
                    <button type="button" wire:click="exportPerformanceAuditReport" class="{{ $exportButton }}">{!! $downloadIcon !!}{{ __($d.'.actions.export_audit_report') }}</button>
                </div>
            </div>
            <div class="px-5 py-4">
                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.test_delivery_reports') }}</p>
                <p class="mt-1 text-[12px] leading-5 text-ink-muted">{{ __($d.'.labels.test_delivery_reports_hint') }}</p>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <button type="button" wire:click="exportPerformanceTestSessionsReport" class="{{ $exportButton }}">{!! $downloadIcon !!}{{ __($d.'.actions.export_test_sessions_report') }}</button>
                    <button type="button" wire:click="exportPerformanceTestAttemptsReport" class="{{ $exportButton }}">{!! $downloadIcon !!}{{ __($d.'.actions.export_test_attempts_report') }}</button>
                    <button type="button" wire:click="exportPerformanceTestAnswersReport" class="{{ $exportButton }}">{!! $downloadIcon !!}{{ __($d.'.actions.export_test_answers_report') }}</button>
                </div>
            </div>
            <div class="px-5 py-4">
                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.print_reports') }}</p>
                <p class="mt-1 text-[12px] leading-5 text-ink-muted">{{ __($d.'.labels.print_report_hint') }}</p>
                <a href="{{ route('performance-evaluation.print-summary') }}" target="_blank"
                    class="mt-3 inline-flex h-9 items-center gap-1.5 rounded-xl bg-ink px-3.5 text-[12.5px] font-semibold text-white hover:bg-ink-hover">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    {{ __($d.'.actions.open_print_summary') }}
                </a>
            </div>
        </div>
    </div>

    {{-- ───────────── recent delivery ───────────── --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}"><p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.recent_test_sessions') }}</p></div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->recentTestSessions as $session)
                    <div wire:key="report-session-{{ $session->id }}" class="{{ $row }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="min-w-0 truncate text-[13px] font-semibold text-ink">{{ $session->personnel_fullname ?: '—' }}</p>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#f4f4f5] px-2 py-0.5 text-[11px] font-medium text-ink-muted"><span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>{{ __($d.'.test_statuses.'.$session->status) }}</span>
                        </div>
                        <p class="mt-0.5 truncate text-[12px] text-ink-muted">{{ $session->bank_name ?: '—' }}</p>
                        <p class="text-[11.5px] text-ink-faint">{{ __($d.'.fields.reviewer') }}: {{ $session->reviewer_name ?: '—' }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <span class="{{ $chip }}">{{ __($d.'.fields.attempts_count') }}: <span class="hrm-num text-ink">{{ $session->attempts_count }}</span></span>
                            <span class="{{ $chip }} hrm-num">{{ optional($session->scheduled_at)->format('d.m.Y') ?: '—' }}</span>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.test_sessions') }}</p>
                @endforelse
            </div>
        </div>

        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}"><p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.recent_test_attempts') }}</p></div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->recentTestAttempts as $attempt)
                    <div wire:key="report-attempt-{{ $attempt->id }}" class="{{ $row }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="min-w-0 truncate text-[13px] font-semibold text-ink"><span class="hrm-num text-ink-faint">#{{ $attempt->id }}</span> {{ $attempt->personnel_fullname ?: '—' }}</p>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $attempt->passed ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}"><span class="h-1.5 w-1.5 rounded-full {{ $attempt->passed ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>{{ __($d.'.test_statuses.'.$attempt->status) }}</span>
                        </div>
                        <p class="mt-0.5 truncate text-[12px] text-ink-muted">{{ $attempt->bank_name ?: '—' }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <span class="{{ $chip }}">{{ __($d.'.fields.score') }}: <span class="hrm-num text-ink">{{ $attempt->score ?? '—' }}</span></span>
                            <span class="{{ $chip }}">{{ __($d.'.fields.percentage') }}: <span class="hrm-num text-ink">{{ $attempt->percentage ?? '—' }}</span></span>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.test_attempts') }}</p>
                @endforelse
            </div>
        </div>

        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}"><p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.answer_audit') }}</p></div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->recentTestAnswers as $answer)
                    @php $pending = $answer->review_status === 'pending'; @endphp
                    <div wire:key="report-answer-{{ $loop->index }}-{{ $answer->attempt_id }}" class="{{ $row }} {{ $pending ? 'bg-amber-50/40' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="min-w-0 truncate text-[13px] font-semibold text-ink"><span class="hrm-num text-ink-faint">#{{ $answer->attempt_id }}</span> {{ $answer->personnel_fullname ?: '—' }}</p>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $pending ? 'bg-amber-50 text-amber-700' : 'bg-[#f4f4f5] text-ink-muted' }}"><span class="h-1.5 w-1.5 rounded-full {{ $pending ? 'bg-amber-500' : 'bg-zinc-400' }}"></span>{{ $answer->review_status ? __($d.'.review_statuses.'.$answer->review_status) : '—' }}</span>
                        </div>
                        <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __($d.'.question_types.'.$answer->question_type) }}</p>
                        <p class="text-[12px] leading-5 text-ink-muted">{{ \Illuminate\Support\Str::limit((string) $answer->question_prompt, 90) }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <span class="{{ $chip }}">{{ __($d.'.fields.final_score') }}: <span class="hrm-num text-ink">{{ $answer->final_score ?? '—' }}</span></span>
                            <span class="{{ $chip }}">{{ __($d.'.fields.is_correct') }}: {{ is_null($answer->is_correct) ? '—' : ($answer->is_correct ? __($d.'.labels.yes') : __($d.'.labels.no')) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.test_answers') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ───────────── analysis ───────────── --}}
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.question_analysis') }}</p>
                <button type="button" wire:click="exportPerformanceQuestionAnalysisReport" class="{{ $exportButton }}" title="{{ __($d.'.actions.export_question_analysis_report') }}">{!! $downloadIcon !!}<span class="sr-only">{{ __($d.'.actions.export_question_analysis_report') }}</span></button>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->questionAnalysisRows as $row)
                    <div wire:key="report-question-{{ $loop->index }}" class="{{ $row }}">
                        <p class="text-[13px] font-medium text-ink">{{ \Illuminate\Support\Str::limit((string) $row->question_prompt, 90) }}</p>
                        <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ $row->bank_name ?: '—' }} · {{ __($d.'.question_types.'.$row->question_type) }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, (float) $row->correct_rate) }}%"></div>
                            </div>
                            <span class="hrm-num shrink-0 text-[11.5px] font-semibold text-ink">{{ $pct($row->correct_rate) }}%</span>
                        </div>
                        <div class="mt-1.5 flex flex-wrap gap-1.5 text-[11px]">
                            <span class="{{ $chip }}">{{ __($d.'.fields.answers_count') }}: <span class="hrm-num text-ink">{{ $row->answers_count }}</span></span>
                            <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-amber-700">{{ __($d.'.fields.pending_reviews_count') }}: <span class="hrm-num">{{ $row->pending_reviews_count }}</span></span>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.test_answers') }}</p>
                @endforelse
            </div>
        </div>

        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.reviewer_turnaround') }}</p>
                <button type="button" wire:click="exportPerformanceReviewerTurnaroundReport" class="{{ $exportButton }}" title="{{ __($d.'.actions.export_reviewer_turnaround_report') }}">{!! $downloadIcon !!}<span class="sr-only">{{ __($d.'.actions.export_reviewer_turnaround_report') }}</span></button>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->reviewerTurnaroundRows as $row)
                    <div wire:key="report-reviewer-{{ $loop->index }}" class="{{ $row }} flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-avatar size="sm" :name="$row->reviewer_name ?: '—'" />
                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-semibold text-ink">{{ $row->reviewer_name ?: '—' }}</p>
                                <p class="text-[11.5px] text-ink-faint">{{ __($d.'.fields.reviewed_answers_count') }}: <span class="hrm-num text-ink-muted">{{ $row->reviewed_answers_count }}</span></p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="hrm-num text-[15px] font-semibold text-ink">{{ $pct($row->average_review_minutes ?? 0) }}</p>
                            <p class="max-w-[9rem] truncate text-[10.5px] text-ink-faint">{{ __($d.'.fields.average_review_minutes') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.pending_review_answers') }}</p>
                @endforelse
            </div>
        </div>

        <div class="{{ $section }}">
            <div class="{{ $sectionHead }}">
                <p class="text-[13px] font-semibold text-ink">{{ __($d.'.cards.personnel_test_outcomes') }}</p>
                <button type="button" wire:click="exportPerformancePersonnelOutcomeReport" class="{{ $exportButton }}" title="{{ __($d.'.actions.export_personnel_outcomes_report') }}">{!! $downloadIcon !!}<span class="sr-only">{{ __($d.'.actions.export_personnel_outcomes_report') }}</span></button>
            </div>
            <div class="divide-y divide-hairline-subtle">
                @forelse ($this->personnelOutcomeRows as $row)
                    <div wire:key="report-outcome-{{ $loop->index }}" class="{{ $row }}">
                        <div class="flex items-center justify-between gap-2">
                            <p class="min-w-0 truncate text-[13px] font-semibold text-ink">{{ $row->personnel_fullname ?: '—' }}</p>
                            <span class="hrm-num shrink-0 text-[11.5px] text-ink-faint">#{{ $row->personnel_tabel_no ?: '—' }}</span>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div class="h-full rounded-full bg-ink" style="width: {{ min(100, (float) $row->pass_rate) }}%"></div>
                            </div>
                            <span class="hrm-num shrink-0 text-[11.5px] font-semibold text-ink">{{ $pct($row->pass_rate) }}%</span>
                        </div>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <span class="{{ $chip }}">{{ __($d.'.fields.attempts_count') }}: <span class="hrm-num text-ink">{{ $row->attempts_count }}</span></span>
                            <span class="{{ $chip }}">{{ __($d.'.fields.pass_rate') }}: <span class="hrm-num text-ink">{{ $pct($row->pass_rate) }}%</span></span>
                            <span class="{{ $chip }}">{{ __($d.'.fields.average_percentage') }}: <span class="hrm-num text-ink">{{ $pct($row->average_percentage) }}%</span></span>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-[12.5px] text-ink-faint">{{ __($d.'.empty.test_attempts') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
