<div class="flex flex-col space-y-4" x-data wire:key="roles">
    @if (!$isUpdate)
        {{-- @can('manage-settings') --}}
        <div>
            <form wire:submit.prevent="store">
                @csrf

                <div class="px-0 py-5 space-y-6">

                    <div class="flex items-end space-x-2">
                        <div class="">
                            <x-label for="role_name" :value="__('Role')" />

                            <x-livewire-input id="role_name" name="role_name" mode="gray"
                                class="block mt-1 w-full sm:text-sm outline-none font-medium h-10 dark:bg-gray-700 {{ $errors->any() ? 'border-red-600' : '' }}"
                                type="text" :value="old('role_name')" wire:model="role_name" required autofocus />
                        </div>
                        <x-button mode="primary" class="space-x-2">
                            <x-icons.key-icon color="text-white" hover="text-gray-50"></x-icons.key-icon>
                            <span> {{ __('Add role') }}</span>
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

                <x-table.tbl :headers="[__('Name'), 'action', 'action', 'action']">
                    @foreach ($roles as $role)
                        <tr wire:key={{ $role->id }}>
                            <x-table.td>
                                <div class="flex flex-row items-center space-x-2">
                                    <span @class([
                                        'px-3 py-1 inline-flex text-xs leading-4 font-medium rounded-lg flex-none uppercase',
                                        'bg-green-100 text-green-500' => Str::contains(
                                            Str::lower($role->name),
                                            'admin'),
                                        'bg-gray-100 text-gray-600' => !Str::contains(
                                            Str::lower($role->name),
                                            'admin'),
                                    ])>
                                        {{ $role->name }}
                                    </span>
                                    @if ($isUpdate && $role_id == $role->id)
                                        <div class="flex flex-col">
                                            <x-livewire-input id="role_name" name="role_name" mode="gray"
                                                class="flex w-auto sm:text-sm outline-none font-normal h-auto dark:bg-gray-700 dark:border-black dark:text-white {{ $errors->any() ? 'border-red-600' : '' }}"
                                                type="text" :value="old('role_name')" wire:model="role_name" autofocus />

                                            <div class="flex space-x-2">
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
                                            </div>
                                        </div>
                                    @endif
                                </div>

                            </x-table.td>

                            <x-table.td class="max-w-[50px]">
                                <button
                                    class="flex flex-row items-center space-x-1 text-blue-500 dark:text-blue-300 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none w-8 h-8"
                                    wire:click="editRole({{ $role->id }})">
                                    <x-icons.edit-icon color="text-blue-500" hover="text-blue-600"></x-icons.edit-icon>
                                </button>
                            </x-table.td>

                            <x-table.td class="max-w-[50px]">
                                {{-- @can('manage-settings') --}}
                                <button wire:click.prevent="openSideMenu('set-permission',{{ $role->id }})"
                                    wire:key="edit_{{ $role_id }}"
                                    class="flex flex-row items-center space-x-1 text-teal-500  hover:text-teal-600  focus:outline-none w-8 h-8">
                                    <x-icons.shield-icon color="text-teal-500" hover="text-teal-600"
                                        size="w-7 h-7"></x-icons.shield-icon>
                                </button>
                                {{-- @endcan --}}
                            </x-table.td>

                            <x-table.td :isButton="true" class="max-w-[50px]">
                                {{-- @can('manage-settings') --}}
                                <button wire:click.prevent="setDeleteRole({{ $role->id }})"
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
    <x-side-modal>
        @if ($showSideMenu == 'set-permission')
            @livewire('roles.set-permission', ['roleModel' => $modelName])
        @endif
    </x-side-modal>
    {{-- @endcan --}}
    <div>
        @auth
            @livewire('roles.delete-role')
        @endauth
    </div>
</div>
