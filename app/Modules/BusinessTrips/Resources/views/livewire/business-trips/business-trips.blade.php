<div class="flex flex-col" x-data x-init="paginator = document.querySelector('span[aria-current=page]>span');
if (paginator != null) {
    paginator.classList.add('bg-blue-50', 'text-blue-600')
}
Livewire.hook('message.processed', (message, component) => {
    const paginator = document.querySelector('span[aria-current=page]>span')
    if (
        ['gotoPage', 'previousPage', 'nextPage', 'filterSelected'].includes(message.updateQueue[0].payload.method) || ['openSideMenu', 'closeSideMenu', 'businessTripUpdated', 'filterResetted'].includes(message.updateQueue[0].payload.event) || ['search'].includes(message.updateQueue[0].name)
    ) {
        if (paginator != null) {
            paginator.classList.add('bg-green-100', 'text-green-600')
        }
    }
})">
    <div class="flex flex-col px-6 py-4 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex flex-col items-center justify-between px-2 py-2 bg-white sm:flex-row filter rounded-xl">
            </div>

            <div class="flex flex-col">
                <div class="flex space-x-4">
                    @can('export-business_trips')
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
                    :model="$this->structureOptions"
                    search-model="searchStructure"
                >
                </x-ui.select-dropdown>
            </div>
            <div class="flex flex-col xl:col-span-2">
                <x-ui.select-dropdown
                    :label="__('Order types')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="filter.order_type_id"
                    :model="$this->orderTypeOptions"
                />
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
                        wire:model.live="filter.date.min">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('filter.date.min', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                    <span>-</span>
                    <x-pikaday-input mode="gray" name="filter.date.max" format="Y-MM-DD"
                        wire:model.live="filter.date.max">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('filter.date.max', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                </div>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.location">{{ __('Location') }}</x-label>
                <x-livewire-input mode="gray" name="filter.location"
                    wire:model.defer="filter.location"></x-livewire-input>
            </div>
            <div class="flex flex-col w-full space-y-1 lg:col-span-2">
                <x-label for="filter.gender">{{ __('Status') }}</x-label>
                <div class="flex flex-row">
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.business_trip_status"
                            wire:model="filter.business_trip_status" value="all">
                        <span class="ml-2 text-sm font-normal">{{ __('All') }}</span>
                    </label>
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.business_trip_status"
                            wire:model="filter.business_trip_status" value="at_work">
                        <span class="ml-2 text-sm font-normal">{{ __('At work') }}</span>
                    </label>
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.business_trip_status"
                            wire:model="filter.business_trip_status" value="in_business_trip">
                        <span class="ml-2 text-sm font-normal">{{ __('In business trip') }}</span>
                    </label>
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="filter.business_trip_status"
                            wire:model="filter.business_trip_status" value="deleted">
                        <span class="ml-2 text-sm font-normal">{{ __('Deleted') }}</span>
                    </label>
                </div>
            </div>
            <div class="flex items-end space-x-2">
                <x-button mode="primary" wire:click="searchFilter">{{ __('Search') }}</x-button>
                <x-button mode="black" wire:click="resetFilter">{{ __('Reset') }}</x-button>
            </div>
        </div>

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

                    <x-table.tbl :headers="$this->getTableHeaders()">
                        @forelse ($this->businessTrips as $key => $_bTrip)
                            @php
                                $multi =
                                    $_bTrip->order->businessTrips->count() > 1 &&
                                    $_bTrip->order->order_type_id !=
                                        \App\Models\PersonnelBusinessTrip::FOREIGN_BUSINESS_TRIP;
                                $activeBusinessTrip =
                                    \Carbon\Carbon::parse($_bTrip->start_date) <= \Carbon\Carbon::now() &&
                                    \Carbon\Carbon::parse($_bTrip->end_date) > \Carbon\Carbon::now();
                            @endphp
                            <tr @class([
                                'bg-teal-50' => $activeBusinessTrip,
                            ])>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ ($this->businessTrips->currentpage() - 1) * $this->businessTrips->perpage() + $key + 1 }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col items-start space-y-1">
                                        <span
                                            class="flex items-center justify-center text-sm font-medium rounded-lg text-slate-500 drop-shadow-2xl">
                                            {{ $_bTrip->attributes['$rank']['value'] }}
                                        </span>
                                        <span class="text-sm font-medium text-slate-900">
                                            {{ $_bTrip->attributes['$fullname']['value'] }}
                                        </span>
                                        <span
                                            class="px-2 py-1 text-sm font-medium text-blue-500 rounded-lg bg-slate-100">
                                            {{ $_bTrip->attributes['$structure']['value'] }}
                                        </span>
                                        @if ($activeBusinessTrip)
                                            <span
                                                class="flex items-center justify-center px-2 py-1 text-sm font-medium text-green-700 bg-green-200 rounded-lg">
                                                {{ __('In business trip') }}
                                            </span>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('Start date') }}:</span>
                                            <span
                                                class="text-sky-500">{{ \Carbon\Carbon::parse($_bTrip->start_date)->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('End date') }}:</span>
                                            <span
                                                class="text-rose-500">{{ \Carbon\Carbon::parse($_bTrip->end_date)->format('d.m.Y') }}</span>
                                        </div>
                                        @if (\Illuminate\Support\Arr::get($search, 'business_trip_status', '') == 'deleted')
                                            <div class="flex flex-col text-sm font-medium">
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('Deleted date') }}:</span>
                                                    <span
                                                        class="text-black">{{ \Carbon\Carbon::parse($_bTrip->deleted_at)->format('d.m.Y H:i') }}</span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{ __('Deleted by') }}:</span>
                                                    <span
                                                        class="text-black">{{ $_bTrip->personDidDelete->name }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1 text-sm font-medium">
                                        <span
                                            class="px-2 py-1 text-sm font-medium text-teal-500 bg-gray-100 rounded-lg">
                                            {{ $_bTrip->order->orderType->name }}
                                        </span>
                                        <span class="text-sm font-medium text-slate-900">
                                            {{ $_bTrip->location }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('Order #') }}:</span>
                                            <a href="{{ route('orders', ['search' => ['order_no' => $_bTrip->order_no]]) }}"
                                                class="text-blue-600">{{ $_bTrip->order_no }}</a>
                                            @if ($multi)
                                                <button
                                                    wire:click="printBusinessTripDocument('{{ $_bTrip->id }}',{{ $multi }})"
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg bg-teal-50 hover:bg-teal-100 hover:text-gray-700">
                                                    @include('components.icons.document-icon', [
                                                        'color' => 'text-teal-500',
                                                        'hover' => 'text-teal-600',
                                                    ])
                                                </button>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('Given by') }}:</span>
                                            <span class="text-black">{{ $_bTrip->order_given_by }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{ __('Given date') }}:</span>
                                            <span
                                                class="text-black">{{ \Carbon\Carbon::parse($_bTrip->order_date)->format('d.m.Y') }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if (!$multi)
                                        @can('export-business_trips')
                                            <button
                                                wire:click="printBusinessTripDocument('{{ $_bTrip->id }}',{{ $multi }})"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg bg-teal-50 hover:bg-teal-100 hover:text-gray-700">
                                                @include('components.icons.document-icon', [
                                                    'color' => 'text-teal-500',
                                                    'hover' => 'text-teal-600',
                                                ])
                                            </button>
                                        @endcan
                                    @endif
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
            {{ $this->businessTrips->links() }}
        </div>

        <x-datepicker :auto=false></x-datepicker>
    </div>
