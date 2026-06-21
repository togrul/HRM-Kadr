<div class="flex flex-col">
    {{-- sidebar  --}}
    <x-slot name="sidebar">
        <livewire:structure.sidebar wire:key="personnel-structure-sidebar" />
    </x-slot>
    {{-- end sidebar --}}

    <div class="">
        <div class="flex flex-col px-6 py-4 space-y-4">
            {{-- ===================== Premium header ===================== --}}
            <x-page-header :title="__('personnel::common.titles.personnels')">
                <x-slot:icon>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </x-slot:icon>
                <x-slot:actions>
                    @include('partials.personnel.action-buttons')
                </x-slot:actions>
            </x-page-header>

            {{-- status filters --}}
            <div class="flex items-center">
                @include('partials.personnel.status-filters')
            </div>

            {{-- Position Filters --}}
            @include('partials.personnel.position-filters')

            @php
                $tableKey = md5(json_encode([
                    'status' => $this->status,
                    'filters' => $this->filters,
                    'structure' => $this->structure,
                    'selectedPosition' => $this->selectedPosition,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            @endphp

            <livewire:personnel.table-panel
                :status="$this->status"
                :filters="$this->filters"
                :structure="$this->structure"
                :selected-position="$this->selectedPosition"
                :key="'personnel-table-'.$tableKey"
                lazy
            />
        </div>
    </div>

    @include('partials.personnel.modals')

    <x-datepicker :auto=false></x-datepicker>
</div>
