<div class="flex flex-col">
    {{-- sidebar  --}}
    <x-slot name="sidebar">
        <livewire:structure.sidebar wire:key="personnel-structure-sidebar" />
    </x-slot>
    {{-- end sidebar --}}

    <div class="">
        <div class="flex flex-col px-6 py-4 space-y-4">
            {{-- header section --}}
            <div class="flex items-center justify-between">
                @include('partials.personnel.status-filters')
                @include('partials.personnel.action-buttons')
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
