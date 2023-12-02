<div class="flex flex-col space-y-4" x-data wire:key="roles"> 
    @if(!$isUpdate)
    {{-- @can('manage-settings') --}}
        <div>
            <form wire:submit.prevent="store">
                @csrf

                <div class="px-0 py-5 space-y-6">

                    <div class="flex items-end space-x-2">
                        <div class="">
                            <x-label for="role_name" :value="__('Role')"/>

                            <x-livewire-input id="role_name" name="role_name"  mode="gray" class="block mt-1 w-full sm:text-sm outline-none font-medium h-10 dark:bg-gray-700 {{$errors->any()?'border-red-600':''}}" type="text"
                                     :value="old('role_name')" wire:model="role_name" required autofocus/>
                        </div>
                            <x-button mode="black">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                </svg>
                                {{__('Add role')}}
                            </x-button>
                        </div>
                    </div>


            </form>
        </div>
        {{-- @endcan --}}
    @endif

    <div class="relative min-h-[300px] overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
        <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

    <x-table.tbl :headers="[__('Name'),'action','action','action']">
        @foreach($roles as $role)
            <tr wire:key={{ $role->id }}>
                <x-table.td>
                    <div class="flex flex-row items-center space-x-2">
                        <span
                            @class([
                                'px-3 py-1 inline-flex text-xs leading-4 font-medium rounded-lg flex-none uppercase',
                                'bg-green-100 text-green-500' => Str::contains(Str::lower($role->name), 'admin'),
                                'bg-gray-100 text-gray-600' => !Str::contains(Str::lower($role->name), 'admin')
                            ])>
                            {{$role->name}}
                        </span>
                        @if($isUpdate && $role_id==$role->id)
                        <div class="flex flex-col">
                            <x-livewire-input id="role_name" name="role_name" mode="gray" class="flex w-auto sm:text-sm outline-none font-normal h-auto dark:bg-gray-700 dark:border-black dark:text-white {{$errors->any()?'border-red-600':''}}" type="text"
                                :value="old('role_name')" wire:model="role_name" autofocus />

                                <div class="flex space-x-2">
                                    <button wire:click.prevent="store" class="flex items-center justify-center w-8 h-8 transition duration-300 ease-in-out rounded-lg bg-green-50 hover:bg-green-100 focus:outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                          </svg>
                                    </button>
        
                                    <button  wire:click.prevent="cancel" class="flex items-center justify-center w-8 h-8 transition duration-300 ease-in-out rounded-lg bg-red-50 hover:bg-red-100 focus:outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
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
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                    </button>
                </x-table.td>

                <x-table.td class="max-w-[50px]">
                    {{-- @can('manage-settings') --}}
                    <button
                    wire:click.prevent=" openSideMenu('set-permission',{{ $role->id }})"
                    wire:key="edit_{{ $role_id }}"
                        class="flex flex-row items-center space-x-1 text-teal-500  hover:text-teal-600  focus:outline-none w-8 h-8"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                      </svg>
                    </button>
                    {{-- @endcan --}}
                </x-table.td>

                <x-table.td :isButton="true" class="max-w-[50px]">
                    {{-- @can('manage-settings') --}}
                    <button wire:click.prevent="setDeleteRole({{ $role->id }})"
                        class="flex items-center justify-center w-8 h-8 text-xs font-semibold uppercase transition duration-300 rounded-lg text-red-500 hover:bg-red-100"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>

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
            @if($showSideMenu == 'set-permission')
                @livewire('roles.set-permission',['roleModel' => $modelName])
            @endif
        </x-side-modal>
    {{-- @endcan --}}
    <div>
        @auth
            @livewire('roles.delete-role') 
        @endauth
    </div>
</div>
