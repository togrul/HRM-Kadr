<div class="space-y-6">
    <div class="rounded-[28px] border border-zinc-200 bg-zinc-50 p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <x-ui.field-label as="div" class="tracking-tight text-zinc-500">{{ __('personnel::common.titles.personnels') }} · {{ __('personnel::portfolio.title') }}</x-ui.field-label>
                <h2 class="text-3xl font-semibold tracking-tight text-zinc-950">{{ $this->personnel->fullname }}</h2>
                <p class="text-sm text-zinc-500">{{ __('personnel::portfolio.description') }}</p>
            </div>

            <a href="{{ route('docs.guide', ['focus' => 'professional-portfolio']) }}#professional-portfolio-module" class="inline-flex items-center bg-white rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50">
                {{ __('personnel::portfolio.actions.open_docs') }}
            </a>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-zinc-200 bg-white backdrop-blur-2xl px-4 py-4">
                <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.summary.verified_events') }}</x-ui.field-label>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">{{ $summary['verified_events'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white backdrop-blur-2xl px-4 py-4">
                <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.summary.verified_media') }}</x-ui.field-label>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">{{ $summary['verified_media'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white backdrop-blur-2xl px-4 py-4">
                <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.summary.verified_projects') }}</x-ui.field-label>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">{{ $summary['verified_projects'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white backdrop-blur-2xl px-4 py-4">
                <x-ui.field-label as="div" class="tracking-tight">{{ __('personnel::portfolio.summary.speaker_events') }}</x-ui.field-label>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">{{ $summary['speaker_events'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-[28px] border border-zinc-200 bg-zinc-50/60 p-5 shadow-sm">
        <div class="flex flex-wrap gap-2">
            @php
                $tabs = ['events', 'media', 'projects', 'timeline'];
                if ($this->canViewAnalytics()) {
                    $tabs[] = 'analytics';
                }
            @endphp
            @foreach ($tabs as $tab)
                <button type="button" wire:click="setActiveTab('{{ $tab }}')" wire:loading.attr="disabled" wire:target="setActiveTab"
                    class="{{ $activeTab === $tab ? 'bg-zinc-950 text-white shadow-sm' : 'border border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50' }} rounded-2xl px-4 py-2.5 text-sm font-semibold tracking-tight transition">
                    {{ __('personnel::portfolio.tabs.'.$tab) }}
                </button>
            @endforeach
        </div>

        <div class="mt-6">
            @if ($activeTab === 'events')
                <livewire:personnel.professional-portfolio.events-manager :personnelId="$personnelId" :key="'portfolio-events-'.$personnelId" lazy />
            @elseif ($activeTab === 'media')
                <livewire:personnel.professional-portfolio.media-manager :personnelId="$personnelId" :key="'portfolio-media-'.$personnelId" lazy />
            @elseif ($activeTab === 'projects')
                <livewire:personnel.professional-portfolio.projects-manager :personnelId="$personnelId" :key="'portfolio-projects-'.$personnelId" lazy />
            @elseif ($activeTab === 'analytics')
                <livewire:personnel.professional-portfolio.analytics-panel :personnelId="$personnelId" :key="'portfolio-analytics-'.$personnelId" lazy />
            @else
                <livewire:personnel.professional-portfolio.timeline-panel :personnelId="$personnelId" :key="'portfolio-timeline-'.$personnelId" lazy />
            @endif
        </div>
    </div>
</div>
