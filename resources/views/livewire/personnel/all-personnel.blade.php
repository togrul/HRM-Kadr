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
                    <x-filter.item  wire:click.prevent="setStatus('pending')" :active="$status === 'pending'">
                        {{ __('Pending') }}
                    </x-filter.item>
                    {{-- @endcan --}}
                </x-filter.nav>
            </div>
            <div class="flex flex-col">
                <div class="flex space-x-4">
                    @can('add-personnels')
                    <button wire:click="openSideMenu('add-personnel')" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-blue-50" type="button">
                        <x-icons.add-file></x-icons.add-file>
                    </button>
                    @endcan

                    @can('export-personnels')
                    <button wire:click.prevent="exportExcel" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-green-50" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"  viewBox="0 0 50 50" class="w-7 h-7 fill-green-400 transition-all duration-300 hover:fill-green-500">
                            <path d="M 28.875 0 C 28.855469 0.0078125 28.832031 0.0195313 28.8125 0.03125 L 0.8125 5.34375 C 0.335938 5.433594 -0.0078125 5.855469 0 6.34375 L 0 43.65625 C -0.0078125 44.144531 0.335938 44.566406 0.8125 44.65625 L 28.8125 49.96875 C 29.101563 50.023438 29.402344 49.949219 29.632813 49.761719 C 29.859375 49.574219 29.996094 49.296875 30 49 L 30 44 L 47 44 C 48.09375 44 49 43.09375 49 42 L 49 8 C 49 6.90625 48.09375 6 47 6 L 30 6 L 30 1 C 30.003906 0.710938 29.878906 0.4375 29.664063 0.246094 C 29.449219 0.0546875 29.160156 -0.0351563 28.875 0 Z M 28 2.1875 L 28 6.53125 C 27.867188 6.808594 27.867188 7.128906 28 7.40625 L 28 42.8125 C 27.972656 42.945313 27.972656 43.085938 28 43.21875 L 28 47.8125 L 2 42.84375 L 2 7.15625 Z M 30 8 L 47 8 L 47 42 L 30 42 L 30 37 L 34 37 L 34 35 L 30 35 L 30 29 L 34 29 L 34 27 L 30 27 L 30 22 L 34 22 L 34 20 L 30 20 L 30 15 L 34 15 L 34 13 L 30 13 Z M 36 13 L 36 15 L 44 15 L 44 13 Z M 6.6875 15.6875 L 12.15625 25.03125 L 6.1875 34.375 L 11.1875 34.375 L 14.4375 28.34375 C 14.664063 27.761719 14.8125 27.316406 14.875 27.03125 L 14.90625 27.03125 C 15.035156 27.640625 15.160156 28.054688 15.28125 28.28125 L 18.53125 34.375 L 23.5 34.375 L 17.75 24.9375 L 23.34375 15.6875 L 18.65625 15.6875 L 15.6875 21.21875 C 15.402344 21.941406 15.199219 22.511719 15.09375 22.875 L 15.0625 22.875 C 14.898438 22.265625 14.710938 21.722656 14.5 21.28125 L 11.8125 15.6875 Z M 36 20 L 36 22 L 44 22 L 44 20 Z M 36 27 L 36 29 L 44 29 L 44 27 Z M 36 35 L 36 37 L 44 37 L 44 35 Z"></path>
                        </svg>
                    </button>
                    @endcan
                    @can('edit-personnels')
                    <button wire:click.prevent="printPage('personnel')" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-red-50" type="button">
                        <x-icons.print-file color="text-rose-500" hover="text-rose-600" size="w-8 h-8"></x-icons.print-file>
                    </button>
                    @endcan
                    <button
                       @click="
                            $wire.dispatch('setOpenFilter');
                        "
                         @class([
                            'flex relative items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-gray-100',
                            'bg-gray-100' => count($filters) > 0
                         ]) type="button">
                            <x-icons.search-file></x-icons.search-file>
                            @if(count($filters) > 0)
                                <span class="absolute top-0 right-0 rounded-full bg-rose-500 text-white flex justify-center w-4 h-4 text-xs">
                                    {{ count($filters) }}
                                </span>
                            @endif
                    </button>
                </div>
                @if(count($filters) > 0)
                <button wire:click="resetSelectedFilter" class="appearance-none text-rose-500 text-sm font-medium flex items-center space-x-2 justify-end">
                    <x-icons.remove-icon></x-icons.remove-icon>
                   <span> {{ __('Reset filter') }}</span>
                </button>
                @endif
            </div>
        </div>

        {{-- filter position--}}
        <div class="flex justify-start items-center flex-wrap gap-2">
            @foreach($this->positions as $position)
                <button
                    wire:click.prevent="setPosition({{ $position->id }})"
                    @class([
                        'appearance-none w-max text-sm font-medium bg-gray-50 border rounded-md px-3 py-1 transition-all duration-300 hover:shadow-sm hover:text-gray-900',
                        'shadow-none text-teal-500' => $position->id == $selectedPosition,
                        'shadow-md text-gray-600' => $position->id != $selectedPosition
                    ])
                >
                   <span> {{ $position->name }} </span>
                </button>
            @endforeach

            @if(!empty($selectedPosition))
                    <button
                        wire:click.prevent="resetFilter"
                        class="appearance-none w-max text-sm font-medium bg-slate-100 text-rose-500 rounded-2xl px-3 py-1 transition-all duration-300 hover:bg-slate-200"
                    >
                        {{ __('Reset') }}
                    </button>
            @endif
        </div>

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

                <x-table.tbl :headers="[__('#'),__('Tabel'),__('Fullname'),__('Position'),'action','action','action']">
                    @forelse ($this->personnels as $key => $personnel)
                    <tr @class([
                        'relative',
//                        'bg-white' => empty($personnel->leave_work_date),
                        'bg-red-100' => !empty($personnel->leave_work_date),
                        'bg-gray-50' => $personnel->hasActiveBusinessTrip || $personnel->hasActiveBusinessTrip
                    ])>
                        <x-table.td>
                            <div class="flex flex-col justify-between h-full absolute top-0 left-0">
                                @if($personnel->hasActiveVacation)
                                    @php
                                        $activeVacation = $personnel->hasActiveBusinessTrip;
                                        $vacationStart = $activeVacation->start_date;
                                        $vacationEnd = $activeVacation->return_work_date;
                                    @endphp
                                    <x-progress :startDate="$vacationStart" :endDate="$vacationEnd" color="emerald">
                                        {{ __('In vacation') }}
                                    </x-progress>
                                @endif
                                @if($personnel->hasActiveBusinessTrip)
                                    @php
                                        $businessTrip = $personnel->hasActiveBusinessTrip;
                                        $startDate = $businessTrip->start_date;
                                        $endDate = $businessTrip->end_date;
                                    @endphp
                                    <x-progress :$startDate :$endDate color="rose">
                                        {{ __('In business trip') }}
                                    </x-progress>
                                @endif
                            </div>

                            <span class="text-sm font-medium text-gray-700">
                                {{ ($this->personnels->currentpage()-1) * $this->personnels->perpage() + $key + 1 }}
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
                                        <span class="text-black">{{ \Carbon\Carbon::parse($personnel->deleted_at)->format('d.m.Y H:i') }}</span>
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
                            <div class="flex items-center space-x-2 px-2">
                                @if(!empty($personnel->photo))
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($personnel->photo) }}" alt="" class="flex-none rounded-xl object-cover w-14 h-14 border-4 border-gray-200">
                                @else
                                    <img src="{{ asset('assets/images/no-image.png') }}" alt="" class="flex-none rounded-xl object-cover w-14 h-14 border-4 border-gray-200">
                                @endif
                               <div class="flex flex-col space-y-1">
                                <span class="text-sm font-medium text-gray-600">
                                    {{ $personnel->fullname }}
                               </span>
                               <span class="text-sm w-max font-medium text-gray-600 rounded-xl px-3 py-1 shadow-sm bg-gray-100">
                                    {{ $personnel->gender == 1 ? __('Man') : __('Woman') }}
                               </span>
                                @if(!empty($personnel->latestRank))
                                <span class="text-sm font-medium rounded-xl px-3 py-1 shadow-sm w-max bg-green-950 text-yellow-400">
                                    {{ $personnel->latestRank?->rank->name }}
                               </span>
                               @endif
                               </div>
                            </div>
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
                             @can('edit-personnels')
                                <a href="#" wire:click="openSideMenu('edit-personnel',{{ $personnel->id }})" class="flex items-center justify-center w-9 h-9 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                    <x-icons.profile-icon></x-icons.profile-icon>
                                </a>
                             @endcan
                            @else
                                @can('edit-personnels')
                                <button
                                    wire:click="restoreData('{{$personnel->tabel_no}}')"
                                    class="flex items-center justify-center w-9 h-9 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 bg-teal-50 hover:bg-teal-100 hover:text-gray-700"
                                >
                                    <x-icons.recover color="text-teal-500" hover="text-teal-600"></x-icons.recover>
                                </button>
                                @endcan
                            @endif
                        </x-table.td>

                        <x-table.td :isButton="true" style="text-align: center !important;">
                            @can('edit-personnels')
                            <div class="relative inline-block text-left" x-data="{showContextMenu:false}">
                                <div>
                                    <button @click="showContextMenu = !showContextMenu" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg bg-blue-50 hover:bg-blue-100">
                                        <x-icons.settings-icon></x-icons.settings-icon>
                                    </button>
                                </div>
                                <div x-show="showContextMenu"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-90"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-100"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-90"
                                     @click.outside="showContextMenu = false"
                                     @class([
                                        'absolute right-0 z-10 mt-2 origin-bottom-right w-max rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none',
                                        'bottom-full' => $loop->remaining <= 1,
                                        'top-full' => $loop->remaining > 1
                                     ])
                                     role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                                    <div class="flex flex-col" role="none">
                                        <button wire:click="openSideMenu('show-files','{{ $personnel->tabel_no }}')"
                                                class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium rounded-md  hover:bg-slate-100"
                                        >
                                            <span class="text-slate-500">{{ __('Files') }}</span>
                                        </button>
                                        <a href="{{ route('print.personnel',$personnel->id) }}"
                                           class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium rounded-md  hover:bg-slate-100"
                                           target="_blank"
                                        >
                                            <span class="text-slate-500">{{ __('Print') }}</span>
                                        </a>
                                        <button wire:click="openSideMenu('show-information','{{ $personnel->tabel_no }}')"
                                                class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium rounded-md  hover:bg-slate-100"
                                        >
                                            <span class="text-slate-500">{{ __('Information') }}</span>
                                        </button>
                                        <button wire:click="printInfo('{{ $personnel->id }}')"
                                                class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium rounded-md  hover:bg-slate-100"
                                        >
                                            <span class="text-slate-500">{{ __('Orders') }}</span>
                                        </button>
                                        <button wire:click="openSideMenu('show-vacations','{{ $personnel->tabel_no }}')"
                                                class="appearance-none w-full flex items-center justify-start space-x-2 px-4 py-2 text-sm font-medium rounded-md  hover:bg-slate-100"
                                        >
                                            <span class="text-slate-500">{{ __('Vacations') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endcan
                        </x-table.td>

                        <x-table.td :isButton="true">
                        @if($status != 'deleted')
                             @can('delete-personnels')
                            <button
                                wire:click="setDeletePersonnel('{{ $personnel->tabel_no }}')"
                                class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                            >
                                <x-icons.delete-icon></x-icons.delete-icon>
                            </button>
                             @endcan
                         @else
                            @can('edit-personnels')
                                 <button
                                    wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                    wire:click="forceDeleteData('{{ $personnel->tabel_no }}')"
                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                 >
                                     <x-icons.force-delete></x-icons.force-delete>
                                </button>
                             @endcan
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
                {{ $this->personnels->links() }}
            </div>
    </div>

    @livewire('filter.detail',['lazy' => 'on-load'])

    <x-side-modal>
        @can('add-personnels')
            @if($showSideMenu == 'add-personnel')
                <livewire:personnel.add-personnel />
            @endif
        @endcan

        @can('edit-personnels')
            @if($showSideMenu == 'edit-personnel')
                <livewire:personnel.edit-personnel :personnelModel="$modelName" />
            @endif
        @endcan

        @can('edit-personnels')
            @if($showSideMenu == 'show-files')
                <livewire:personnel.files :personnelModel="$modelName" />
            @endif
        @endcan

        @can('edit-personnels')
            @if($showSideMenu == 'show-information')
                <livewire:personnel.information :personnelModel="$modelName" />
            @endif
        @endcan

        @can('edit-personnels')
             @if($showSideMenu == 'show-vacations')
                <livewire:personnel.vacation-list :personnelModel="$modelName" :key="'vacation-list-'.$modelName" />
             @endif
        @endcan

    </x-side-modal>

    @can('delete-personnels')
       <div>
            <livewire:personnel.delete-personnel />
       </div>
    @endcan

   <x-datepicker :auto=false></x-datepicker>
</div>
