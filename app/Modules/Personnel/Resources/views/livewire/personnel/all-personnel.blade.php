@php
    $counts = $this->statusCounts;
    $statusOptions = collect($this->getStatusFilters())
        ->reject(fn (array $filter): bool => isset($filter['permission']) && ! auth()->user()?->can($filter['permission']))
        ->map(fn (array $filter): array => [
            'id' => $filter['key'],
            'label' => $filter['label'].' · '.number_format($counts[$filter['key']] ?? 0, 0, ',', ' '),
        ])
        ->values()
        ->all();
    $positionOptions = $this->positions->map(fn ($position): array => ['id' => $position->id, 'label' => $position->name])->all();
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('personnel::common.titles.personnels')"
            :subtitle="__('personnel::common.labels.employee_count', ['count' => number_format($counts['all'], 0, ',', ' ')])"
        >
            <livewire:structure.sidebar :selected="$this->structure[0] ?? null" wire:key="personnel-structure-sidebar" />
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        collapsible-filters
        :filters-active="$search !== '' || $filters !== [] || $selectedPosition !== null"
        :title="__('personnel::common.titles.personnels')"
        :breadcrumb="__('personnel::common.titles.personnels')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="number_format($counts['all'], 0, ',', ' ')" :label="__('personnel::common.labels.employee')" />
            <x-page-header.stat :value="number_format($counts['at_work'], 0, ',', ' ')" :label="__('personnel::common.states.at_work')" tone="green" />
            <x-page-header.stat :value="number_format($counts['on_vacation'], 0, ',', ' ')" :label="__('personnel::common.states.in_vacation')" tone="violet" />
            <x-page-header.stat :value="number_format($counts['pending'], 0, ',', ' ')" :label="__('personnel::common.states.waiting_for_approval')" tone="amber" />
        </x-slot:stats>

        <x-slot:actions>
            @include('partials.personnel.action-buttons')
        </x-slot:actions>

        {{-- toolbar: search + status / position selects live inside the header card --}}
        <div class="flex flex-col gap-3">
            <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                <label class="relative w-full sm:max-w-[360px]">
                    <span class="sr-only">{{ __('ui::common.labels.search') }}</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="{{ __('personnel::common.placeholders.quick_search') }}"
                        class="{{ \App\Support\Ui\FieldStyles::input('pl-9') }}"
                    />
                </label>

                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.status')"
                    wire:key="personnel-status-filter"
                    :placeholder="__('personnel::common.labels.active')"
                    :clearable="false"
                    mode="gray"
                    class="w-full sm:w-52 [&>div]:mt-0"
                    wire:model.live="status"
                    :model="$statusOptions"
                />

                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.position')"
                    wire:key="personnel-position-filter"
                    :placeholder="__('personnel::common.labels.all_positions')"
                    mode="gray"
                    class="w-full sm:w-72 [&>div]:mt-0"
                    searchable
                    wire:model.live="selectedPosition"
                    :model="$positionOptions"
                />

                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.sort_by')"
                    wire:key="personnel-sort"
                    :placeholder="__('personnel::common.labels.sort_by_position')"
                    :clearable="false"
                    mode="gray"
                    class="w-full sm:w-48 [&>div]:mt-0"
                    wire:model.live="sort"
                    :model="[
                        ['id' => 'position', 'label' => __('personnel::common.labels.sort_by_position')],
                        ['id' => 'structure', 'label' => __('personnel::common.labels.sort_by_structure')],
                    ]"
                />
            </div>
        </div>
    </x-page-header>

    @php
        $tableKey = md5(json_encode([
            'status' => $this->status,
            'filters' => $this->filters,
            'structure' => $this->structure,
            'selectedPosition' => $this->selectedPosition,
            'search' => $this->search,
            'sort' => $this->sort,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    @endphp

    <livewire:personnel.table-panel
        :status="$this->status"
        :filters="$this->filters"
        :structure="$this->structure"
        :selected-position="$this->selectedPosition"
        :search="$this->search"
        :sort="$this->sort"
        :key="'personnel-table-'.$tableKey"
        lazy
    />

    @include('partials.personnel.modals')

    <x-datepicker :auto=false></x-datepicker>
</div>
