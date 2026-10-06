@php
    // Counts and the open-vacancy list are optional: only the pipeline screen supplies them.
    $panelCounts = $panelCounts ?? [];
    $panelOpenings = $panelOpenings ?? null;

    $recruitmentTabs = [
        ['route' => 'candidates', 'label' => __('candidates::common.titles.candidates'), 'patterns' => ['candidates'], 'count' => $panelCounts['candidates'] ?? null],
        ['route' => 'candidates.applications', 'label' => __('candidates::recruitment.actions.open_pipeline'), 'patterns' => ['candidates.applications', 'candidates.applications.*'], 'count' => $panelCounts['applications'] ?? null],
        ['route' => 'candidates.requisitions', 'label' => __('candidates::recruitment.actions.open_requisitions'), 'patterns' => ['candidates.requisitions', 'candidates.requisitions.*'], 'count' => $panelCounts['requisitions'] ?? null],
        ['route' => 'candidates.openings', 'label' => __('candidates::recruitment.actions.open_openings'), 'patterns' => ['candidates.openings', 'candidates.openings.*'], 'count' => $panelCounts['openings'] ?? null],
        ['route' => 'candidates.analytics', 'label' => __('candidates::recruitment.actions.open_analytics'), 'patterns' => ['candidates.analytics'], 'count' => null],
    ];
@endphp

<x-context-panel.section>
    @foreach ($recruitmentTabs as $recruitmentTab)
        <x-context-panel.item
            :href="route($recruitmentTab['route'])"
            wire:navigate
            :active="request()->routeIs(...$recruitmentTab['patterns'])"
            :count="$recruitmentTab['count'] === null ? null : number_format($recruitmentTab['count'], 0, ',', ' ')"
        >{{ $recruitmentTab['label'] }}</x-context-panel.item>
    @endforeach

    <x-slot name="footer">
        <p class="text-[11px] leading-snug text-ink-faint">{{ __('candidates::recruitment.labels.transition_note') }}</p>
    </x-slot>
</x-context-panel.section>

@if ($panelOpenings !== null && $panelOpenings->isNotEmpty())
    <x-context-panel.section :title="__('candidates::recruitment.titles.open_openings')">
        @foreach ($panelOpenings as $panelOpening)
            <x-context-panel.item
                wire:key="recruitment-panel-opening-{{ $panelOpening->id }}"
                :href="route('candidates.openings.show', $panelOpening)"
                wire:navigate
                :note="__('candidates::recruitment.labels.headcount_counted', ['count' => (int) $panelOpening->headcount])
                    .' · '.__('candidates::recruitment.labels.applications_short', ['count' => (int) $panelOpening->applications_count])"
            >{{ $panelOpening->title }}</x-context-panel.item>
        @endforeach
    </x-context-panel.section>
@endif
