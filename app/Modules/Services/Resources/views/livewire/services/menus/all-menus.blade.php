<div class="flex flex-col" x-data>
    <div class="flex items-center justify-end space-x-2 action-section py-2">
        {{-- @can('manage-settings') --}}
        <x-button mode="primary" wire:click.prevent="openSideMenu('add-menu')" class="space-x-2">
            <x-icons.folder-plus-icon color="text-white" hover="text-gray-50"></x-icons.folder-plus-icon>
            <span>{{ __('Add menu') }}</span>
        </x-button>
        {{-- @endcan --}}
    </div>

    <div class="flex flex-col space-y-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('Name'), __('Color'), __('Order'), __('URL'), __('Active?'), 'action', 'action']">
                        @forelse ($_menus as $menu)
                            <tr>
                                <x-table.td>
                                    <div class="flex space-x-2 items-center">
                                        <div
                                            class="flex justify-center items-center p-2 rounded-xl bg-{{ $menu->color }}-100">
                                            {!! $menu->icon !!}
                                        </div>

                                        <span class="text-sm font-medium">
                                            {{ $menu->name }}
                                        </span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-slate-500 font-medium">
                                            {{ $menu->color }}
                                        </span>
                                        <span class="w-4 h-4 rounded-full bg-{{ $menu->color }}-500"></span>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <span class="text-sm font-normal text-gray-700">
                                        {{ $menu->order }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <span class="text-sm font-normal text-gray-700">
                                        {{ $menu->url }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center justify-start">
                                        <x-icons.check-icon size="w-8 h-8" :color="$menu->is_active ? 'text-green-400' : 'text-gray-300'"
                                            :hover="$menu->is_active ? 'text-green-500' : 'text-gray-400'"></x-icons.check-icon>
                                    </div>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    {{-- @can('manage-settings') --}}
                                    <a href="#" wire:click.prevent="openSideMenu('edit-menu',{{ $menu->id }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                        <x-icons.edit-icon color="text-slate-400"
                                            hover="text-slate-500"></x-icons.edit-icon>
                                    </a>
                                    {{-- @endcan --}}
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    {{-- @can('manage-settings') --}}
                                    <button wire:click.prevent = "setDeleteMenu({{ $menu->id }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 bg-rose-50 hover:bg-rose-100 hover:text-gray-700">
                                        <x-icons.delete-icon color="text-rose-500"
                                            hover="text-rose-600"></x-icons.delete-icon>
                                    </button>
                                    {{-- @endcan --}}
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    {{-- <x-empty :title="__('No users found.')" wire:click="$dispatch('openSideMenu','add-user')">
                                  {{ __('Add user') }}
                              </x-empty> --}}
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>

                </div>
            </div>
        </div>
    </div>

    <x-side-modal>
        @if ($showSideMenu == 'add-menu')
            <livewire:services.menus.add-menu wire:key="services-menu-add-modal" />
        @endif

        @if ($showSideMenu == 'edit-menu')
            <livewire:services.menus.edit-menu :menuModel="$modelName" :key="'services-menu-edit-modal-' . ($modelName ?? 'none')" />
        @endif
    </x-side-modal>

    <div class="">
        @auth
            <livewire:services.menus.delete-menu wire:key="services-menu-delete-modal" />
        @endauth
    </div>
</div>
