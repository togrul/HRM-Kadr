<div class="flex flex-col space-y-4" x-data wire:key="permissions">
    @if (empty($permission_id))
        {{-- @can('manage-settings') --}}
        <div>
            <form wire:submit.prevent="store">
                @csrf

                <div class="px-0 py-5 space-y-6 flex items-end space-x-2">
                    <div>
                        <x-label for="permission_name" :value="__('Permission')" />

                        <x-livewire-input mode="gray" name="permission_name" id="permission_name"
                            class="block mt-1 w-full sm:text-sm outline-none font-medium h-10 dark:bg-gray-700 {{ $errors->any() ? 'border-red-600' : '' }}"
                            type="text" :value="old('permission_name')" wire:model="permission_name" autofocus />
                    </div>
                    <div>
                        <x-button mode="primary" class="space-x-2">
                            <x-icons.permission-icon color="text-white" hover="text-gray-50"></x-icons.permission-icon>
                            <span>{{ __('Add permission') }}</span>
                        </x-button>
                    </div>
                </div>
            </form>
        </div>
        {{-- @endcan --}}
    @endif

    <div class="relative min-h-[300px] overflow-x-auto px-2">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

                <x-table.tbl :headers="[__('Name'), 'action', 'action']">
                    @foreach ($permissions as $permission)
                        <tr>
                            <x-table.td>
                                <div class="flex flex-row items-center space-x-2">
                                    <span @class([
                                        'px-3 py-1 inline-flex text-xs leading-4 font-medium rounded-full flex-none uppercase',
                                    ])>
                                        {{ $permission->name }}
                                    </span>
                                    {{-- @can('manage-settings') --}}
                                    @if ($permission_id && $permission_id == $permission->id)
                                        <x-livewire-input id="permission_name" name="permission_name" mode="gray"
                                            class="flex w-auto sm:text-sm outline-none font-medium h-auto dark:bg-gray-700 dark:border-black dark:text-white {{ $errors->any() ? 'border-red-600' : '' }}"
                                            type="text" :value="old('permission_name')" wire:model.defer="permission_name"
                                            autofocus />

                                        <button wire:click.prevent="store"
                                            class="flex items-center justify-center w-8 h-8 transition duration-300 ease-in-out rounded-lg bg-green-50 hover:bg-green-100 focus:outline-none">
                                            <x-icons.check-simple-icon color="text-green-600"
                                                hover="text-green-700"></x-icons.check-simple-icon>
                                        </button>

                                        <button wire:click.prevent="cancel"
                                            class="flex items-center justify-center w-8 h-8 transition duration-300 ease-in-out rounded-lg bg-red-50 hover:bg-red-100 focus:outline-none">
                                            <x-icons.close-icon color="text-red-500"
                                                hover="text-red-600"></x-icons.close-icon>
                                        </button>
                                    @endif
                                    {{-- @endcan --}}
                                </div>

                            </x-table.td>


                            <x-table.td>
                                {{-- @can('manage-settings')  --}}
                                <button
                                    class="flex flex-row items-center space-x-1 text-blue-500 dark:text-blue-300 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none"
                                    wire:click="editPermission({{ $permission->id }})">
                                    <x-icons.edit-icon color="text-blue-500" hover="text-blue-600"></x-icons.edit-icon>
                                </button>
                                {{-- @endcan --}}
                            </x-table.td>

                            <x-table.td :isButton="true">
                                {{-- @can('manage-settings') --}}
                                <button wire:click.prevent = "setDeletePermission({{ $permission->id }})"
                                    class="flex items-center justify-center w-8 h-8 text-xs font-semibold uppercase transition duration-300 rounded-lg text-red-500 hover:bg-red-100">
                                    <x-icons.delete-icon color="text-rose-400"
                                        hover="text-rose-300"></x-icons.delete-icon>
                                </button>
                                {{-- @endcan --}}
                            </x-table.td>

                        </tr>
                    @endforeach
                </x-table.tbl>
            </div>
        </div>
    </div>

    {{-- @can('manage-settings') --}}
    <div>
        @auth
            @livewire('roles.delete-permission')
        @endauth
    </div>
    {{-- @endcan --}}

</div>
