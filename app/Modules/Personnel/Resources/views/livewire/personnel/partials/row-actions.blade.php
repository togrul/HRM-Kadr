<x-table.td :isButton="true" style="text-align: center !important;">
    <div class="flex items-center space-x-2">
        @if ($status != 'deleted')
            @can('edit-personnels')
                <a href="#"
                    wire:click="openSideMenu('edit-personnel',{{ $personnel->id }})"
                    class="flex items-center justify-center text-xs font-medium text-gray-500 uppercase bg-gray-100 rounded-lg w-9 h-9 hover:bg-gray-200 hover:text-gray-700">
                    <x-icons.profile-icon></x-icons.profile-icon>
                </a>
            @endcan
        @else
            @can('edit-personnels')
                <button wire:click="restoreData('{{ $personnel->tabel_no }}')"
                    class="flex items-center justify-center text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg w-9 h-9 bg-teal-50 hover:bg-teal-100 hover:text-gray-700">
                    <x-icons.recover color="text-teal-500" hover="text-teal-600"></x-icons.recover>
                </button>
            @endcan
        @endif

        @can('edit-personnels')
            <div class="relative inline-block text-left" x-data="{ showContextMenu: false, showTooltip: '' }">
                <div>
                    <button @click="showContextMenu = !showContextMenu"
                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg bg-blue-50 hover:bg-blue-100">
                        <x-icons.settings-icon></x-icons.settings-icon>
                    </button>
                </div>
                <div x-show="showContextMenu"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-90"
                    @click.outside="showContextMenu = false" @class([
                        'absolute right-0 z-10 mt-2 origin-bottom-right w-max rounded-md bg-white shadow-lg shadow-black/5 ring-1 ring-black ring-opacity-5 focus:outline-none',
                        'bottom-full' => $index >= 1,
                        'top-full' => $index < 1,
                    ])
                    role="menu" aria-orientation="vertical" aria-labelledby="menu-button"
                    tabindex="-1"
                >
                    <div class="flex items-center divide-x divide-neutral-100" role="none">
                        <button
                            wire:click="openSideMenu('show-files','{{ $personnel->tabel_no }}')"
                            class="relative flex items-center justify-start w-full px-4 py-2 space-x-2 text-sm font-medium appearance-none hover:bg-slate-100"
                            @mouseover="showTooltip = 'files'"
                            @mouseleave="showTooltip = ''"
                        >
                            <x-icons.files-icon hover="text-blue-500" />
                            <div x-show="showTooltip == 'files'"
                                class="absolute z-10 px-2 py-1 text-sm text-center transition-all ease-out -translate-x-1/2 bg-white border rounded-lg shadow-lg opacity-100 -top-9 left-1/2 whitespace-nowrap shadow-black/5 border-neutral-200/70 text-neutral-600 dark:bg-neutral-900/80 dark:text-neutral-50"
                                role="tooltip"
                            >
                                {{ __('Files') }}
                            </div>
                        </button>
                        <a href="{{ route('print.personnel', $personnel->id) }}"
                            class="flex items-center justify-start w-full px-4 py-2 space-x-2 text-sm font-medium appearance-none hover:bg-slate-100"
                            target="_blank"
                            @mouseover="showTooltip = 'print'"
                            @mouseleave="showTooltip = ''"
                        >
                            <x-icons.print-outline-icon hover="text-blue-500" />
                            <div x-show="showTooltip == 'print'"
                                class="absolute z-10 px-2 py-1 text-sm text-center transition-all ease-out -translate-x-1/2 bg-white border shadow-lg opacity-100 -top-9 left-1/2 whitespace-nowrap shadow-black/5 border-neutral-200/70 text-neutral-600 dark:bg-neutral-900/80 dark:text-neutral-50"
                                role="tooltip"
                            >
                                {{ __('Print') }}
                            </div>
                        </a>
                        <a href="{{ route('print.cv', $personnel->id) }}"
                            class="flex items-center justify-start w-full px-4 py-2 space-x-2 text-sm font-medium appearance-none hover:bg-slate-100"
                            target="_blank"
                            @mouseover="showTooltip = 'cv'"
                            @mouseleave="showTooltip = ''"
                        >
                            <x-icons.cv-outline hover="text-blue-500" />
                            <div x-show="showTooltip == 'cv'"
                                class="absolute z-10 px-2 py-1 text-sm text-center transition-all ease-out -translate-x-1/2 bg-white border shadow-lg opacity-100 -top-9 left-1/2 whitespace-nowrap shadow-black/5 border-neutral-200/70 text-neutral-600 dark:bg-neutral-900/80 dark:text-neutral-50"
                                role="tooltip"
                            >
                                {{ __('CV') }}
                            </div>
                        </a>
                        <button
                            wire:click="openSideMenu('show-information','{{ $personnel->tabel_no }}')"
                            class="relative flex items-center justify-start w-full px-4 py-2 space-x-2 text-sm font-medium appearance-none hover:bg-slate-100"
                            @mouseover="showTooltip = 'information'"
                            @mouseleave="showTooltip = ''"
                        >
                            <x-icons.profile-outline-icon hover="text-blue-500" />
                            <div x-show="showTooltip == 'information'"
                                class="absolute z-10 px-2 py-1 text-sm text-center transition-all ease-out -translate-x-1/2 bg-white border shadow-lg opacity-100 -top-9 left-1/2 whitespace-nowrap shadow-black/5 border-neutral-200/70 text-neutral-600 dark:bg-neutral-900/80 dark:text-neutral-50"
                                role="tooltip"
                            >
                                {{ __('Information') }}
                            </div>
                        </button>
                        <button
                            wire:click="printInfo('{{ $personnel->id }}')"
                            class="relative flex items-center justify-start w-full px-4 py-2 space-x-2 text-sm font-medium appearance-none hover:bg-slate-100"
                            @mouseover="showTooltip = 'orders'"
                            @mouseleave="showTooltip = ''"
                        >
                            <x-icons.orders-icon hover="text-blue-500" />
                            <div x-show="showTooltip == 'orders'"
                                class="absolute z-10 px-2 py-1 text-sm text-center transition-all ease-out -translate-x-1/2 bg-white border rounded-lg shadow-lg opacity-100 -top-9 left-1/2 whitespace-nowrap shadow-black/5 border-neutral-200/70 text-neutral-600 dark:bg-neutral-900/80 dark:text-neutral-50"
                                role="tooltip"
                            >
                                {{ __('Orders') }}
                            </div>
                        </button>
                        <button
                            wire:click="openSideMenu('show-vacations','{{ $personnel->tabel_no }}')"
                            class="relative flex items-center justify-start w-full px-4 py-2 space-x-2 text-sm font-medium appearance-none hover:bg-slate-100"
                            @mouseover="showTooltip = 'vacations'"
                            @mouseleave="showTooltip = ''"
                        >
                            <x-icons.vacation-outline-icon hover="text-blue-500" />
                            <div x-show="showTooltip == 'vacations'"
                                class="absolute z-10 px-2 py-1 text-sm text-center transition-all ease-out -translate-x-1/2 bg-white border rounded-lg shadow-lg opacity-100 -top-9 left-1/2 whitespace-nowrap shadow-black/5 border-neutral-200/70 text-neutral-600 dark:bg-neutral-900/80 dark:text-neutral-50"
                                role="tooltip"
                            >
                                {{ __('Vacations') }}
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        @endcan

        @if ($status != 'deleted')
            @can('delete-personnels')
                <button wire:click="setDeletePersonnel('{{ $personnel->tabel_no }}')"
                    class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-100 hover:text-gray-700">
                    <x-icons.delete-icon></x-icons.delete-icon>
                </button>
            @endcan
        @else
            @can('edit-personnels')
                <button
                    wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                    wire:click="forceDeleteData('{{ $personnel->tabel_no }}')"
                    class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700">
                    <x-icons.force-delete></x-icons.force-delete>
                </button>
            @endcan
        @endif
    </div>
</x-table.td>
