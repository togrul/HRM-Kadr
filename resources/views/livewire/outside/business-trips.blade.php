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
    <div class="flex flex-col space-y-4 px-6 py-4">
        <div class="flex justify-between items-center">
            <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
            </div>

            <div class="flex flex-col">
                <div class="flex space-x-4">
                    @can('export-business_trips')
                        <button wire:click.prevent="exportExcel"
                            class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-green-50"
                            type="button">
                            <x-icons.excel-icon />
                        </button>
                        <button wire:click="printPage"
                            class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-red-50"
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

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-2">
            <div class="flex flex-col xl:col-span-2">
                @php
                    $selectedName = array_key_exists('structure_id', $filter) ? $filter['structure_id']['name'] : '---';
                    $selectedId = array_key_exists('structure_id', $filter) ? $filter['structure_id']['id'] : -1;
                @endphp
                <x-select-list class="w-full" :title="__('Structure')" mode="gray" :selected="$selectedName" name="structureId">
                    <x-livewire-input @click.stop="open = true" mode="gray" name="searchStructure"
                        wire:model.live="searchStructure"></x-livewire-input>

                    <x-select-list-item wire:click="setData('filter','structure_id',null,'---',null)" :selected="'---' == $selectedName"
                        wire:model='filter.structure_id.id'>
                        ---
                    </x-select-list-item>
                    @foreach ($_structures as $_structure)
                        <x-select-list-item
                            wire:click="setData('filter','structure_id',null,'{{ trim($_structure->name) }}',{{ $_structure->id }})"
                            :selected="$_structure->id === $selectedId" wire:model='filter.structure_id.id'>
                            {{ $_structure->name }}
                        </x-select-list-item>
                    @endforeach
                </x-select-list>
            </div>
            <div class="flex flex-col xl:col-span-2">
                @php
                    $selectedName = array_key_exists('order_type_id', $filter)
                        ? $filter['order_type_id']['name']
                        : '---';
                    $selectedId = array_key_exists('order_type_id', $filter) ? $filter['order_type_id']['id'] : -1;
                @endphp
                <x-select-list class="w-full" :title="__('Order types')" mode="gray" :selected="$selectedName" name="orderTypeId">
                    <x-select-list-item wire:click="setData('filter','order_type_id',null,'---',null)" :selected="'---' == $selectedName"
                        wire:model='filter.order_type_id.id'>
                        ---
                    </x-select-list-item>
                    @foreach ($_order_types as $type)
                        <x-select-list-item
                            wire:click="setData('filter','order_type_id',null,'{{ trim($type->name) }}',{{ $type->id }})"
                            :selected="$type->id === $selectedId" wire:model='filter.order_type_id.id'>
                            {{ $type->name }}
                        </x-select-list-item>
                    @endforeach
                </x-select-list>
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
                <div class="flex space-x-1 items-center">
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
            <div class="flex flex-col space-y-1 w-full lg:col-span-2">
                <x-label for="filter.gender">{{ __('Status') }}</x-label>
                <div class="flex flex-row">
                    <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                        <input type="radio" class="form-radio" name="filter.business_trip_status"
                            wire:model="filter.business_trip_status" value="all">
                        <span class="ml-2 text-sm font-normal">{{ __('All') }}</span>
                    </label>
                    <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                        <input type="radio" class="form-radio" name="filter.business_trip_status"
                            wire:model="filter.business_trip_status" value="at_work">
                        <span class="ml-2 text-sm font-normal">{{ __('At work') }}</span>
                    </label>
                    <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                        <input type="radio" class="form-radio" name="filter.business_trip_status"
                            wire:model="filter.business_trip_status" value="in_business_trip">
                        <span class="ml-2 text-sm font-normal">{{ __('In business trip') }}</span>
                    </label>
                    <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                        <input type="radio" class="form-radio" name="filter.business_trip_status"
                            wire:model="filter.business_trip_status" value="deleted">
                        <span class="ml-2 text-sm font-normal">{{ __('Deleted') }}</span>
                    </label>
                </div>
            </div>
            <div class="flex space-x-2 items-end">
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
                                    <div class="flex flex-col space-y-1 items-start">
                                        <span
                                            class="text-slate-500 flex justify-center items-center text-sm font-medium rounded-lg drop-shadow-2xl">
                                            {{ $_bTrip->attributes['$rank']['value'] }}
                                        </span>
                                        <span class="text-sm font-medium text-slate-900">
                                            {{ $_bTrip->attributes['$fullname']['value'] }}
                                        </span>
                                        <span
                                            class="text-blue-500 text-sm font-medium bg-slate-100 px-2 py-1 rounded-lg">
                                            {{ $_bTrip->attributes['$structure']['value'] }}
                                        </span>
                                        @if ($activeBusinessTrip)
                                            <span
                                                class="text-green-700 flex justify-center items-center text-sm font-medium bg-green-200 px-2 py-1 rounded-lg">
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
                                            class="text-teal-500 text-sm font-medium bg-gray-100 px-2 py-1 rounded-lg">
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
                                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 bg-teal-50 rounded-lg text-gray-500 hover:bg-teal-100 hover:text-gray-700">
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
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase bg-teal-50 transition duration-300 rounded-lg text-gray-500 hover:bg-teal-100 hover:text-gray-700">
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
