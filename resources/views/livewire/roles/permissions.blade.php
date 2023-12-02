<div class="flex flex-col space-y-4" x-data wire:key="permissions">
    @if(empty($permission_id))
    {{-- @can('manage-settings') --}}
        <div>
            <form wire:submit.prevent="store">
                @csrf

                <div class="px-0 py-5 space-y-6">

                    <div class="flex items-end space-x-2">
                        <div class="">

                            <x-label for="permission_name" :value="__('Permission')"/>

                            <x-livewire-input mode="gray" name="permission_name" id="permission_name"  class="block mt-1 w-full sm:text-sm outline-none font-medium h-10 dark:bg-gray-700 {{$errors->any()?'border-red-600':''}}" type="text"
                                     :value="old('permission_name')" wire:model="permission_name" autofocus/>

                        </div>
                        <div>                         
                            <x-button mode="black">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 mr-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.05 4.575a1.575 1.575 0 10-3.15 0v3m3.15-3v-1.5a1.575 1.575 0 013.15 0v1.5m-3.15 0l.075 5.925m3.075.75V4.575m0 0a1.575 1.575 0 013.15 0V15M6.9 7.575a1.575 1.575 0 10-3.15 0v8.175a6.75 6.75 0 006.75 6.75h2.018a5.25 5.25 0 003.712-1.538l1.732-1.732a5.25 5.25 0 001.538-3.712l.003-2.024a.668.668 0 01.198-.471 1.575 1.575 0 10-2.228-2.228 3.818 3.818 0 00-1.12 2.687M6.9 7.575V12m6.27 4.318A4.49 4.49 0 0116.35 15m.002 0h-.002" />
                                  </svg>
                                  
                                {{__('Add permission')}}
                            </x-button>
                        </div>

                        </div>
                    </div>


            </form>
        </div>
        {{-- @endcan --}}
    @endif

    <div class="relative min-h-[300px] overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
        <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

    <x-table.tbl :headers="[__('Name'),'action','action']">
        @foreach($permissions as $permission)
            <tr>
                <x-table.td>        
                    <div class="flex flex-row items-center space-x-2">
                        <span
                            @class([
                                'px-3 py-1 inline-flex text-xs leading-4 font-medium rounded-full flex-none uppercase',
                            ])>
                            {{$permission->name}}
                        </span>
                        {{-- @can('manage-settings') --}}
                        @if($permission_id && $permission_id==$permission->id)
                            <x-livewire-input id="permission_name" name="permission_name" mode="gray" class="flex w-auto sm:text-sm outline-none font-medium h-auto dark:bg-gray-700 dark:border-black dark:text-white {{$errors->any()?'border-red-600':''}}" type="text"
                                     :value="old('permission_name')" wire:model.defer="permission_name" autofocus />

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
                        @endif
                        {{-- @endcan --}}
                    </div>

                </x-table.td> 
            

                <x-table.td>       
                    {{-- @can('manage-settings')  --}}
                    <button
                       class="flex flex-row items-center space-x-1 text-blue-500 dark:text-blue-300 hover:text-blue-600 dark:hover:text-blue-400 focus:outline-none"
                       wire:click="editPermission({{ $permission->id }})">
                       <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                      </svg>
                    </button>
                    {{-- @endcan --}}
                </x-table.td>           

                <x-table.td :isButton="true">
                    {{-- @can('manage-settings') --}}
                    <button
                    wire:click.prevent = "setDeletePermission({{ $permission->id }})"
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
<div>
    @auth
        @livewire('roles.delete-permission')
    @endauth
</div>
{{-- @endcan --}}

</div>
