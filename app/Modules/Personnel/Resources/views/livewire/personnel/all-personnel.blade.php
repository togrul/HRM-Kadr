@php
    $counts = $this->statusCounts;
    $statusFilters = $this->getStatusFilters();
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('personnel::common.titles.personnels')"
            :subtitle="__('personnel::common.labels.employee_count', ['count' => number_format($counts['all'], 0, ',', ' ')])"
        >
            <x-context-panel.section :padded="true">
                @foreach ($statusFilters as $filter)
                    @continue (array_key_exists('permission', $filter) && ! auth()->user()?->can($filter['permission']))

                    <x-context-panel.item
                        wire:click.prevent="setStatus('{{ $filter['key'] }}')"
                        wire:loading.attr="disabled"
                        wire:target="setStatus"
                        :active="$status === $filter['key']"
                        :count="number_format($counts[$filter['key']] ?? 0, 0, ',', ' ')"
                    >{{ $filter['label'] }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            <livewire:structure.sidebar :selected="$this->structure[0] ?? null" wire:key="personnel-structure-sidebar" />
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
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

        {{-- toolbar: search + position chips live inside the header card --}}
        <div class="flex flex-col gap-3">
            <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                <label class="relative w-full sm:max-w-[360px]">
                    <span class="sr-only">{{ __('ui::common.labels.search') }}</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="{{ __('personnel::common.placeholders.quick_search') }}"
                        class="h-[34px] w-full rounded-[10px] border border-hairline bg-[#f4f4f5] pl-9 pr-3 text-[12.5px] text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-0"
                    />
                </label>

                <div class="min-w-0 flex-1">
                    @include('partials.personnel.position-filters')
                </div>
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
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    @endphp

    <livewire:personnel.table-panel
        :status="$this->status"
        :filters="$this->filters"
        :structure="$this->structure"
        :selected-position="$this->selectedPosition"
        :search="$this->search"
        :key="'personnel-table-'.$tableKey"
        lazy
    />

    @include('partials.personnel.modals')

    <x-datepicker :auto=false></x-datepicker>
</div>
