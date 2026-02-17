<div
    class="flex flex-col"
    x-data
    x-init="
        const applyPaginatorTheme = (isUpdate = false) => {
            const paginator = document.querySelector('span[aria-current=page]>span');
            if (!paginator) return;
            paginator.classList.remove('bg-blue-50', 'text-blue-600', 'bg-green-100', 'text-green-600');
            paginator.classList.add(isUpdate ? 'bg-green-100' : 'bg-blue-50', isUpdate ? 'text-green-600' : 'text-blue-600');
        };

        applyPaginatorTheme(false);

        const currentComponentId = $wire.__instance?.id ?? $wire.$id ?? null;
        window.__ordersPaginatorHooks ??= {};

        if (currentComponentId && !window.__ordersPaginatorHooks[currentComponentId]) {
            window.__ordersPaginatorHooks[currentComponentId] = true;

            Livewire.hook('message.processed', (message, component) => {
                if (!component || component.id !== currentComponentId) return;

                const payload = message?.updateQueue?.[0]?.payload ?? {};
                const name = message?.updateQueue?.[0]?.name;
                const methods = ['gotoPage', 'previousPage', 'nextPage', 'selectOrder'];
                const events = ['openSideMenu', 'closeSideMenu', 'orderAdded', 'selectOrder', 'orderWasDeleted'];

                if (methods.includes(payload.method) || events.includes(payload.event) || name === 'search') {
                    applyPaginatorTheme(true);
                }
            });
        }
    "
