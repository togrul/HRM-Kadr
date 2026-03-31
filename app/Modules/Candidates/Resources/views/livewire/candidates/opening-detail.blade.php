<div class="flex flex-col gap-6 px-6 py-4">
    @include('candidates::livewire.candidates.partials.recruitment-nav')

    <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="flex flex-col gap-5 border-b border-slate-200 pb-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                    {{ __('candidates::recruitment.titles.opening_detail') }}
                </div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ $opening->title }}</h1>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">{{ $this->recruitmentPackLabel($opening->profile_pack) }}</span>
                    <span class="inline-flex rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">{{ $this->recruitmentStatusLabel($opening->status) }}</span>
                    <span class="inline-flex rounded-full bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700">{{ $opening->headcount }} {{ __('candidates::recruitment.labels.headcount_short') }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('candidates.applications', ['opening' => $opening->id]) }}" class="inline-flex h-11 items-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                    {{ __('candidates::recruitment.actions.open_pipeline') }}
                </a>
                @can('create', App\Models\CandidateApplication::class)
                    <button type="button" wire:click="openSideMenu('add-application', {{ $opening->id }})" class="inline-flex h-11 items-center rounded-2xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                        {{ __('candidates::recruitment.actions.add_application') }}
                    </button>
                @endcan
                @if ($opening->requisition)
                    <a href="{{ route('candidates.requisitions.show', $opening->requisition) }}" class="inline-flex h-11 items-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                        {{ __('candidates::recruitment.actions.open_requisitions') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-4">
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.structure') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ $opening->structure?->name ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $opening->position?->name ?? '—' }}</div>
            </div>
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.owner_summary') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ $opening->owner?->name ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $opening->creator?->name ?? '—' }}</div>
            </div>
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.requisition_link') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ $opening->requisition?->title ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ $this->recruitmentStatusLabel($opening->requisition?->status) }}</div>
            </div>
            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.timeline') }}</div>
                <div class="mt-3 text-base font-semibold text-slate-900">{{ optional($opening->published_at)->format('d.m.Y') ?? '—' }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ optional($opening->closes_at)->format('d.m.Y') ?? '—' }}</div>
            </div>
        </div>
    </section>

    <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                    {{ __('candidates::recruitment.titles.pipeline_summary') }}
                </div>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                    {{ __('candidates::recruitment.titles.pipeline_summary') }}
                </h2>
            </div>
            <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                {{ $opening->applications->count() }} {{ __('candidates::recruitment.labels.applications') }}
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->stageSummary as $stage)
                <div class="rounded-[24px] border {{ $stage['terminal'] ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50' }} p-4">
                    <div class="text-[11px] font-semibold uppercase tracking-tight {{ $stage['terminal'] ? 'text-rose-400' : 'text-slate-400' }}">
                        {{ __('candidates::recruitment.labels.pipeline_stage') }}
                    </div>
                    <div class="mt-3 text-lg font-semibold tracking-tight text-slate-900">{{ $stage['label'] }}</div>
                    <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $stage['count'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
        <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
            {{ __('candidates::recruitment.titles.recent_applications') }}
        </div>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
            {{ __('candidates::recruitment.titles.recent_applications') }}
        </h2>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @forelse ($opening->applications as $application)
                <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $this->recruitmentStageLabel($application->current_stage) }}</span>
                        <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ $application->status }}</span>
                    </div>
                    <h3 class="mt-4 text-xl font-semibold tracking-tight text-slate-900">{{ $application->candidate?->fullname ?? '—' }}</h3>
                    <div class="mt-4 grid gap-3 text-sm text-slate-500 md:grid-cols-2">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.source') }}</div>
                            <div class="mt-1 text-slate-700">{{ $application->source?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.assigned_recruiter') }}</div>
                            <div class="mt-1 text-slate-700">{{ $application->assignedRecruiter?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.timeline') }}</div>
                            <div class="mt-1 text-slate-700">{{ optional($application->applied_at)->format('d.m.Y H:i') ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.decision') }}</div>
                            <div class="mt-1 text-slate-700">{{ $application->final_decision ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('candidates.applications.show', $application) }}" class="inline-flex h-9 items-center rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">
                            {{ __('candidates::recruitment.actions.open_application') }}
                        </a>
                    </div>
                </article>
            @empty
                <div class="rounded-[24px] border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                    {{ __('candidates::recruitment.empty.applications') }}
                </div>
            @endforelse
        </div>
    </section>

    <x-side-modal>
        @can('create', App\Models\CandidateApplication::class)
            @if ($showSideMenu === 'add-application')
                <livewire:candidates.add-application :openingModel="$modelName" :key="'candidate-add-application-modal-'.$modelName" />
            @endif
        @endcan
    </x-side-modal>
</div>
