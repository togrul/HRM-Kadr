<div class="flex flex-col"
    x-data
    x-init="
    paginator = document.querySelector('span[aria-current=page]>span');
    if(paginator != null)
    {
        paginator.classList.add('bg-blue-50','text-blue-600')
    }
    Livewire.hook('message.processed', (message,component) => {
        const paginator = document.querySelector('span[aria-current=page]>span')
        if(
            ['gotoPage','previousPage','nextPage','selectOrder'].includes(message.updateQueue[0].payload.method)
            || ['openSideMenu','closeSideMenu','orderAdded','selectOrder','orderWasDeleted'].includes(message.updateQueue[0].payload.event)
            || ['search'].includes(message.updateQueue[0].name)
        ){
            if(paginator != null)
            {
                paginator.classList.add('bg-green-100','text-green-600')
            }
        }
    })
">
    {{-- sidebar  --}}
    <x-slot name="sidebar">
       @livewire('structure.orders')
    </x-slot>
    {{-- end sidebar --}}

    <div class="flex flex-col space-y-4 px-6 py-4">
        <div class="flex justify-between items-center">
            <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
                <x-filter.nav>
                    <x-filter.item  wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                        {{ __('All') }}
                    </x-filter.item>
                    @foreach($this->statuses as $_status)
                        <x-filter.item wire:click.prevent="setStatus({{$_status->id}})" :active="$_status->id === intval($status)">
                            {{ $_status->name  }}
                        </x-filter.item>
                    @endforeach
                    @role('Admin')
                    <x-filter.item wire:click.prevent="setStatus('deleted')" :active="$status === 'deleted'">
                        {{ __('Deleted') }}
                    </x-filter.item>
                    @endrole
                </x-filter.nav>
            </div>
            <div class="flex flex-col">
                <div class="flex space-x-4">
                    @can('add-orders')
                    <button wire:click="openSideMenu('add-order',{{ $selectedOrder }})" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-blue-50" type="button">
                        @include('components.icons.add-file')
                    </button>
                    @endcan
                    @can('export-orders')
                    <button wire:click.prevent="exportExcel" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-green-50" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"  viewBox="0 0 50 50" class="w-7 h-7 fill-green-400 transition-all duration-300 hover:fill-green-500">
                            <path d="M 28.875 0 C 28.855469 0.0078125 28.832031 0.0195313 28.8125 0.03125 L 0.8125 5.34375 C 0.335938 5.433594 -0.0078125 5.855469 0 6.34375 L 0 43.65625 C -0.0078125 44.144531 0.335938 44.566406 0.8125 44.65625 L 28.8125 49.96875 C 29.101563 50.023438 29.402344 49.949219 29.632813 49.761719 C 29.859375 49.574219 29.996094 49.296875 30 49 L 30 44 L 47 44 C 48.09375 44 49 43.09375 49 42 L 49 8 C 49 6.90625 48.09375 6 47 6 L 30 6 L 30 1 C 30.003906 0.710938 29.878906 0.4375 29.664063 0.246094 C 29.449219 0.0546875 29.160156 -0.0351563 28.875 0 Z M 28 2.1875 L 28 6.53125 C 27.867188 6.808594 27.867188 7.128906 28 7.40625 L 28 42.8125 C 27.972656 42.945313 27.972656 43.085938 28 43.21875 L 28 47.8125 L 2 42.84375 L 2 7.15625 Z M 30 8 L 47 8 L 47 42 L 30 42 L 30 37 L 34 37 L 34 35 L 30 35 L 30 29 L 34 29 L 34 27 L 30 27 L 30 22 L 34 22 L 34 20 L 30 20 L 30 15 L 34 15 L 34 13 L 30 13 Z M 36 13 L 36 15 L 44 15 L 44 13 Z M 6.6875 15.6875 L 12.15625 25.03125 L 6.1875 34.375 L 11.1875 34.375 L 14.4375 28.34375 C 14.664063 27.761719 14.8125 27.316406 14.875 27.03125 L 14.90625 27.03125 C 15.035156 27.640625 15.160156 28.054688 15.28125 28.28125 L 18.53125 34.375 L 23.5 34.375 L 17.75 24.9375 L 23.34375 15.6875 L 18.65625 15.6875 L 15.6875 21.21875 C 15.402344 21.941406 15.199219 22.511719 15.09375 22.875 L 15.0625 22.875 C 14.898438 22.265625 14.710938 21.722656 14.5 21.28125 L 11.8125 15.6875 Z M 36 20 L 36 22 L 44 22 L 44 20 Z M 36 27 L 36 29 L 44 29 L 44 27 Z M 36 35 L 36 37 L 44 37 L 44 35 Z"></path>
                        </svg>
                    </button>

                    <button wire:click.prevent="wordEdit" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-rose-50" type="button">
                        @include('components.icons.print-file',['color' => 'text-rose-500','hover' => 'text-rose-600','size' => 'w-8 h-8'])
                    </button>
                    @endcan
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 lg:grid-cols-4">
            <div class="flex flex-col">
                <x-label for="search.order_no">{{ __('Search') }}</x-label>
                <x-livewire-input mode="gray" name="search.order_no" wire:model.live="search.order_no"></x-livewire-input>
            </div>
            <div class="flex flex-col lg:col-span-2">
                <x-label for="search.given_date">{{ __('Given date') }}</x-label>
                <div class="flex space-x-1 items-center">
                    <x-pikaday-input mode="gray" name="search.given_date.min" format="Y-MM-DD" wire:model.live="search.given_date.min">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('search.given_date.min', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                    <span>-</span>
                    <x-pikaday-input mode="gray" name="search.given_date.max" format="Y-MM-DD" wire:model.live="search.given_date.max">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('search.given_date.max', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                </div>
            </div>
            <div class="flex items-end">
                <x-button mode="black" wire:click="resetFilter">{{ __('Reset') }}</x-button>
            </div>
        </div>

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <x-table.tbl :headers="[__('#'),__('Order #'),__('Given date'),__('Given by'),__('Status'),'action','action','action']">
                        @forelse ($orders as $key => $_order)
                            <tr @class([
                                'bg-white' => $_order->status_id != 30,
                                'bg-rose-50' => $_order->status_id == 30
                            ])>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ ($orders->currentpage()-1) * $orders->perpage() + $key + 1 }}
                                   </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-medium text-blue-500">
                                            {{ $_order->order_no }}
                                        </span>
                                        <div class="flex space-x-1 items-center">
                                             <span class="text-sm shadow-sm font-medium px-3 py-1 rounded-lg bg-teal-100 text-teal-600 flex justify-center items-center">
                                              {{ $_order->order->name }}
                                            </span>
                                            <span>
                                                <svg class="w-4 h-4" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"></path>
                                                </svg>
                                            </span>
                                            <span class="text-sm shadow-sm font-medium px-2 py-1 rounded-lg bg-slate-100 text-slate-500 flex justify-center items-center">
                                              {{ $_order->orderType->name }}
                                            </span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-medium text-gray-600">
                                            {{  \Carbon\Carbon::parse($_order->given_date)->format('d.m.Y') }}
                                       </span>
                                        @role('Admin')
                                        @if($status == 'deleted')
                                            <div class="flex flex-col text-xs font-medium">
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{__('Deleted date')}}:</span>
                                                    <span class="text-black">{{ \Carbon\Carbon::parse($_order->deleted_at)->format('d.m.Y H:i') }}</span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{__('Deleted by')}}:</span>
                                                    <span class="text-black">{{$_order->personDidDelete->name}}</span>
                                                </div>
                                            </div>
                                        @endif
                                        @endrole
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ $_order->given_by }}
                                       </span>
                                        <span class="text-sm font-medium text-gray-500">
                                            {{ $_order->given_by_rank }}
                                       </span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    @php
                                        $_color = [
                                         10 => 'slate',
                                         20 => 'green',
                                         30 => 'rose',
                                    ];
                                    @endphp
                                    <span class="text-sm font-medium px-3 py-2 rounded-lg bg-{{ $_color[$_order->status_id] }}-100 text-{{ $_color[$_order->status_id] }}-500">{{ $_order->status->name }}</span>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @can('export-orders')
                                    @if($_order->order->blade != \App\Models\Order::BLADE_BUSINESS_TRIP)
                                        <button
                                            wire:click="printOrder('{{$_order->order_no}}')"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 bg-teal-50 hover:bg-teal-100 hover:text-gray-700"
                                        >
                                            @include('components.icons.print-file',['color' => 'text-teal-500','hover' => 'text-teal-600'])
                                        </button>
                                    @endif
                                    @endcan
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if($status != 'deleted')
                                         @can('edit-orders')
                                            <button wire:click="openSideMenu('edit-order','{{ $_order->order_no }}')" class="appearance-none flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                                @include('components.icons.document-icon')
                                            </button>
                                         @endcan
                                    @else
                                        @can('edit-orders')
                                            <button
                                                wire:click="restoreData('{{$_order->order_no}}')"
                                                class="flex items-center justify-center w-9 h-9 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 bg-teal-50 hover:bg-teal-100 hover:text-gray-700"
                                            >
                                                @include('components.icons.recover',['color' => 'text-teal-500','hover' => 'text-teal-600'])
                                            </button>
                                        @endcan
                                    @endif
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if($status != 'deleted')
                                        @can('delete-orders')
                                            <button
                                                wire:click="setDeleteOrder('{{$_order->order_no}}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                                            >
                                                @include('components.icons.delete-icon')
                                            </button>
                                         @endcan
                                    @else
                                        @can('delete-orders')
                                            <button
                                                wire:click="forceDeleteData('{{$_order->order_no}}')"
                                                wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                            >
                                                @include('components.icons.force-delete')
                                            </button>
                                        @endcan
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="flex justify-center items-center py-4">
                                        <span class="font-medium">{{ __('No information added') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>
        <div class="mt-2">
            {{ $orders->links() }}
        </div>
    </div>

    <x-side-modal>
        @can('add-orders')
            @if($showSideMenu == 'add-order')
                <livewire:orders.add-order :$selectedOrder />
            @endif
        @endcan
        @can('edit-orders')
            @if($showSideMenu == 'edit-order')
                <livewire:orders.edit-order :orderModel="$modelName" />
            @endif
        @endcan
    </x-side-modal>

    @can('delete-orders')
    <div>
        <livewire:orders.delete-order />
    </div>
    @endcan

    <x-datepicker :auto=false></x-datepicker>
</div>
