<div class="flex flex-col" x-data>
    <div class="flex items-center justify-end space-x-2 action-section py-2">
        {{-- @can('manage-settings') --}}
        <x-button mode="primary" wire:click.prevent="openSideMenu('add-menu')" class="space-x-2">
            <x-icons.folder-plus-icon color="text-white" hover="text-gray-50"></x-icons.folder-plus-icon>
            <span>{{ __('services::menus.actions.add_menu') }}</span>
        </x-button>
        {{-- @endcan --}}
    </div>

    <div class="flex flex-col space-y-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('services::common.labels.name'), __('services::common.labels.color'), __('services::common.labels.order'), __('services::common.labels.url'), __('services::common.labels.active_question'), __('services::common.labels.action'), __('services::common.labels.action')]">
                        @forelse ($_menus as $menu)
                            <tr>
                                <x-table.td>
                                    <div class="flex space-x-2 items-center">
                                        <div
                                            class="flex justify-center items-center p-2 rounded-xl bg-{{ $menu->color }}-100">
                                            <x-dynamic-component
                                                  :component="$this->displayMenuIconComponent($menu)"
                                                  color="text-zinc-600"
                                                  size="w-6 h-6"
                                              />
                                        </div>

                                        <span class="text-sm font-medium">
                                            {{ $this->displayMenuName($menu) }}
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
                                    <x-action-button wire:click.prevent="openSideMenu('edit-menu',{{ $menu->id }})"
                                        class="h-9 w-9 bg-zinc-100 hover:bg-zinc-200"
                                        :title="__('services::menus.titles.edit')">
                                        <x-icons.edit-icon color="text-slate-400"
                                            hover="text-slate-500"></x-icons.edit-icon>
                                    </x-action-button>
                                    {{-- @endcan --}}
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    {{-- @can('manage-settings') --}}
                                    <x-action-button wire:click.prevent = "setDeleteMenu({{ $menu->id }})"
                                        class="h-9 w-9 bg-rose-50 hover:bg-rose-100"
                                        :title="__('services::menus.titles.delete')">
                                        <x-icons.delete-icon color="text-rose-500"
                                            hover="text-rose-600"></x-icons.delete-icon>
                                    </x-action-button>
                                    {{-- @endcan --}}
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
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
