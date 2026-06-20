<div class="flex flex-col gap-6 px-6 py-4">
    @include('candidates::livewire.candidates.partials.recruitment-nav')

    <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="flex flex-col gap-5 border-b border-slate-200 pb-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                    {{ __('candidates::recruitment.titles.application_detail') }}
                </div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ $application->candidate?->fullname ?? '—' }}
                </h1>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">{{ $this->recruitmentPackLabel($this->currentPack()) }}</span>
                    <span class="inline-flex rounded-full bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700">{{ $this->recruitmentStageLabel($application->current_stage) }}</span>
                    <span class="inline-flex rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">{{ $this->recruitmentStatusLabel($application->status) }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('candidates.openings.show', $application->opening) }}" class="inline-flex h-11 items-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                    {{ __('candidates::recruitment.actions.open_opening') }}
                </a>
                <a href="{{ route('candidates.applications', ['opening' => $application->job_opening_id]) }}" class="inline-flex h-11 items-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                    {{ __('candidates::recruitment.actions.open_pipeline') }}
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-4">
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.opening') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ $application->opening?->title ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $application->opening?->structure?->name ?? '—' }} / {{ $application->opening?->position?->name ?? '—' }}</div>
            </div>
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.source') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ $application->source?->name ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $application->assignedRecruiter?->name ?? '—' }}</div>
            </div>
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.timeline') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ optional($application->applied_at)->format('d.m.Y H:i') ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ optional($application->moved_at)->format('d.m.Y H:i') ?? '—' }}</div>
            </div>
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.decision') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ $application->final_decision ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $application->rejectionReason?->name ?? '—' }}</div>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <livewire:candidates.application-stage-action-panel
            :application-id="$application->id"
            :key="'application-stage-action-panel-'.$application->id"
        />

        <livewire:candidates.application-ats-panel
            :application="$application"
            :key="'application-ats-panel-'.$application->id"
        />

        <livewire:candidates.application-stage-timeline-panel
            :application-id="$application->id"
            :key="'application-stage-timeline-panel-'.$application->id"
        />

        <livewire:candidates.application-artifact-timeline-panel
            :application-id="$application->id"
            :key="'application-artifact-timeline-panel-'.$application->id"
        />
    </div>
</div>
