<div class="space-y-4">
    <x-surface-card :title="__('training_needs::dashboard.cards.executive_reports')" icon="icons.pending-icon">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ __('training_needs::dashboard.labels.executive_snapshot') }}</p>
                <p class="max-w-3xl text-sm text-zinc-500">{{ __('training_needs::dashboard.labels.executive_reports_hint') }}</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[24rem]">
                <div>
                    <x-ui.select-dropdown
                        :label="__('training_needs::dashboard.fields.report_year')"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        instance="training-reports-year"
                        wire:model.live="reportYear"
                        :model="$this->reportYearOptions"
                    ></x-ui.select-dropdown>
                </div>
                <div>
                    <x-ui.select-dropdown
                        :label="__('training_needs::dashboard.fields.report_quarter')"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        instance="training-reports-quarter"
                        wire:model.live="reportQuarter"
                        :model="[
                            ['id' => null, 'label' => __('training_needs::dashboard.labels.all_quarters')],
                            ['id' => 1, 'label' => __('training_needs::dashboard.labels.quarter_label', ['quarter' => 1])],
                            ['id' => 2, 'label' => __('training_needs::dashboard.labels.quarter_label', ['quarter' => 2])],
                            ['id' => 3, 'label' => __('training_needs::dashboard.labels.quarter_label', ['quarter' => 3])],
                            ['id' => 4, 'label' => __('training_needs::dashboard.labels.quarter_label', ['quarter' => 4])],
                        ]"
                    ></x-ui.select-dropdown>
                </div>
            </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4">
                <p class="text-[11px] font-semibold uppercase text-sky-700">{{ __('training_needs::dashboard.labels.delivered_trainings_count') }}</p>
                <p class="mt-2 text-3xl font-semibold text-sky-950">{{ $this->executiveSummary['delivered_trainings_count'] }}</p>
                <p class="mt-2 text-xs text-sky-700">{{ __('training_needs::dashboard.labels.completed_sessions_meta', ['count' => $this->executiveSummary['completed_sessions']]) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                <p class="text-[11px] font-semibold uppercase text-emerald-700">{{ __('training_needs::dashboard.labels.attended_hours') }}</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-950">{{ number_format((float) $this->executiveSummary['attended_hours'], 1) }}</p>
                <p class="mt-2 text-xs text-emerald-700">{{ __('training_needs::dashboard.labels.employee_hours_meta', ['count' => $this->executiveSummary['participants_count']]) }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                <p class="text-[11px] font-semibold uppercase text-amber-700">{{ __('training_needs::dashboard.labels.attendance_rate') }}</p>
                <p class="mt-2 text-3xl font-semibold text-amber-950">{{ number_format((float) $this->executiveSummary['attendance_rate'], 2) }}%</p>
                <p class="mt-2 text-xs text-amber-700">{{ __('training_needs::dashboard.labels.attendance_rate_meta', ['attended' => $this->executiveSummary['attended_count'], 'participants' => $this->executiveSummary['participants_count']]) }}</p>
            </div>
            <div class="rounded-2xl border border-violet-200 bg-violet-50 px-4 py-4">
                <p class="text-[11px] font-semibold uppercase text-violet-700">{{ __('training_needs::dashboard.labels.average_feedback_score') }}</p>
                <p class="mt-2 text-3xl font-semibold text-violet-950">{{ number_format((float) $this->executiveSummary['average_feedback_score'], 2) }}</p>
                <p class="mt-2 text-xs text-violet-700">{{ __('training_needs::dashboard.labels.outcome_snapshot_hint') }}</p>
            </div>
        </div>
    </x-surface-card>

    <div class="grid gap-4 xl:grid-cols-[1.2fr,0.8fr]">
        <x-surface-card :title="__('training_needs::dashboard.cards.annual_report')" icon="icons.folder-plus-icon">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm">
                    <thead>
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-tight text-zinc-400">
                            <th class="px-3 py-2">{{ __('training_needs::dashboard.fields.report_year') }}</th>
                            <th class="px-3 py-2">{{ __('training_needs::dashboard.labels.delivered_trainings_count') }}</th>
                            <th class="px-3 py-2">{{ __('training_needs::dashboard.labels.participants_count') }}</th>
                            <th class="px-3 py-2">{{ __('training_needs::dashboard.labels.attended_hours') }}</th>
                            <th class="px-3 py-2">{{ __('training_needs::dashboard.labels.budget_variance') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($this->annualReportRows as $row)
                            <tr>
                                <td class="px-3 py-3 font-semibold text-zinc-900">{{ $row->report_year }}</td>
                                <td class="px-3 py-3 text-zinc-700">{{ $row->sessions_count }}</td>
                                <td class="px-3 py-3 text-zinc-700">{{ $row->participants_count }}</td>
                                <td class="px-3 py-3 text-zinc-700">{{ number_format((float) $row->attended_hours, 1) }}</td>
                                <td class="px-3 py-3 text-zinc-700">{{ number_format((float) $row->planned_budget_total - (float) $row->actual_budget_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-zinc-500">{{ __('training_needs::dashboard.empty.reports') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-surface-card>

        <x-surface-card :title="__('training_needs::dashboard.cards.quarterly_report')" icon="icons.profile-outline-icon">
            <div class="space-y-3">
                @forelse ($this->quarterlyReportRows as $row)
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-zinc-900">{{ __('training_needs::dashboard.labels.quarter_label', ['quarter' => $row->report_quarter]) }}</p>
                                <p class="text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.quarterly_summary_meta', ['sessions' => $row->sessions_count, 'participants' => $row->participants_count]) }}</p>
                            </div>
                            <x-small-badge mode="sky">{{ number_format((float) $row->average_feedback_score, 2) }}</x-small-badge>
                        </div>
                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                            <div class="rounded-xl bg-white px-3 py-2">
                                <p class="text-[11px] uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.attended_hours') }}</p>
                                <p class="mt-1 text-lg font-semibold text-zinc-900">{{ number_format((float) $row->attended_hours, 1) }}</p>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2">
                                <p class="text-[11px] uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.planned_budget_total') }}</p>
                                <p class="mt-1 text-lg font-semibold text-zinc-900">{{ number_format((float) $row->planned_budget_total, 2) }}</p>
                            </div>
                            <div class="rounded-xl bg-white px-3 py-2">
                                <p class="text-[11px] uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.actual_budget_total') }}</p>
                                <p class="mt-1 text-lg font-semibold text-zinc-900">{{ number_format((float) $row->actual_budget_total, 2) }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="icons.pending-icon" :message="__('training_needs::dashboard.empty.reports')" />
                @endforelse
            </div>
        </x-surface-card>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-surface-card :title="__('training_needs::dashboard.cards.delivery_mix')" icon="icons.clock-icon">
            <div class="space-y-3">
                @forelse ($this->deliveryTypeRows as $row)
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-zinc-900">{{ __('training_needs::dashboard.delivery_types.'.$row->delivery_type) }}</p>
                                <p class="text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.delivery_mix_meta', ['sessions' => $row->sessions_count, 'completed' => $row->completed_sessions]) }}</p>
                            </div>
                            <x-small-badge mode="emerald">{{ number_format((float) $row->average_feedback_score, 2) }}</x-small-badge>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-zinc-600">
                            <div class="rounded-xl bg-white px-3 py-2">{{ __('training_needs::dashboard.labels.participants_count') }}: <span class="font-semibold text-zinc-900">{{ $row->participants_count }}</span></div>
                            <div class="rounded-xl bg-white px-3 py-2">{{ __('training_needs::dashboard.labels.attended_hours') }}: <span class="font-semibold text-zinc-900">{{ number_format((float) $row->attended_hours, 1) }}</span></div>
                            <div class="rounded-xl bg-white px-3 py-2">{{ __('training_needs::dashboard.labels.planned_budget_total') }}: <span class="font-semibold text-zinc-900">{{ number_format((float) $row->planned_budget_total, 2) }}</span></div>
                            <div class="rounded-xl bg-white px-3 py-2">{{ __('training_needs::dashboard.labels.actual_budget_total') }}: <span class="font-semibold text-zinc-900">{{ number_format((float) $row->actual_budget_total, 2) }}</span></div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="icons.pending-icon" :message="__('training_needs::dashboard.empty.reports')" />
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('training_needs::dashboard.cards.outcome_dashboard')" icon="icons.comment-icon">
            <div class="space-y-3">
                @forelse ($this->outcomeRows as $row)
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-zinc-900">{{ __('training_needs::dashboard.delivery_result_statuses.'.$row->result_status) }}</p>
                            <x-small-badge mode="sky">{{ $row->deliveries_count }}</x-small-badge>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-zinc-600">
                            <div class="rounded-xl bg-white px-3 py-2">{{ __('training_needs::dashboard.labels.average_feedback_score') }}: <span class="font-semibold text-zinc-900">{{ number_format((float) $row->average_feedback_score, 2) }}</span></div>
                            <div class="rounded-xl bg-white px-3 py-2">{{ __('training_needs::dashboard.labels.average_attended_hours') }}: <span class="font-semibold text-zinc-900">{{ number_format((float) $row->average_attended_hours, 2) }}</span></div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="icons.pending-icon" :message="__('training_needs::dashboard.empty.reports')" />
                @endforelse
            </div>
        </x-surface-card>

        <x-surface-card :title="__('training_needs::dashboard.cards.budget_analytics')" icon="icons.training-icon">
            <div class="grid gap-3">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.planned_budget_total') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ number_format((float) $this->executiveSummary['planned_budget_total'], 2) }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.actual_budget_total') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ number_format((float) $this->executiveSummary['actual_budget_total'], 2) }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.budget_variance') }}</p>
                    <p class="mt-1 text-2xl font-semibold {{ $this->executiveSummary['budget_variance'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ number_format((float) $this->executiveSummary['budget_variance'], 2) }}</p>
                </div>
            </div>
        </x-surface-card>
    </div>

    <div class="grid gap-4 xl:grid-cols-[0.9fr,1.1fr]">
        <x-surface-card :title="__('training_needs::dashboard.cards.need_vs_delivery')" icon="icons.profile-icon">
            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.total_needs') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->coverageSummary['total_needs'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.approved_needs') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->coverageSummary['approved_needs'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.planned_needs') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->coverageSummary['planned_needs'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.session_linked_needs') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->coverageSummary['session_linked_needs'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.completed_needs') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->coverageSummary['completed_needs'] }}</p>
                </div>
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.open_needs') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->coverageSummary['open_needs'] }}</p>
                </div>
            </div>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-sky-700">{{ __('training_needs::dashboard.labels.planning_coverage_ratio') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-sky-950">{{ number_format((float) $this->coverageSummary['planning_coverage_ratio'], 2) }}%</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase text-emerald-700">{{ __('training_needs::dashboard.labels.delivery_coverage_ratio') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-950">{{ number_format((float) $this->coverageSummary['delivery_coverage_ratio'], 2) }}%</p>
                </div>
            </div>
        </x-surface-card>

        <div class="grid gap-4">
            <x-surface-card :title="__('training_needs::dashboard.cards.competency_coverage')" icon="icons.profile-outline-icon">
                <div class="space-y-3">
                    @forelse ($this->coverageCompetencyRows as $row)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-zinc-900">{{ $row->name }}</p>
                                <x-small-badge mode="blue">{{ $row->total_needs }}</x-small-badge>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.competency_coverage_meta', ['planned' => $row->planned_needs, 'delivered' => $row->delivered_records]) }}</p>
                        </div>
                    @empty
                        <x-ui.empty-state icon="icons.pending-icon" :message="__('training_needs::dashboard.empty.reports')" />
                    @endforelse
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.program_coverage')" icon="icons.folder-plus-icon">
                <div class="space-y-3">
                    @forelse ($this->coverageProgramRows as $row)
                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $row->title }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('training_needs::dashboard.delivery_types.'.$row->delivery_type) }}</p>
                                </div>
                                <x-small-badge mode="emerald">{{ $row->sessions_count }}</x-small-badge>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.program_coverage_meta', ['recommended' => $row->recommended_needs, 'delivered' => $row->delivered_records]) }}</p>
                        </div>
                    @empty
                        <x-ui.empty-state icon="icons.pending-icon" :message="__('training_needs::dashboard.empty.reports')" />
                    @endforelse
                </div>
            </x-surface-card>
        </div>
    </div>

    <x-surface-card :title="__('training_needs::dashboard.cards.employee_hours')" icon="icons.comment-icon">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-tight text-zinc-400">
                        <th class="px-3 py-2">{{ __('training_needs::dashboard.fields.personnel') }}</th>
                        <th class="px-3 py-2">{{ __('training_needs::dashboard.fields.tabel_no') }}</th>
                        <th class="px-3 py-2">{{ __('training_needs::dashboard.labels.delivered_trainings_count') }}</th>
                        <th class="px-3 py-2">{{ __('training_needs::dashboard.labels.attended_hours') }}</th>
                        <th class="px-3 py-2">{{ __('training_needs::dashboard.labels.internal_external_split') }}</th>
                        <th class="px-3 py-2">{{ __('training_needs::dashboard.labels.average_feedback_score') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($this->employeeHoursRows as $row)
                        <tr>
                            <td class="px-3 py-3 font-semibold text-zinc-900">{{ $row->personnel_fullname }}</td>
                            <td class="px-3 py-3 text-zinc-700">{{ $row->personnel_tabel_no }}</td>
                            <td class="px-3 py-3 text-zinc-700">{{ $row->delivered_trainings_count }}</td>
                            <td class="px-3 py-3 text-zinc-700">{{ number_format((float) $row->attended_hours_total, 1) }}</td>
                            <td class="px-3 py-3 text-zinc-700">{{ number_format((float) $row->internal_hours_total, 1) }} / {{ number_format((float) $row->external_hours_total, 1) }} / {{ number_format((float) $row->hybrid_hours_total, 1) }}</td>
                            <td class="px-3 py-3 text-zinc-700">{{ number_format((float) $row->average_feedback_score, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-sm text-zinc-500">{{ __('training_needs::dashboard.empty.reports') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-surface-card>
</div>
