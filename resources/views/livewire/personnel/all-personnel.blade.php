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
            ['gotoPage','previousPage','nextPage','filterSelected'].includes(message.updateQueue[0].payload.method)
            || ['openSideMenu','closeSideMenu','personnelAdded','filterResetted','personnelWasDeleted'].includes(message.updateQueue[0].payload.event)
            || ['search'].includes(message.updateQueue[0].name)
        ){
            if(paginator != null)
            {
                paginator.classList.add('bg-green-100','text-green-600')
            }
        }
    })
">
    {{-- sidebar  --}}
    <x-slot name="sidebar">
        @livewire('structure.sidebar')
    </x-slot>
    {{-- end sidebar --}}

    <div class="flex flex-col space-y-4 px-6 py-4">

        <div class="flex justify-between items-center">
            <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
                <x-filter.nav>
                    <x-filter.item  wire:click.prevent="setStatus('current')" :active="$status === 'current'">
                        {{ __('Active') }}
                    </x-filter.item>
                    <x-filter.item  wire:click.prevent="setStatus('leaves')" :active="$status === 'leaves'">
                        {{ __('Resigned') }}
                    </x-filter.item>
                    <x-filter.item  wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                        {{ __('All') }}
                    </x-filter.item>
                    {{-- <x-filter.item  wire:click.prevent="setBirthday()" :active="!empty($birthday)">
                        {{ __('Birthdays') }}
                    </x-filter.item> --}}
                    {{-- @can('manage-customers') --}}
                    <x-filter.item  wire:click.prevent="setStatus('deleted')" :active="$status === 'deleted'">
                        {{ __('Deleted') }}
                    </x-filter.item>
                    {{-- @endcan --}}
                </x-filter.nav>
            </div>
            <div class="flex flex-col">
                <div class="flex space-x-4">
                    <button  wire:click="openSideMenu('add-personnel')" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-blue-50" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-blue-400 transition-all duration-300 hover:text-blue-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </button>
                    <button wire:click.prevent="exportExcel" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-green-50" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"  viewBox="0 0 50 50" class="w-7 h-7 fill-green-400 transition-all duration-300 hover:fill-green-500">
                            <path d="M 28.875 0 C 28.855469 0.0078125 28.832031 0.0195313 28.8125 0.03125 L 0.8125 5.34375 C 0.335938 5.433594 -0.0078125 5.855469 0 6.34375 L 0 43.65625 C -0.0078125 44.144531 0.335938 44.566406 0.8125 44.65625 L 28.8125 49.96875 C 29.101563 50.023438 29.402344 49.949219 29.632813 49.761719 C 29.859375 49.574219 29.996094 49.296875 30 49 L 30 44 L 47 44 C 48.09375 44 49 43.09375 49 42 L 49 8 C 49 6.90625 48.09375 6 47 6 L 30 6 L 30 1 C 30.003906 0.710938 29.878906 0.4375 29.664063 0.246094 C 29.449219 0.0546875 29.160156 -0.0351563 28.875 0 Z M 28 2.1875 L 28 6.53125 C 27.867188 6.808594 27.867188 7.128906 28 7.40625 L 28 42.8125 C 27.972656 42.945313 27.972656 43.085938 28 43.21875 L 28 47.8125 L 2 42.84375 L 2 7.15625 Z M 30 8 L 47 8 L 47 42 L 30 42 L 30 37 L 34 37 L 34 35 L 30 35 L 30 29 L 34 29 L 34 27 L 30 27 L 30 22 L 34 22 L 34 20 L 30 20 L 30 15 L 34 15 L 34 13 L 30 13 Z M 36 13 L 36 15 L 44 15 L 44 13 Z M 6.6875 15.6875 L 12.15625 25.03125 L 6.1875 34.375 L 11.1875 34.375 L 14.4375 28.34375 C 14.664063 27.761719 14.8125 27.316406 14.875 27.03125 L 14.90625 27.03125 C 15.035156 27.640625 15.160156 28.054688 15.28125 28.28125 L 18.53125 34.375 L 23.5 34.375 L 17.75 24.9375 L 23.34375 15.6875 L 18.65625 15.6875 L 15.6875 21.21875 C 15.402344 21.941406 15.199219 22.511719 15.09375 22.875 L 15.0625 22.875 C 14.898438 22.265625 14.710938 21.722656 14.5 21.28125 L 11.8125 15.6875 Z M 36 20 L 36 22 L 44 22 L 44 20 Z M 36 27 L 36 29 L 44 29 L 44 27 Z M 36 35 L 36 37 L 44 37 L 44 35 Z"></path>
                        </svg>
                    </button>
                    <button wire:click.prevent="printPage('personnel')" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-red-50" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-red-400 transition-all duration-300 hover:text-red-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                        </svg>
                    </button>
                    <button
                       @click="
                            $wire.dispatch('setOpenFilter');
                        "
                         @class([
                            'flex relative items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-gray-100',
                            'bg-gray-100' => count($filters) > 0
                         ]) type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-gray-500 transition-all duration-300 hover:text-gray-900">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        @if(count($filters) > 0)
                        <span class="absolute top-0 right-0 rounded-full bg-rose-500 text-white flex justify-center w-4 h-4 text-xs">
                            {{ count($filters) }}
                        </span>
                        @endif
                    </button>
                </div>
                @if(count($filters) > 0)
                <button wire:click="resetSelectedFilter" class="appearance-none text-rose-500 text-sm font-medium flex items-center space-x-2 justify-end">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                   <span> {{ __('Reset filter') }}</span>
                </button>
                @endif
            </div>
        </div>

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

                <x-table.tbl :headers="[__('#'),__('Tabel'),__('Fullname'),__('Gender'),__('Position'),'action','action','action','action']">
                    @forelse ($personnels as $key => $personnel)
                    <tr @class([
                        'bg-white' => empty($personnel->leave_work_date),
                        'bg-red-100' => !empty($personnel->leave_work_date)
                    ])>
                        <x-table.td>
                            <span class="text-sm font-medium text-gray-700">
                                {{ ($personnels->currentpage()-1) * $personnels->perpage() + $key + 1 }}
                           </span>
                        </x-table.td>

                        <x-table.td>
                            <div class="flex flex-col space-y-1">
                                <span class="text-sm font-medium text-blue-500">
                                    {{ $personnel->tabel_no }}
                               </span>
                                @if($status == 'deleted')
                                <div class="flex flex-col text-xs font-medium">
                                    <div class="flex items-center space-x-1">
                                        <span class="text-gray-500">{{__('Deleted date')}}:</span>
                                        <span class="text-black">{{ \Carbon\Carbon::parse($personnel->deleted_at)->format('d-m-Y H:i') }}</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <span class="text-gray-500">{{__('Deleted by')}}:</span>
                                        <span class="text-black">{{$personnel->personDidDelete->name}}</span>
                                    </div>
                                </div>
                                @endif
                            </div>

                        </x-table.td>

                        <x-table.td>
                            <div class="flex items-center space-x-2">
                                @if(!empty($personnel->photo))
                                    <img src="{{ asset('/storage/'.$personnel->photo) }}" alt="" class="flex-none rounded-xl object-cover w-14 h-14 border-4 border-gray-200">
                                @else
                                    <img src="{{ asset('assets/images/no-image.png') }}" alt="" class="flex-none rounded-xl object-cover w-14 h-14 border-4 border-gray-200">
                                @endif
                               <div class="flex flex-col space-y-1">
                                <span class="text-sm font-medium text-gray-600">
                                    {{ $personnel->fullname }}
                               </span>
                               <span class="text-sm font-medium text-teal-500 bg-teal-50 rounded-xl px-3 py-1 w-max">
                                     {{ $personnel->pin }}
                                </span>
                                @if(!empty($personnel->latestRank))
                                <span class="text-sm font-medium text-rose-500 rounded-xl px-3 py-1 shadow-sm w-max bg-rose-50">
                                    {{ $personnel->latestRank?->rank->name }}
                               </span>
                               @endif
                               </div>
                            </div>
                        </x-table.td>

                        <x-table.td>
                            <span class="text-sm font-medium text-gray-500 rounded-xl px-3 py-1 shadow-sm bg-gray-100">
                                {{ $personnel->gender ? __('Man') : __('Woman') }}
                           </span>
                        </x-table.td>

                        <x-table.td>
                            <div class="flex flex-col space-y-1">
                                <div class="flex space-x-1 items-center">
                                    <span class="text-gray-500 text-sm font-medium">{{ __('Structure') }}:</span>
                                    <span class="text-gray-900 text-sm font-medium bg-green-100 px-2 py-1 rounded-lg">{{ $personnel->structure->name }}</span>
                                </div>
                                <div class="flex space-x-1 items-center">
                                    <span class="text-gray-500 text-sm font-medium">{{ __('Position') }}:</span>
                                    <span class="text-gray-900 text-sm font-medium bg-orange-100 px-2 py-1 rounded-lg">{{ $personnel->position->name }}</span>
                                </div>
                                <div class="flex space-x-1">
                                    <span class="text-gray-500 text-sm font-medium">{{ __('Join date') }}:</span>
                                    <span class="text-gray-900 text-sm font-medium">{{ \Carbon\Carbon::parse($personnel->join_work_date)->format('d.m.Y') }}</span>
                                </div>
                                @if( !empty($personnel->leave_work_date))
                                <div class="flex space-x-1">
                                    <span class="text-gray-500 text-sm font-medium">{{ __('Leave date') }}:</span>
                                    <span class="text-red-500 text-sm font-medium">{{ \Carbon\Carbon::parse($personnel->leave_work_date)->format('d.m.Y') }}</span>
                                </div>
                                @endif
                            </div>
                        </x-table.td>
                        <x-table.td :isButton="true">
                            @if($status != 'deleted')
                            {{-- @can('manage-customers') --}}
                                <a href="#" wire:click="openSideMenu('edit-personnel',{{ $personnel->id }})" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                                     4 </svg>
                                </a>
                            {{-- @endcan --}}
                            @else
                            <button
                                wire:click="restoreData('{{$personnel->tabel_no}}')"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-teal-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3" />
                                </svg>
                            </button>
                            @endif
                        </x-table.td>

                        <x-table.td :isButton="true">
                            <a href="#" wire:click="openSideMenu('show-files','{{ $personnel->tabel_no }}')" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg bg-blue-50 hover:bg-blue-100">
                                <svg class="w-6 h-6 text-blue-500" data-slot="icon" fill="none" stroke-width="1.7" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"></path>
                                </svg>
                            </a>
                        </x-table.td>

                        <x-table.td :isButton="true">
                            <button wire:click="printInfo('{{ $personnel->id }}')" class="appearance-none flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg bg-teal-50 hover:bg-teal-100">
                                <svg class="w-6 h-6 text-teal-500" data-slot="icon" fill="none" stroke-width="1.7" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"></path>
                                </svg>
                            </button>
                        </x-table.td>

                        <x-table.td :isButton="true">
                        @if($status != 'deleted')
                            {{-- @can('manage-employee') --}}
                            <button
                               wire:click="setDeletePersonnel('{{ $personnel->tabel_no }}')"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                            {{-- @endcan --}}
                         @else
                             <button
                                wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                wire:click="forceDeleteData('{{ $personnel->tabel_no }}')"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                </svg>
                            </button>
                        @endif
                        </x-table.td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                           <div class="flex justify-center items-center py-4">
                            <span class="font-medium">{{ __('No information added') }}</span>
                           </div>
                        </td>
                    </tr>
                    @endforelse
                </x-table.tbl>

            </div>
            </div>
            </div>

            <div class="mt-2">
                {{ $personnels->links() }}
            </div>
    </div>

    @livewire('filter.detail')
    {{-- @can('manage-personnel') --}}
    <x-side-modal>
        @if($showSideMenu == 'add-personnel')
            <livewire:personnel.add-personnel />
        @endif

        @if($showSideMenu == 'edit-personnel')
            <livewire:personnel.edit-personnel :personnelModel="$modelName" />
        @endif

            @if($showSideMenu == 'show-files')
                <livewire:personnel.files :personnelModel="$modelName" />
            @endif
   </x-side-modal>
   {{-- @endcan --}}
   <div>
        <livewire:personnel.delete-personnel />
   </div>

   <x-datepicker :auto=false></x-datepicker>
</div>

