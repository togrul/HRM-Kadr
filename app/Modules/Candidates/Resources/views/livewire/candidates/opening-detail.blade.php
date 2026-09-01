@php
    $cardShell = 'overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    $tile = 'rounded-xl border border-hairline bg-[#fafafa] px-3.5 py-3';
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        @include('candidates::livewire.candidates.partials.recruitment-panel', [
            'panelTitle' => __('candidates::recruitment.titles.opening_detail'),
            'panelSubtitle' => $opening->title,
        ])
    @endteleport

    <div class="lg:hidden">@include('candidates::livewire.candidates.partials.recruitment-nav')</div>

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="$opening->title"
        :breadcrumb="__('candidates::recruitment.titles.openings')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            <x-pill-button :href="route('candidates.applications', ['opening' => $opening->id])" wire:navigate>
                {{ __('candidates::recruitment.actions.open_pipeline') }}
            </x-pill-button>
            @if ($opening->requisition)
                <x-pill-button :href="route('candidates.requisitions.show', $opening->requisition)" wire:navigate>
                    {{ __('candidates::recruitment.actions.open_requisitions') }}
                </x-pill-button>
            @endif
            @can('create', App\Models\CandidateApplication::class)
                <x-pill-button variant="primary" wire:click="openSideMenu('add-application', {{ $opening->id }})">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('candidates::recruitment.actions.add_application') }}
                </x-pill-button>
            @endcan
        </x-slot:actions>

        <div class="flex flex-wrap items-center gap-2">
            <x-small-badge mode="secondary">{{ $this->recruitmentPackLabel($opening->profile_pack) }}</x-small-badge>
            <x-small-badge :mode="$this->recruitmentStatusTone($opening->status)" dot>{{ $this->recruitmentStatusLabel($opening->status) }}</x-small-badge>
            <x-small-badge mode="blue">{{ $opening->headcount }} {{ __('candidates::recruitment.labels.headcount_short') }}</x-small-badge>
        </div>
    </x-page-header>

    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-fact-tile
                :label="__('candidates::recruitment.labels.structure')"
                :value="$opening->structure?->name ?? '—'"
                :note="$opening->position?->name ?? '—'"
            />
            <x-fact-tile
                :label="__('candidates::recruitment.labels.owner_summary')"
                :value="$opening->owner?->name ?? '—'"
                :note="$opening->creator?->name ?? '—'"
            />
            <x-fact-tile
                :label="__('candidates::recruitment.labels.requisition_link')"
                :value="$opening->requisition?->title ?? '—'"
                :note="$this->recruitmentStatusLabel($opening->requisition?->status)"
            />
            <x-fact-tile
                :label="__('candidates::recruitment.labels.timeline')"
                :value="optional($opening->published_at)->format('d.m.Y') ?? '—'"
                :note="optional($opening->closes_at)->format('d.m.Y') ?? '—'"
            />
        </div>

        {{-- stage summary --}}
        <section class="{{ $cardShell }}">
            <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ __('candidates::recruitment.titles.pipeline_summary') }}</h2>
                <x-small-badge mode="secondary">{{ $opening->applications->count() }} {{ __('candidates::recruitment.labels.applications') }}</x-small-badge>
            </div>
            <div class="grid gap-2 p-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($this->stageSummary as $stage)
                    <div @class([$tile, 'border-rose-200 bg-[#fff1f2]/60' => $stage['terminal']])>
                        <p class="truncate text-[12px] font-medium text-ink-soft">{{ $stage['label'] }}</p>
                        <p class="hrm-num mt-1 text-[19px] font-semibold tracking-[-0.03em] text-ink">{{ $stage['count'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- recent applications --}}
        <section class="{{ $cardShell }}">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ __('candidates::recruitment.titles.recent_applications') }}</h2>
            </div>
            <div class="grid gap-2 p-3 lg:grid-cols-2">
                @forelse ($opening->applications as $application)
                    <article class="{{ $tile }}">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <x-small-badge mode="blue" dot>{{ $this->recruitmentStageLabel($application->current_stage) }}</x-small-badge>
                            <x-small-badge :mode="$this->recruitmentStatusTone($application->status)">{{ $this->recruitmentStatusLabel($application->status) }}</x-small-badge>
                        </div>

                        <div class="mt-2.5 flex items-center gap-2.5">
                            <x-avatar :name="(string) ($application->candidate?->fullname ?? '')" size="sm" />
                            <p class="min-w-0 flex-1 truncate text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ $application->candidate?->fullname ?? '—' }}</p>
                            <a href="{{ route('candidates.applications.show', $application) }}" wire:navigate
                                title="{{ __('candidates::recruitment.actions.open_application') }}"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-ink-faint transition hover:bg-white hover:text-ink">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                            </a>
                        </div>

                        <div class="mt-2.5 grid gap-2 sm:grid-cols-2">
                            <div>
                                <p class="hrm-eyebrow">{{ __('candidates::recruitment.labels.source') }}</p>
                                <p class="truncate text-[12px] text-ink-soft">{{ $application->source?->name ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="hrm-eyebrow">{{ __('candidates::recruitment.labels.assigned_recruiter') }}</p>
                                <p class="truncate text-[12px] text-ink-soft">{{ $application->assignedRecruiter?->name ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="hrm-eyebrow">{{ __('candidates::recruitment.labels.timeline') }}</p>
                                <p class="hrm-num truncate text-[12px] text-ink-soft">{{ optional($application->applied_at)->format('d.m.Y H:i') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="hrm-eyebrow">{{ __('candidates::recruitment.labels.decision') }}</p>
                                <p class="truncate text-[12px] text-ink-soft">{{ $application->final_decision ?? '—' }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-hairline bg-[#fafafa] px-4 py-6 text-center text-[12.5px] text-ink-faint lg:col-span-2">
                        {{ __('candidates::recruitment.empty.applications') }}
                    </p>
                @endforelse
            </div>
        </section>
    </div>

    <x-side-modal>
        @can('create', App\Models\CandidateApplication::class)
            @if ($showSideMenu === 'add-application')
                <livewire:candidates.add-application :openingModel="$modelName" :key="'candidate-add-application-modal-'.$modelName" />
            @endif
        @endcan
    </x-side-modal>
</div>
