<div class="flex flex-col"
    x-data
>
    <div class="flex items-center justify-end space-x-2 action-section py-2">
            {{-- @can('manage-settings') --}}
            <x-button mode="primary" wire:click.prevent="openSideMenu('add-menu')" class="space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                </svg>                  
                <span>{{ __('Add menu') }}</span>
            </x-button>
            {{-- @endcan --}}
    </div>

    <div class="flex flex-col space-y-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <x-table.tbl :headers="[__('Name'),__('Color'),__('Order'),__('URL'),__('Active?'),'action','action']">
                    @forelse ($_menus as $menu)
                        <tr>
                            <x-table.td>
                                <div class="flex space-x-2 items-center">
                                    <div class="flex justify-center items-center p-2 rounded-xl bg-{{ $menu->color }}-100">
                                        {!! $menu->icon !!}
                                    </div>
                                   
                                    <span class="text-sm font-medium">
                                        {{ $menu->name }}
                                   </span>
                                </div>  
                            </x-table.td>
    
                            <x-table.td>
                                <span class="text-slate-500 font-medium">
                                    {{ $menu->color }}
                                </span>
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
            
                            <x-table.td >
                                <div class="flex items-center justify-start {{ $menu->is_active ? 'text-green-400' : 'text-gray-300' }}">
            
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8">
                                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </x-table.td>
        
                            <x-table.td :isButton="true">
                              {{-- @can('manage-settings') --}}
                              <a href="#" wire:click.prevent="openSideMenu('edit-menu',{{ $menu }})" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </a>
                            {{-- @endcan --}}
                           </x-table.td>
        
                           <x-table.td :isButton="true">
                            {{-- @can('manage-settings') --}}
                            <button
                            wire:click.prevent = "setDeleteMenu({{ $menu->id }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
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

    {{-- @can('manage-settings') --}}
    <x-side-modal>
        @if($showSideMenu == 'add-menu')
            <livewire:services.menus.add-menu />
        @endif

        @if($showSideMenu == 'edit-menu')
            <livewire:services.menus.edit-menu :menuModel="$modelName" />
        @endif
    </x-side-modal>
   {{-- @endcan --}}

   <div class="">
        @auth
            @livewire('services.menus.delete-menu')
        @endauth
   </div>
</div>
