{{-- The recruitment module's contextual panel: same card on every screen of the section. --}}
<x-context-panel
    :title="$panelTitle ?? __('candidates::common.titles.candidates')"
    :subtitle="$panelSubtitle ?? null"
>
    @include('candidates::livewire.candidates.partials.recruitment-context-panel', [
        'panelCounts' => $panelCounts ?? $this->recruitmentPanelCounts(),
        'panelOpenings' => $panelOpenings ?? null,
    ])
</x-context-panel>
