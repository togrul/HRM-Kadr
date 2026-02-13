<x-table.td :isButton="true" style="text-align: center !important;">
    <div class="flex items-center space-x-2">
        @if ($status != 'deleted')
            @can('edit-personnels')
                <a
                    href="#"
                    wire:click="openSideMenu('edit-personnel',{{ $personnel->id }})"
                    class="flex items-center justify-center text-xs font-medium text-gray-500 uppercase bg-gray-100 rounded-lg w-9 h-9 hover:bg-gray-200 hover:text-gray-700"
                    title="{{ __('Edit') }}"
                >
                    <x-icons.profile-icon></x-icons.profile-icon>
                </a>
            @endcan
        @else
            @can('edit-personnels')
                <button
                    wire:click="restoreData('{{ $personnel->tabel_no }}')"
                    class="flex items-center justify-center text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg w-9 h-9 bg-teal-50 hover:bg-teal-100 hover:text-gray-700"
                    title="{{ __('Restore') }}"
                >
                    <x-icons.recover color="text-teal-500" hover="text-teal-600"></x-icons.recover>
                </button>
            @endcan
        @endif

        @can('edit-personnels')
            <div class="relative inline-block text-left" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg bg-blue-50 hover:bg-blue-100"
                    title="{{ __('More actions') }}"
                >
                    <x-icons.settings-icon></x-icons.settings-icon>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.outside="open = false"
                    @class([
                        'absolute right-0 z-20 mt-2 origin-bottom-right rounded-md bg-white shadow-lg shadow-black/5 ring-1 ring-black ring-opacity-5 focus:outline-none',
                        'bottom-full' => $index >= 1,
                        'top-full' => $index < 1,
                    ])
                >
                    <div class="flex items-center divide-x divide-neutral-100" @click="open = false">
                        <button
                            wire:click="openSideMenu('show-files','{{ $personnel->tabel_no }}')"
                            class="px-4 py-2 hover:bg-slate-100"
                            title="{{ __('Files') }}"
                        >
                            <x-icons.files-icon hover="text-blue-500" />
                        </button>

                        <a
                            href="{{ route('print.personnel', $personnel->id) }}"
                            target="_blank"
                            class="px-4 py-2 hover:bg-slate-100"
                            title="{{ __('Print') }}"
                        >
                            <x-icons.print-outline-icon hover="text-blue-500" />
                        </a>

                        <a
                            href="{{ route('print.cv', $personnel->id) }}"
                            target="_blank"
                            class="px-4 py-2 hover:bg-slate-100"
                            title="{{ __('CV') }}"
                        >
                            <x-icons.cv-outline hover="text-blue-500" />
                        </a>

                        <button
                            wire:click="openSideMenu('show-information','{{ $personnel->tabel_no }}')"
                            class="px-4 py-2 hover:bg-slate-100"
                            title="{{ __('Information') }}"
                        >
                            <x-icons.profile-outline-icon hover="text-blue-500" />
                        </button>

                        <button
                            wire:click="printInfo('{{ $personnel->id }}')"
                            class="px-4 py-2 hover:bg-slate-100"
                            title="{{ __('Orders') }}"
                        >
                            <x-icons.orders-icon hover="text-blue-500" />
                        </button>

                        <button
                            wire:click="openSideMenu('show-vacations','{{ $personnel->tabel_no }}')"
                            class="px-4 py-2 hover:bg-slate-100"
                            title="{{ __('Vacations') }}"
                        >
                            <x-icons.vacation-outline-icon hover="text-blue-500" />
                        </button>
                    </div>
                </div>
            </div>
        @endcan

        @if ($status != 'deleted')
            @can('delete-personnels')
                <button
                    wire:click="setDeletePersonnel('{{ $personnel->tabel_no }}')"
                    class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-100 hover:text-gray-700"
                    title="{{ __('Delete') }}"
                >
                    <x-icons.delete-icon></x-icons.delete-icon>
                </button>
            @endcan
        @else
            @can('edit-personnels')
                <button
                    wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                    wire:click="forceDeleteData('{{ $personnel->tabel_no }}')"
                    class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                    title="{{ __('Force delete') }}"
                >
                    <x-icons.force-delete></x-icons.force-delete>
                </button>
            @endcan
        @endif
    </div>
</x-table.td>

