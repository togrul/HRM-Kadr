<div class="space-y-4">
    <x-surface-card :title="__('performance_evaluation::dashboard.cards.reports')" icon="icons.pending-icon">
        <div class="space-y-5">
            <div class="grid gap-3 md:grid-cols-5">
                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-sky-700">{{ __('performance_evaluation::dashboard.fields.forms_count') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-sky-900">{{ $this->reportStats['forms'] }}</p>
                </div>
                <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-violet-700">{{ __('performance_evaluation::dashboard.fields.links_count') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-violet-900">{{ $this->reportStats['weak_links'] }}</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-amber-700">{{ __('performance_evaluation::dashboard.fields.test_sessions_count') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $this->reportStats['test_sessions'] }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-emerald-700">{{ __('performance_evaluation::dashboard.fields.test_attempts_count') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $this->reportStats['test_attempts'] }}</p>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-rose-700">{{ __('performance_evaluation::dashboard.fields.test_answers_count') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-rose-900">{{ $this->reportStats['test_answers'] }}</p>
                </div>
            </div>

            <div class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4">
                <div class="grid gap-3 lg:grid-cols-3">
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.cards.reporting_summary') }}</p>
                        <p class="text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.export_report_hint') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <x-button mode="secondary" wire:click="exportPerformanceFormsReport">{{ __('performance_evaluation::dashboard.actions.export_forms_report') }}</x-button>
                            <x-button mode="secondary" wire:click="exportPerformanceSummaryReport">{{ __('performance_evaluation::dashboard.actions.export_summary_report') }}</x-button>
                            <x-button mode="secondary" wire:click="exportPerformanceWeakLinksReport">{{ __('performance_evaluation::dashboard.actions.export_weak_links_report') }}</x-button>
                            <x-button mode="secondary" wire:click="exportPerformanceWeakPivotReport">{{ __('performance_evaluation::dashboard.actions.export_weak_pivot_report') }}</x-button>
                            <x-button mode="secondary" wire:click="exportPerformanceAuditReport">{{ __('performance_evaluation::dashboard.actions.export_audit_report') }}</x-button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.cards.test_delivery_reports') }}</p>
                        <p class="text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.test_delivery_reports_hint') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <x-button mode="secondary" wire:click="exportPerformanceTestSessionsReport">{{ __('performance_evaluation::dashboard.actions.export_test_sessions_report') }}</x-button>
                            <x-button mode="secondary" wire:click="exportPerformanceTestAttemptsReport">{{ __('performance_evaluation::dashboard.actions.export_test_attempts_report') }}</x-button>
                            <x-button mode="secondary" wire:click="exportPerformanceTestAnswersReport">{{ __('performance_evaluation::dashboard.actions.export_test_answers_report') }}</x-button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-zinc-900">{{ __('performance_evaluation::dashboard.cards.print_reports') }}</p>
                        <p class="text-xs leading-6 text-zinc-500">{{ __('performance_evaluation::dashboard.labels.print_report_hint') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('performance-evaluation.print-summary') }}" target="_blank" class="inline-flex h-11 items-center justify-center rounded-2xl bg-zinc-900 px-4 text-sm font-medium text-white">
                                {{ __('performance_evaluation::dashboard.actions.open_print_summary') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_test_sessions')" icon="icons.clock-icon">
                    <div class="space-y-3">
                        @forelse ($this->recentTestSessions as $session)
                            <x-ui.list-card>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-zinc-900">{{ $session->personnel_fullname ?: '—' }}</p>
                                        <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.test_statuses.'.$session->status) }}</x-small-badge>
                                    </div>
                                    <p class="text-xs text-zinc-500">{{ $session->bank_name ?: '—' }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.fields.reviewer') }}: {{ $session->reviewer_name ?: '—' }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.attempts_count') }}: {{ $session->attempts_count }}</x-small-badge>
                                        <x-small-badge mode="secondary">{{ optional($session->scheduled_at)->format('d.m.Y') ?: '—' }}</x-small-badge>
                                    </div>
                                </div>
                            </x-ui.list-card>
                        @empty
                            <x-ui.empty-state icon="icons.clock-icon" :message="__('performance_evaluation::dashboard.empty.test_sessions')" />
                        @endforelse
                    </div>
                </x-surface-card>

                <x-surface-card :title="__('performance_evaluation::dashboard.cards.recent_test_attempts')" icon="icons.pending-icon">
                    <div class="space-y-3">
                        @forelse ($this->recentTestAttempts as $attempt)
                            <x-ui.list-card>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-zinc-900">#{{ $attempt->id }} / {{ $attempt->personnel_fullname ?: '—' }}</p>
                                        <x-small-badge :mode="$attempt->passed ? 'green' : 'amber'">{{ __('performance_evaluation::dashboard.test_statuses.'.$attempt->status) }}</x-small-badge>
                                    </div>
                                    <p class="text-xs text-zinc-500">{{ $attempt->bank_name ?: '—' }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.score') }}: {{ $attempt->score ?? '—' }}</x-small-badge>
                                        <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.percentage') }}: {{ $attempt->percentage ?? '—' }}</x-small-badge>
                                    </div>
                                </div>
                            </x-ui.list-card>
                        @empty
                            <x-ui.empty-state icon="icons.pending-icon" :message="__('performance_evaluation::dashboard.empty.test_attempts')" />
                        @endforelse
                    </div>
                </x-surface-card>

                <x-surface-card :title="__('performance_evaluation::dashboard.cards.answer_audit')" icon="icons.comment-icon">
                    <div class="space-y-3">
                        @forelse ($this->recentTestAnswers as $answer)
                            <x-ui.list-card :tone="$answer->review_status === 'pending' ? 'amber' : 'default'">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-zinc-900">#{{ $answer->attempt_id }} / {{ $answer->personnel_fullname ?: '—' }}</p>
                                        <x-small-badge mode="{{ $answer->review_status === 'pending' ? 'amber' : 'secondary' }}">{{ $answer->review_status ? __('performance_evaluation::dashboard.review_statuses.'.$answer->review_status) : '—' }}</x-small-badge>
                                    </div>
                                    <p class="text-xs text-zinc-500">{{ __('performance_evaluation::dashboard.question_types.'.$answer->question_type) }}</p>
                                    <p class="text-xs text-zinc-500">{{ \Illuminate\Support\Str::limit((string) $answer->question_prompt, 90) }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <x-small-badge mode="sky">{{ __('performance_evaluation::dashboard.fields.final_score') }}: {{ $answer->final_score ?? '—' }}</x-small-badge>
                                        <x-small-badge mode="secondary">{{ __('performance_evaluation::dashboard.fields.is_correct') }}: {{ is_null($answer->is_correct) ? '—' : ($answer->is_correct ? __('performance_evaluation::dashboard.labels.yes') : __('performance_evaluation::dashboard.labels.no')) }}</x-small-badge>
                                    </div>
                                </div>
                            </x-ui.list-card>
                        @empty
                            <x-ui.empty-state icon="icons.comment-icon" :message="__('performance_evaluation::dashboard.empty.test_answers')" />
                        @endforelse
                    </div>
                </x-surface-card>
            </div>
        </div>
    </x-surface-card>
</div>
