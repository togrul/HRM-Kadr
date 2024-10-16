<div class="flex flex-col"
    x-data
    x-init="
        paginator = document.querySelector('span[aria-current=page]>span');
        if(paginator != null)
        {
            paginator.classList.add('bg-blue-50','text-blue-600')
        }
        Livewire.hook('message.processed', (message,component) => {
            const paginator = document.querySelector('span[aria-current=page]>span')
            if(
                ['gotoPage','previousPage','nextPage','setStatus','resetFilter'].includes(message.updateQueue[0].payload.method)
                || ['openSideMenu','closeSideMenu','userAdded'].includes(message.updateQueue[0].payload.event)
                || ['q'].includes(message.updateQueue[0].name)
            ){
                if(paginator != null)
                {
                    paginator.classList.add('bg-blue-50','text-blue-600')
                }
            }
        })
    "
>

<div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
    <x-filter.nav>
        <x-filter.item  wire:click.prevent="setStatus(1)" :active="$status === 1">
            {{ __('Active') }}
        </x-filter.item>
        <x-filter.item  wire:click.prevent="setStatus(0)" :active="$status === 0">
            {{ __('De-active') }}
        </x-filter.item>
        <x-filter.item  wire:click.prevent="setStatus(2)" :active="$status === 2">
            {{ __('Deleted') }}
        </x-filter.item>
    </x-filter.nav>


    <div class="flex items-center justify-center space-x-2 action-section">
        <x-button mode="gray" wire:click.prevent="resetFilter">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            {{ __('Reset filter') }}
        </x-button>
        {{-- @can('manage-settings') --}}
        <x-button mode="primary" wire:click.prevent="openSideMenu('add-user')">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            {{ __('Add User') }}
        </x-button>
        {{-- @endcan --}}
    </div>
</div>

<div class="grid grid-cols-1 gap-4 my-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
    <div>
        <x-label for="q">{{ __('User name or email') }}</x-label>
        <x-livewire-input id="q" name="q" mode="gray" wire:model.live="q" autocomplete="off"></x-livewire-input>
    </div>
</div>

<div class="flex flex-col space-y-2">
    <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
        <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
            <x-table.tbl :headers="[__('User'),__('Role'),__('Email'),__('Active?'),'action','action']">
                @forelse ($_users as $user)
                    <tr>
                        <x-table.td>
                            <span class="text-sm font-medium">
                                {{ $user->name }}
                           </span>
                        </x-table.td>

                        <x-table.td>
                            @if(count($user->roles) > 0)
                            <span class="bg-blue-100 text-blue-500 rounded-lg px-2 py-1 text-sm font-normal lowercase whitespace-no-wrap">
                                {{ $user->roles[0]->name }}
                           </span>
                           @endif
                        </x-table.td>

                       <x-table.td>
                        <span class="text-sm font-normal text-gray-700">
                            {{ $user->email }}
                       </span>
                    </x-table.td>

                    <x-table.td >
                        <div class="flex items-center justify-start {{ $user->is_active ? 'text-green-400' : 'text-gray-300' }}">

                              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-8 h-8">
                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                              </svg>
                        </div>
                     </x-table.td>

                     <x-table.td :isButton="true">
                        @if($status == 2)
                        <div class="flex flex-col text-xs font-medium">
                            <div class="flex items-center space-x-1">
                                <span class="text-gray-500">{{__('Deleted date')}}:</span>
                                <span class="text-black">{{ \Carbon\Carbon::parse($user->deleted_at)->format('d-m-Y H:i') }}</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <span class="text-gray-500">{{__('Deleted by')}}:</span>
                                <span class="text-black">{{$user->personDidDelete->name}}</span>
                            </div>
                        </div>
                        @else
                            {{-- @can('manage-settings') --}}
                                <a href="#" wire:click.prevent="openSideMenu('edit-user',{{ $user }})" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </a>
                            {{-- @endcan --}}
                        @endif
                       </x-table.td>

                       <x-table.td :isButton="true">
                        @if($status == 2)
                        <button
                            wire:click="restoreData({{ $user->id }})"
                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-teal-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" />
                            </svg>
                        </button>
                        {{-- @role('admin') --}}
                            <button
                                onclick="confirm('Are you sure you want to remove this user?') || event.stopImmediatePropagation()"
                                wire:click.prevent="forceDeleteData({{$user->id}})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                </svg>
                            </button>
                        {{-- @endrole --}}
                        @else
                            {{-- @can('manage-settings') --}}
                            <button
                            wire:click.prevent = "setDeleteUser({{ $user->id }})"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
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
        @if($showSideMenu == 'add-user')
            <livewire:services.users.add-user />
        @endif

        @if($showSideMenu == 'edit-user')
            <livewire:services.users.edit-user :userModel="$modelName" />
        @endif
   </x-side-modal>
   {{-- @endcan --}}

   <div class="">
        @auth
            @livewire('services.users.delete-user')
        @endauth
   </div>
</div>
