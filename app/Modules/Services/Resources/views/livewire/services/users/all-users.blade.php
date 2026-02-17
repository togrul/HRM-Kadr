<div
    class="flex flex-col"
    x-data
    x-init="
        const root = $el;
        const paintPaginator = () => {
            const paginator = root.querySelector('span[aria-current=page]>span');
            if (paginator) {
                paginator.classList.add('bg-blue-50', 'text-blue-600');
            }
        };
        paintPaginator();
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('commit', ({ component, succeed }) => {
                if (component.id !== $wire.__instance.id) return;
                succeed(() => queueMicrotask(paintPaginator));
            });
        }
    "
>

    <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
        <x-filter.nav>
            <x-filter.item wire:click.prevent="setStatus(1)" :active="$status === 1">
                {{ __('Active') }}
            </x-filter.item>
            <x-filter.item wire:click.prevent="setStatus(0)" :active="$status === 0">
                {{ __('De-active') }}
            </x-filter.item>
            <x-filter.item wire:click.prevent="setStatus(2)" :active="$status === 2">
                {{ __('Deleted') }}
            </x-filter.item>
        </x-filter.nav>


        <div class="flex items-center justify-center space-x-2 action-section">
            <x-button class="space-x-2" mode="gray" wire:click.prevent="resetFilter">
                <x-icons.refresh-icon color="text-gray-400" hover="text-gray-200"></x-icons.refresh-icon>
                <span>{{ __('Reset filter') }}</span>
            </x-button>
            {{-- @can('manage-settings') --}}
            <x-button class="space-x-2" mode="primary" wire:click.prevent="openSideMenu('add-user')">
                <x-icons.add-user color="text-white" hover="text-gray-50"></x-icons.add-user>
                <span>{{ __('Add User') }}</span>
            </x-button>
            {{-- @endcan --}}
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 my-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        <div>
            <x-label for="q">{{ __('User name or email') }}</x-label>
            <x-livewire-input id="q" name="q" mode="gray" wire:model.live="q"
                autocomplete="off"></x-livewire-input>
        </div>
    </div>

    <div class="flex flex-col space-y-2 mt-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <x-table.tbl :headers="[__('User'), __('Role'), __('Email'), __('Active?'), 'action', 'action']">
                        @forelse ($_users as $user)
                            <tr wire:key="user-row-{{ $user->id }}">
                                <x-table.td>
                                    <span class="text-sm font-medium">
                                        {{ $user->row_no }}. {{ $user->name }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    @if ($user->primary_role)
                                        <span
                                            class="bg-blue-100 text-blue-500 rounded-lg px-2 py-1 text-sm font-normal lowercase whitespace-no-wrap">
                                            {{ $user->primary_role }}
                                        </span>
                                    @endif
                                </x-table.td>

                                <x-table.td>
                                    <span class="text-sm font-normal text-gray-700">
                                        {{ $user->email }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex items-center justify-start">
                                        <x-icons.check-icon size="w-8 h-8" :color="$user->is_active ? 'text-green-400' : 'text-gray-300'"
                                            :hover="$user->is_active ? 'text-green-500' : 'text-gray-400'"></x-icons.check-icon>
                                    </div>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status == 2)
                                        <div class="flex flex-col text-xs font-medium">
                                            <div class="flex items-center space-x-1">
                                                <span class="text-gray-500">{{ __('Deleted date') }}:</span>
                                                <span class="text-black">{{ $user->deleted_at_label }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <span class="text-gray-500">{{ __('Deleted by') }}:</span>
                                                <span class="text-black">{{ $user->deleted_by_name }}</span>
                                            </div>
                                        </div>
                                    @else
                                        {{-- @can('manage-settings') --}}
                                        <a href="#"
                                            wire:click.prevent="openSideMenu('edit-user',{{ $user->id }})"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                            <x-icons.edit-icon color="text-slate-400"
                                                hover="text-slate-500"></x-icons.edit-icon>
                                        </a>
                                        {{-- @endcan --}}
                                    @endif
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if ($status == 2)
                                        <button wire:click="restoreData({{ $user->id }})"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700">
                                            <x-icons.recover color="text-teal-500"
                                                hover="text-teal-600"></x-icons.recover>
                                        </button>
                                        {{-- @role('admin') --}}
                                        <button
                                            onclick="confirm('Are you sure you want to remove this user?') || event.stopImmediatePropagation()"
                                            wire:click.prevent="forceDeleteData({{ $user->id }})"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700">
                                            <x-icons.force-delete color="text-rose-400"
                                                hover="text-rose-500"></x-icons.force-delete>
                                        </button>
                                        {{-- @endrole --}}
                                    @else
                                        {{-- @can('manage-settings') --}}
                                        <button wire:click.prevent = "setDeleteUser({{ $user->id }})"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 bg-rose-50 hover:bg-red-100 hover:text-gray-700">
                                            <x-icons.delete-icon color="text-rose-500"
                                                hover="text-rose-600"></x-icons.delete-icon>
                                        </button>
                                        {{-- @endcan --}}
                                    @endif
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

        <div>
            {{ $_users->links() }}
        </div>
    </div>

    {{-- @can('manage-settings') --}}
    <x-side-modal>
        @if ($showSideMenu == 'add-user')
            <livewire:services.users.add-user wire:key="services-user-add-modal" />
        @endif

        @if ($showSideMenu == 'edit-user')
            <livewire:services.users.edit-user :userModel="$modelName" :key="'services-user-edit-modal-' . ($modelName ?? 'none')" />
        @endif
    </x-side-modal>
    {{-- @endcan --}}

    <div class="">
        @auth
            <livewire:services.users.delete-user wire:key="services-user-delete-modal" />
        @endauth
    </div>
</div>
