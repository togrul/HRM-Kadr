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
                ['gotoPage','previousPage','nextPage','setStatus','resetFilter'].includes(message?.updateQueue?.[0]?.payload?.method)
                || ['openSideMenu','closeSideMenu','rankAdded'].includes(message?.updateQueue?.[0]?.payload?.event)
                || ['q'].includes(message?.updateQueue?.[0]?.name)
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
        </x-filter.nav>


        <div class="flex items-center justify-center space-x-2 action-section">
            {{-- @can('manage-settings') --}}
            <x-button class="space-x-2" mode="primary" wire:click.prevent="openSideMenu('add-rank')">
                <x-icons.add-icon color="text-white" hover="text-gray-50"></x-icons.add-icon>
               <span>{{ __('Add rank') }}</span>
            </x-button>
            {{-- @endcan --}}
        </div>
    </div>

    <div class="flex flex-col space-y-2">
        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <x-table.tbl :headers="[__('ID'),__('Category'),__('Name'),__('Duration'),__('Active?'),'action','action']">
                        @forelse ($_ranks as $rank)
                            <tr>
                                <x-table.td>
                                      <span class="text-sm font-medium">
                                          {{ $rank->id }}
                                      </span>
                                </x-table.td>
                                <x-table.td>
                                      <span @class([
                                            'text-sm font-medium text-blue-500',
                                            'bg-slate-100 rounded-sm px-3 py-1' => $rank->rankCategory
                                      ])>
                                          {{ $rank->rankCategory?->name }}
                                      </span>
                                </x-table.td>
                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-medium text-sm text-slate-500">
                                                AZ -
                                            </span>
                                            <span class="text-sm font-medium">
                                                {{ $rank->name_az }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col space-y-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-medium text-sm text-slate-500">
                                                EN -
                                            </span>
                                            <span class="text-sm font-medium">
                                                {{ $rank->name_en }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col space-y-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-medium text-sm text-slate-500">
                                                RU -
                                            </span>
                                            <span class="text-sm font-medium">
                                                {{ $rank->name_ru }}
                                            </span>
                                        </div>
                                    </div>

                                </x-table.td>


                                <x-table.td>
                                    <span class="text-sm font-normal text-gray-700">
                                        {{ $rank->duration ?? '-' }}
                                   </span>
                                </x-table.td>

                                <x-table.td >
                                    <div class="flex items-center justify-start">
                                        <x-icons.check-icon
                                            size="w-8 h-8"
                                            :color="$rank->is_active ? 'text-green-400' : 'text-gray-300'"
                                            :hover="$rank->is_active ? 'text-green-500' : 'text-gray-400'"
                                        ></x-icons.check-icon>
                                    </div>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    <button
                                        wire:click.prevent="openSideMenu('edit-rank',{{ $rank }})"
                                        class="appearance-none flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700"
                                    >
                                        <x-icons.edit-icon color="text-slate-400" hover="text-slate-500"></x-icons.edit-icon>
                                    </button>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    {{-- @can('manage-settings') --}}
                                    <button
                                        wire:click.prevent = "setDeleteRank({{ $rank->id }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                                    >
                                        <x-icons.delete-icon color="text-rose-500" hover="text-rose-600"></x-icons.delete-icon>
                                    </button>
                                    {{-- @endcan --}}
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
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
            {{ $_ranks->links() }}
        </div>
    </div>

    {{-- @can('manage-settings') --}}
    <x-side-modal>
        @if($showSideMenu == 'add-rank')
            <livewire:services.ranks.add-rank />
        @endif

        @if($showSideMenu == 'edit-rank')
            <livewire:services.ranks.edit-rank :rankModel="$modelName" />
        @endif
    </x-side-modal>
    {{-- @endcan --}}

    <div class="">
        @auth
            @livewire('services.ranks.delete-rank')
        @endauth
    </div>
</div>
