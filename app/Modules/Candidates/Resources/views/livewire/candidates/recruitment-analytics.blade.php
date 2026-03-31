<div class="flex flex-col gap-6 px-6 py-4">
    @include('candidates::livewire.candidates.partials.recruitment-nav')

    <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="flex flex-col gap-5 border-b border-slate-200 pb-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                    {{ __('candidates::recruitment.titles.analytics') }}
                </div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ __('candidates::recruitment.titles.analytics') }}
                </h1>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">{{ $this->recruitmentPackLabel($this->currentPack) }}</span>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3 xl:grid-cols-6">
            @foreach ($this->summaryCards as $card)
                <div class="{{ $card['card'] }} rounded-[24px] border p-4">
                    <div class="{{ $card['labelColor'] }} text-[11px] font-semibold uppercase tracking-tight">{{ $card['label'] }}</div>
                    <div class="{{ $card['valueColor'] }} mt-3 text-3xl font-semibold tracking-tight">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <div class="space-y-6">
            <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.titles.pipeline_summary') }}</div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ __('candidates::recruitment.titles.pipeline_summary') }}</h2>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach ($this->stageSummary as $stage)
                        <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.pipeline_stage') }}</div>
                            <div class="mt-3 text-xl font-semibold text-slate-900">{{ $stage['label'] }}</div>
                            <div class="mt-4 text-4xl font-semibold tracking-tight text-slate-900">{{ $stage['count'] }}</div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.titles.time_to_stage') }}</div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ __('candidates::recruitment.titles.time_to_stage') }}</h2>

                <div class="mt-6 space-y-3">
                    @forelse ($this->timeToStageSummary as $row)
                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ $row['label'] }}</div>
                                <div class="text-xs text-slate-500">{{ $row['total'] }} {{ __('candidates::recruitment.labels.applications') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-semibold tracking-tight text-slate-900">{{ $row['avg_days'] }}</div>
                                <div class="text-xs text-slate-500">{{ __('candidates::recruitment.labels.days_avg') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            {{ __('candidates::recruitment.empty.analytics_stage_velocity') }}
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.titles.source_effectiveness') }}</div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ __('candidates::recruitment.titles.source_effectiveness') }}</h2>

                <div class="mt-6 space-y-3">
                    @forelse ($this->sourceEffectivenessSummary as $row)
                        <div class="rounded-[24px] border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-800">{{ $row['label'] }}</div>
                                <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ $row['success_rate'] }}%</div>
                            </div>
                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                                    <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.total') }}</div>
                                    <div class="mt-1 text-lg font-semibold tracking-tight text-slate-900">{{ $row['total'] }}</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                                    <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.successful') }}</div>
                                    <div class="mt-1 text-lg font-semibold tracking-tight text-emerald-700">{{ $row['successful'] }}</div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2">
                                    <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.rejected') }}</div>
                                    <div class="mt-1 text-lg font-semibold tracking-tight text-rose-700">{{ $row['rejected'] }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            {{ __('candidates::recruitment.empty.analytics_sources') }}
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.titles.rejection_reasons') }}</div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ __('candidates::recruitment.titles.rejection_reasons') }}</h2>

                <div class="mt-6 space-y-3">
                    @forelse ($this->rejectionReasonSummary as $row)
                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-sm font-semibold text-slate-700">{{ $row['label'] }}</div>
                            <div class="text-lg font-semibold tracking-tight text-rose-700">{{ $row['count'] }}</div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            {{ __('candidates::recruitment.empty.analytics_rejection_reasons') }}
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.titles.recent_activity') }}</div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ __('candidates::recruitment.titles.recent_activity') }}</h2>

                <div class="mt-6 space-y-3">
                    @forelse ($this->recentMoves as $event)
                        <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ __('candidates::recruitment.stages.'.$event->stage_key) }}</span>
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ $event->action }}</span>
                            </div>
                            <div class="mt-3 text-sm font-semibold text-slate-900">{{ $event->application?->candidate?->fullname ?? '—' }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $event->application?->opening?->title ?? '—' }}</div>
                            <div class="mt-2 text-xs text-slate-400">{{ $event->actor?->name ?? '—' }} · {{ optional($event->occurred_at)->format('d.m.Y H:i') ?? '—' }}</div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                            {{ __('candidates::recruitment.empty.analytics_activity') }}
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
