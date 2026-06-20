<div class="sidemenu-title">
    <h2 class="text-xl font-semibold text-gray-500 font-title" id="slide-over-title">
        {!! $title ?? '' !!}
    </h2>
    <div class="mt-1">
        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
            {{ __('candidates::common.labels.mode') }}: {{ $this->candidateModeLabel() }}
        </span>
    </div>
</div>

@if (isset($this->candidateModelData) && $this->candidateModelData?->latestApplication)
    <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
        <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
            {{ __('candidates::recruitment.titles.recruitment_context') }}
        </div>
        <div class="mt-3 grid gap-3 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.total_applications') }}</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ (int) ($this->candidateModelData->applications_count ?? 0) }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.active_applications') }}</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-emerald-700">{{ (int) ($this->candidateModelData->active_applications_count ?? 0) }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.latest_opening') }}</div>
                <div class="mt-2 text-sm font-semibold text-slate-900">{{ $this->candidateModelData->latestApplication->opening?->title ?? '—' }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ $this->candidateModelData->latestApplication->opening?->requisition?->title ?? '—' }}</div>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                {{ __('candidates::recruitment.labels.latest_application') }}: {{ __('candidates::recruitment.stages.'.$this->candidateModelData->latestApplication->current_stage) }}
            </span>
            @if ($this->candidateModelData->latestApplication->opening)
                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ __('candidates::recruitment.labels.latest_opening') }}: {{ $this->candidateModelData->latestApplication->opening->title }}
                </span>
            @endif
        </div>
        @if ($this->candidateModelData->applications->isNotEmpty())
            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                    {{ __('candidates::recruitment.titles.recent_applications') }}
                </div>
                <div class="mt-3 space-y-2">
                    @foreach ($this->candidateModelData->applications->take(3) as $application)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900">{{ $application->opening?->title ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $application->opening?->requisition?->title ?? '—' }}</div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-slate-600">
                                    {{ __('candidates::recruitment.stages.'.$application->current_stage) }}
                                </span>
                                <a href="{{ route('candidates.applications.show', $application) }}" class="inline-flex h-8 items-center rounded-xl border border-slate-200 bg-white px-3 text-[11px] font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                                    {{ __('candidates::recruitment.actions.open_application') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ route('candidates.applications.show', $this->candidateModelData->latestApplication) }}" class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                {{ __('candidates::recruitment.actions.open_latest_application') }}
            </a>
            @if ($this->candidateModelData->latestApplication->opening)
                <a href="{{ route('candidates.openings.show', $this->candidateModelData->latestApplication->opening) }}" class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                    {{ __('candidates::recruitment.actions.open_latest_opening') }}
                </a>
            @endif
            <a href="{{ route('candidates.applications', ['candidate' => $this->candidateModelData->id]) }}" class="inline-flex h-9 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                {{ __('candidates::recruitment.actions.open_candidate_pipeline') }}
            </a>
        </div>
    </div>
@endif
