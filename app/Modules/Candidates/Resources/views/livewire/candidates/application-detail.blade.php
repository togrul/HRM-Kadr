<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        @include('candidates::livewire.candidates.partials.recruitment-panel', [
            'panelTitle' => __('candidates::recruitment.titles.application_detail'),
            'panelSubtitle' => $application->candidate?->fullname,
        ])
    @endteleport

    <div class="lg:hidden">@include('candidates::livewire.candidates.partials.recruitment-nav')</div>

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="$application->candidate?->fullname ?? '—'"
        :breadcrumb="__('candidates::recruitment.titles.pipeline')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            <x-pill-button :href="route('candidates.openings.show', $application->opening)" wire:navigate>
                {{ __('candidates::recruitment.actions.open_opening') }}
            </x-pill-button>
            <x-pill-button :href="route('candidates.applications', ['opening' => $application->job_opening_id])" wire:navigate>
                {{ __('candidates::recruitment.actions.open_pipeline') }}
            </x-pill-button>
        </x-slot:actions>

        <div class="flex flex-wrap items-center gap-2">
            <x-small-badge mode="secondary">{{ $this->recruitmentPackLabel($this->currentPack()) }}</x-small-badge>
            <x-small-badge mode="blue" dot>{{ $this->recruitmentStageLabel($application->current_stage) }}</x-small-badge>
            <x-small-badge :mode="$this->recruitmentStatusTone($application->status)" dot>{{ $this->recruitmentStatusLabel($application->status) }}</x-small-badge>
        </div>
    </x-page-header>

    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-fact-tile
                :label="__('candidates::recruitment.labels.opening')"
                :value="$application->opening?->title ?? '—'"
                :note="($application->opening?->structure?->name ?? '—').' / '.($application->opening?->position?->name ?? '—')"
            />
            <x-fact-tile
                :label="__('candidates::recruitment.labels.source')"
                :value="$application->source?->name ?? '—'"
                :note="$application->assignedRecruiter?->name ?? '—'"
            />
            <x-fact-tile
                :label="__('candidates::recruitment.labels.timeline')"
                :value="optional($application->applied_at)->format('d.m.Y H:i') ?? '—'"
                :note="optional($application->moved_at)->format('d.m.Y H:i') ?? '—'"
            />
            <x-fact-tile
                :label="__('candidates::recruitment.labels.decision')"
                :value="$application->final_decision ?? '—'"
                :note="$application->rejectionReason?->name ?? '—'"
            />
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
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
</div>
