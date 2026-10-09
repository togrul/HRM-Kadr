<div class="sidemenu-title">
    <h2 class="text-xl font-semibold text-zinc-500 font-title" id="slide-over-title">
        {!! $title ?? '' !!}
    </h2>
</div>

@if (isset($this->candidateModelData) && $this->candidateModelData)
    @if ($this->candidateModelData->hired_personnel_id)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3" data-candidate-hire-links>
            <div class="text-sm text-emerald-800">
                {{ __('candidates::common.hire.hired_note') }}
                @if ($this->candidateModelData->hired_at)
                    <span class="hrm-num ml-1 text-emerald-700">({{ __('candidates::common.labels.hired_on') }}: {{ $this->candidateModelData->hired_at->format('d.m.Y') }})</span>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <x-pill-button :href="route('personnel.show', $this->candidateModelData->hired_personnel_id)">{{ __('candidates::common.actions.open_hired_employee') }}</x-pill-button>
                @if ($this->candidateModelData->hire_order_no)
                    <x-pill-button :href="route('orders', ['search' => ['order_no' => $this->candidateModelData->hire_order_no]])">
                        {{ __('candidates::common.labels.hire_order_number', ['number' => $this->candidateModelData->hire_order_no]) }}
                    </x-pill-button>
                @endif
            </div>
        </div>
    @elseif ($this->canPrepareHireOrder())
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3">
            <p class="text-sm text-zinc-600">{{ __('candidates::common.hire.hint') }}</p>
            <x-pill-button variant="emerald" wire:click="requestHireOrder" data-candidate-hire-order>
                <x-icons.document-icon color="text-current" hover="text-current" />
                {{ __('candidates::common.actions.prepare_hire_order') }}
            </x-pill-button>
        </div>
    @endif
@endif

@if (isset($this->candidateModelData) && $this->candidateModelData?->latestApplication)
    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
        <div class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">
            {{ __('candidates::recruitment.titles.recruitment_context') }}
        </div>
        <div class="mt-3 grid gap-3 lg:grid-cols-3">
            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('candidates::recruitment.labels.total_applications') }}</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900">{{ (int) ($this->candidateModelData->applications_count ?? 0) }}</div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('candidates::recruitment.labels.active_applications') }}</div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-emerald-700">{{ (int) ($this->candidateModelData->active_applications_count ?? 0) }}</div>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">{{ __('candidates::recruitment.labels.latest_opening') }}</div>
                <div class="mt-2 text-sm font-semibold text-zinc-900">{{ $this->candidateModelData->latestApplication->opening?->title ?? '—' }}</div>
                <div class="mt-1 text-xs text-zinc-500">{{ $this->candidateModelData->latestApplication->opening?->requisition?->title ?? '—' }}</div>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                {{ __('candidates::recruitment.labels.latest_application') }}: {{ __('candidates::recruitment.stages.'.$this->candidateModelData->latestApplication->current_stage) }}
            </span>
            @if ($this->candidateModelData->latestApplication->opening)
                <span class="inline-flex rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">
                    {{ __('candidates::recruitment.labels.latest_opening') }}: {{ $this->candidateModelData->latestApplication->opening->title }}
                </span>
            @endif
        </div>
        @if ($this->candidateModelData->applications->isNotEmpty())
            <div class="mt-4 rounded-2xl border border-zinc-200 bg-white p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-zinc-400">
                    {{ __('candidates::recruitment.titles.recent_applications') }}
                </div>
                <div class="mt-3 space-y-2">
                    @foreach ($this->candidateModelData->applications->take(3) as $application)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-zinc-200 bg-zinc-50 px-3 py-3">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-zinc-900">{{ $application->opening?->title ?? '—' }}</div>
                                <div class="text-xs text-zinc-500">{{ $application->opening?->requisition?->title ?? '—' }}</div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-zinc-600">
                                    {{ __('candidates::recruitment.stages.'.$application->current_stage) }}
                                </span>
                                <a href="{{ route('candidates.applications.show', $application) }}" class="inline-flex h-8 items-center rounded-xl border border-zinc-200 bg-white px-3 text-[11px] font-semibold text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900">
                                    {{ __('candidates::recruitment.actions.open_application') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ route('candidates.applications.show', $this->candidateModelData->latestApplication) }}" class="inline-flex h-9 items-center rounded-xl border border-zinc-200 bg-white px-3 text-xs font-semibold text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900">
                {{ __('candidates::recruitment.actions.open_latest_application') }}
            </a>
            @if ($this->candidateModelData->latestApplication->opening)
                <a href="{{ route('candidates.openings.show', $this->candidateModelData->latestApplication->opening) }}" class="inline-flex h-9 items-center rounded-xl border border-zinc-200 bg-white px-3 text-xs font-semibold text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900">
                    {{ __('candidates::recruitment.actions.open_latest_opening') }}
                </a>
            @endif
            <a href="{{ route('candidates.applications', ['candidate' => $this->candidateModelData->id]) }}" class="inline-flex h-9 items-center rounded-xl border border-zinc-200 bg-white px-3 text-xs font-semibold text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900">
                {{ __('candidates::recruitment.actions.open_candidate_pipeline') }}
            </a>
        </div>
    </div>
@endif