>
    {{-- sidebar  --}}
    <x-slot name="sidebar">
        <livewire:structure.orders wire:key="orders-structure-sidebar" />
    </x-slot>
    {{-- end sidebar --}}

    <div class="flex flex-col px-6 py-4 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex flex-col items-center justify-between px-2 py-2 bg-white sm:flex-row filter rounded-xl">
                <x-filter.nav>
                    <x-filter.item wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                        {{ __('All') }}
                    </x-filter.item>
                    @foreach ($this->statuses as $_status)
                        <x-filter.item wire:click.prevent="setStatus({{ $_status->id }})" :active="$_status->id === intval($status)">
                            {{ $_status->name }}
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
                        <button wire:click="openSideMenu('add-order',{{ $selectedOrder }})"
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-blue-50"
                            type="button">
                            @include('components.icons.add-file')
                        </button>
                    @endcan
                    @can('export-orders')
                        <button wire:click.prevent="exportExcel"
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-green-50"
                            type="button">
                            <x-icons.excel-icon />
                        </button>

                        <button wire:click.prevent="wordEdit"
                            class="flex items-center justify-center w-12 h-12 transition-all duration-300 rounded-xl hover:bg-rose-50"
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

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex flex-col">
                <x-label for="search.order_no">{{ __('Search') }}</x-label>
                <x-livewire-input mode="gray" name="search.order_no"
                    wire:model.live="search.order_no"></x-livewire-input>
            </div>
            <div class="flex flex-col lg:col-span-2">
                <x-label for="search.given_date">{{ __('Given date') }}</x-label>
                <div class="flex items-center space-x-1">
                    <x-pikaday-input mode="gray" name="search.given_date.min" format="Y-MM-DD"
                        wire:model.live="search.given_date.min">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('search.given_date.min', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                    <span>-</span>
                    <x-pikaday-input mode="gray" name="search.given_date.max" format="Y-MM-DD"
                        wire:model.live="search.given_date.max">
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
                    <x-table.tbl :headers="$this->getTableHeaders()">
                        @forelse ($orders as $_order)
                            <tr wire:key="order-row-{{ $_order->id }}" @class([
                                '' => $_order->status_id != 30,
                                'bg-rose-50' => $_order->status_id == 30,
                            ])>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $_order->row_no }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded-lg w-max">
                                            {{ $_order->order_no }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                   <div class="flex items-center space-x-1">
                                            <span
                                                class="flex items-center justify-center px-3 py-1 text-xs font-medium text-teal-600 uppercase border border-teal-200 rounded-lg shadow-sm bg-teal-50">
                                                {{ $_order->order->name }}
                                            </span>
                                            <span>
                                                <svg class="w-4 h-4" data-slot="icon" fill="none" stroke-width="1.5"
                                                    stroke="currentColor" viewBox="0 0 24 24"
                                                    xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"></path>
                                                </svg>
                                            </span>
                                            <span
                                                class="flex items-center justify-center px-2 py-1 text-xs font-normal uppercase border rounded-lg shadow-sm medium border-neutral-200 bg-neutral-100 text-neutral-500">
                                                {{ $_order->orderType->name }}
                                            </span>
                                        </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-medium text-gray-600">
                                            {{ \Carbon\Carbon::parse($_order->given_date)->format('d.m.Y') }}
                                        </span>
                                        @role('Admin')
                                            @if ($status == 'deleted')
                                                <div class="flex flex-col text-xs font-medium">
                                                    <div class="flex items-center space-x-1">
                                                        <span class="text-gray-500">{{ __('Deleted date') }}:</span>
                                                        <span
                                                            class="text-black">{{ \Carbon\Carbon::parse($_order->deleted_at)->format('d.m.Y H:i') }}</span>
                                                    </div>
                                                    <div class="flex items-center space-x-1">
                                                        <span class="text-gray-500">{{ __('Deleted by') }}:</span>
                                                        <span
                                                            class="text-black">{{ $_order->personDidDelete->name }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endrole
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-0">
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ $_order->given_by }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-500">
                                            {{ $_order->given_by_rank }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                         <x-status :status-id="$_order->status_color_id" :label="$_order->status->name"></x-status>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @can('export-orders')
                                        @if ($_order->order->blade != \App\Models\Order::BLADE_BUSINESS_TRIP)
                                            <button wire:click="printOrder('{{ $_order->order_no }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg bg-teal-50 hover:bg-teal-100 hover:text-gray-700">
                                                @include('components.icons.print-file', [
                                                    'color' => 'text-teal-500',
                                                    'hover' => 'text-teal-600',
                                                ])
                                            </button>
                                        @endif
                                    @endcan
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status != 'deleted')
                                        @can('edit-orders')
                                            <button wire:click="openSideMenu('edit-order','{{ $_order->order_no }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase bg-gray-100 rounded-lg appearance-none hover:bg-gray-200 hover:text-gray-700">
                                                @include('components.icons.document-icon')
                                            </button>
                                        @endcan
                                    @else
                                        @can('edit-orders')
                                            <button wire:click="restoreData('{{ $_order->order_no }}')"
                                                class="flex items-center justify-center text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg w-9 h-9 bg-teal-50 hover:bg-teal-100 hover:text-gray-700">
                                                @include('components.icons.recover', [
                                                    'color' => 'text-teal-500',
                                                    'hover' => 'text-teal-600',
                                                ])
                                            </button>
                                        @endcan
                                    @endif
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status != 'deleted')
                                        @can('delete-orders')
                                            <button wire:click="setDeleteOrder('{{ $_order->order_no }}')"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-100 hover:text-gray-700">
                                                @include('components.icons.delete-icon')
                                            </button>
                                        @endcan
                                    @else
                                        @can('delete-orders')
                                            <button wire:click="forceDeleteData('{{ $_order->order_no }}')"
                                                wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                                class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700">
                                                @include('components.icons.force-delete')
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
            {{ $orders->links() }}
        </div>
    </div>

    <x-side-modal>
        @can('add-orders')
            @if ($showSideMenu == 'add-order')
                <livewire:orders.add-order :$selectedOrder :key="'order-add-modal-' . ($selectedOrder ?? 'none')" />
            @endif
        @endcan
        @can('edit-orders')
            @if ($showSideMenu == 'edit-order')
                <livewire:orders.edit-order :orderModel="$modelName" :key="'order-edit-modal-' . ($modelName ?? 'none')" />
            @endif
        @endcan
    </x-side-modal>

    @can('delete-orders')
        <div>
            <livewire:orders.delete-order wire:key="order-delete-modal" />
        </div>
    @endcan

    <x-datepicker :auto=false></x-datepicker>
</div>
