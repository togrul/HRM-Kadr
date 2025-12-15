<div class="flex flex-col" x-data x-init="paginator = document.querySelector('span[aria-current=page]>span');
if (paginator != null) {
    paginator.classList.add('bg-blue-50', 'text-blue-600')
}
Livewire.hook('message.processed', (message, component) => {
    const paginator = document.querySelector('span[aria-current=page]>span')
    if (
        ['gotoPage', 'previousPage', 'nextPage', 'filterSelected'].includes(message.updateQueue[0].payload.method) || ['openSideMenu', 'closeSideMenu', 'vacationUpdated', 'filterResetted'].includes(message.updateQueue[0].payload.event) || ['search'].includes(message.updateQueue[0].name)
    ) {
        if (paginator != null) {
            paginator.classList.add('bg-green-100', 'text-green-600')
        }
    }
})">
    <div class="flex flex-col px-6 py-4 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex flex-row items-center justify-start px-2 py-2 space-x-2 rounded-xl">
                <x-label>{{ __('Year') }} </x-label>
                <select name="selectedYear" id="selectedYear" wire:model.live="selectedYear" @disabled(!empty($filter['date']['min'] ?? null) || !empty($filter['date']['max'] ?? null))
                    class="block w-full text-base text-white rounded-md bg-neutral-800 border-slate-600 focus:outline-none focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected($year == $selectedYear)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col">
                <div class="flex space-x-4">
                    @can('export-vacations')
                        <button wire:click.prevent="exportExcel"
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-green-50"
                            type="button">
                            <x-icons.excel-icon />
                        </button>
                        <button wire:click="printPage"
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-red-50"
                            type="button">
                            @include('components.icons.print-file', [
                                'color' => 'text-rose-500',
                                'hover' => 'text-rose-600',
                                'size' => 'w-8 h-8',
                            ])
                        </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            <div class="flex flex-col xl:col-span-2">
                <x-ui.select-dropdown
                    :label="__('Structure')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="filter.structure_id"
                    :model="$this->structureOptions()"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchStructure"
                        wire:model.live.debounce.300ms="searchStructure"
                        placeholder="{{ __('Search...') }}"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    ></x-livewire-input>
                </x-ui.select-dropdown>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.fullname">{{ __('Fullname') }}</x-label>
                <x-livewire-input mode="gray" name="filter.fullname"
                    wire:model.defer="filter.fullname"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.order_no">{{ __('Order #') }}</x-label>
                <x-livewire-input mode="gray" name="filter.order_no"
                    wire:model.defer="filter.order_no"></x-livewire-input>
            </div>
            <div class="flex flex-col lg:col-span-2">
                <x-label for="filter.date_range">{{ __('Date range') }}</x-label>
                <div class="flex items-center space-x-1">
                    <x-pikaday-input mode="gray" name="filter.date.min" format="Y-MM-DD"
                        wire:model.defer="filter.date.min">
                        <x-slot name="script">
                            $el.onchange = function () {
                            if ($el.value === '') {
                            @this.set('filter.date.min', null);
                            } else {
                            @this.set('filter.date.min', $el.value);
                            }
                            }
                        </x-slot>
                    </x-pikaday-input>
                    <span>-</span>
                    <x-pikaday-input mode="gray" name="filter.date.max" format="Y-MM-DD"
                        wire:model.defer="filter.date.max">
                        <x-slot name="script">
                            $el.onchange = function () {
                            if ($el.value === '') {
                            @this.set('filter.date.max', null); // Explicitly set to null when cleared
                            } else {
                            @this.set('filter.date.max', $el.value);
                            }
                            }
                        </x-slot>
                    </x-pikaday-input>
                </div>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.vacation_places">{{ __('Location') }}</x-label>
                <x-livewire-input mode="gray" name="filter.vacation_places"
                    wire:model.defer="filter.vacation_places"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.duration">{{ __('Duration') }}</x-label>
                <x-livewire-input type="number" mode="gray" name="filter.duration"
                    wire:model.defer="filter.duration"></x-livewire-input>
            </div>
            <div class="flex flex-col w-full space-y-1 lg:col-span-2">
                <x-label for="filter.gender">{{ __('Status') }}</x-label>
                <div class="flex flex-row">
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.vacation_status"
                            wire:model="filter.vacation_status" value="all">
                        <span class="ml-2 text-sm font-normal">{{ __('All') }}</span>
                    </label>
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.vacation_status"
                            wire:model="filter.vacation_status" value="at_work">
                        <span class="ml-2 text-sm font-normal">{{ __('At work') }}</span>
                    </label>
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.vacation_status"
                            wire:model="filter.vacation_status" value="in_vacation">
                        <span class="ml-2 text-sm font-normal">{{ __('In vacation') }}</span>
                    </label>
                </div>
            </div>
            <div class="flex items-end space-x-2">
                <x-button mode="primary" wire:click="searchFilter()">{{ __('Search') }}</x-button>
                <x-button mode="black" wire:click="resetFilter">{{ __('Reset') }}</x-button>
            </div>
        </div>

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

                    <x-table.tbl :headers="$this->getTableHeaders()">
                        @forelse ($this->vacations as $key => $_vacation)
                            @php
                                $activeVacation =
                                    $_vacation->start_date <= \Carbon\Carbon::now() &&
                                    $_vacation->return_work_date > \Carbon\Carbon::now();
                            @endphp
                            <tr @class([
                                'bg-teal-50' => $activeVacation,
                            ])>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ ($this->vacations->currentpage() - 1) * $this->vacations->perpage() + $key + 1 }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col items-start space-y-1">
                                        <span
                                            class="flex items-center justify-center text-sm font-medium rounded-lg text-slate-500 drop-shadow-2xl">
                                            {{ $_vacation->personnel?->latestRank?->rank->name }}
                                        </span>
                                        <span class="text-sm font-medium text-slate-900">
                                            {{ $_vacation->personnel?->fullname }}
                                        </span>
                                        @if ($activeVacation)
                                            <span
                                                class="flex items-center justify-center px-2 py-1 text-sm font-medium text-green-700 bg-green-200 rounded-lg">
                                                {{ __('In vacation') }}
                                            </span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span
                                            class="px-2 py-1 text-sm font-medium text-blue-500 rounded-lg bg-slate-100">
                                            {{ $_vacation->personnel?->structure?->name }}
                                        </span>
                                        <span
                                            class="px-2 py-1 text-sm font-medium rounded-lg text-rose-500 bg-slate-100">
                                            {{ $_vacation->personnel?->position_label }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('Duration') }}:</span>
                                            <span class="text-teal-500">{{ $_vacation->duration }}
                                                {{ __('day') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('Start date') }}:</span>
                                            <span
                                                class="text-sky-500">{{ \Carbon\Carbon::parse($_vacation->start_date)->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('End date') }}:</span>
                                            <span
                                                class="text-rose-500">{{ \Carbon\Carbon::parse($_vacation->end_date)->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('Return work date') }}:</span>
                                            <span
                                                class="text-green-500">{{ \Carbon\Carbon::parse($_vacation->return_work_date)->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            @php
                                                $totalDays = max($_vacation->vacation_days_total, 1); // Avoid division by zero
                                                $percentage = ($_vacation->remaining_days * 100) / $totalDays;
                                                $color = match (true) {
                                                    $percentage < 30 => 'rose',
                                                    $percentage < 60 => 'blue',
                                                    default => 'teal', // Handles $percentage >= 60
                                                };
                                            @endphp
                                            <span
                                                class="flex-shrink-0 text-sm text-gray-500">{{ __('Vacation days') }}:
                                            </span>
                                            <div
                                                class="relative flex items-center justify-center w-20 h-2 overflow-hidden rounded-lg bg-slate-200">
                                                <div class="absolute left-0 h-full bg-{{ $color }}-500 shadow-sm"
                                                    style="width: {{ $percentage }}%"></div>
                                            </div>
                                            <span
                                                class="z-10 text-sm font-medium text-slate-900">{{ $_vacation->remaining_days }}/{{ $_vacation->vacation_days_total }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <span class="text-sm font-medium text-slate-900">
                                        {{ $_vacation->vacation_places }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('Order #') }}:</span>
                                            <a href="{{ route('orders', ['search' => ['order_no' => $_vacation->order_no]]) }}"
                                                class="text-blue-600">{{ $_vacation->order_no }}</a>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('Given by') }}:</span>
                                            <span class="text-black">{{ $_vacation->order_given_by }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('Given date') }}:</span>
                                            <span
                                                class="text-black">{{ \Carbon\Carbon::parse($_vacation->order_date)->format('d.m.Y') }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @can('export-vacations')
                                        <button wire:click="printVacationDocument('{{ $_vacation->id }}')"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg bg-teal-50 hover:bg-teal-50 hover:text-gray-700">
                                            @include('components.icons.document-icon', [
                                                'color' => 'text-teal-500',
                                                'hover' => 'text-teal-600',
                                            ])
                                        </button>
                                    @endcan
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty :rows="count($this->getTableHeaders())"></x-table.empty>
                        @endforelse
                    </x-table.tbl>

                </div>
            </div>
        </div>

        <div class="mt-2">
            {{ $this->vacations->links() }}
        </div>
    </div>

    <x-datepicker :auto=false></x-datepicker>
</div>
