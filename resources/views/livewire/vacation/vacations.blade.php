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
                || ['openSideMenu','closeSideMenu','vacationUpdated','filterResetted'].includes(message.updateQueue[0].payload.event)
                || ['search'].includes(message.updateQueue[0].name)
            ){
                if(paginator != null)
                {
                    paginator.classList.add('bg-green-100','text-green-600')
                }
            }
        })
">
    <div class="flex flex-col space-y-4 px-6 py-4">
        <div class="flex justify-between items-center">
            <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl"></div>

            <div class="flex flex-col">
                <div class="flex space-x-4">
                    <button wire:click.prevent="exportExcel" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-green-50" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"  viewBox="0 0 50 50" class="w-7 h-7 fill-green-400 transition-all duration-300 hover:fill-green-500">
                            <path d="M 28.875 0 C 28.855469 0.0078125 28.832031 0.0195313 28.8125 0.03125 L 0.8125 5.34375 C 0.335938 5.433594 -0.0078125 5.855469 0 6.34375 L 0 43.65625 C -0.0078125 44.144531 0.335938 44.566406 0.8125 44.65625 L 28.8125 49.96875 C 29.101563 50.023438 29.402344 49.949219 29.632813 49.761719 C 29.859375 49.574219 29.996094 49.296875 30 49 L 30 44 L 47 44 C 48.09375 44 49 43.09375 49 42 L 49 8 C 49 6.90625 48.09375 6 47 6 L 30 6 L 30 1 C 30.003906 0.710938 29.878906 0.4375 29.664063 0.246094 C 29.449219 0.0546875 29.160156 -0.0351563 28.875 0 Z M 28 2.1875 L 28 6.53125 C 27.867188 6.808594 27.867188 7.128906 28 7.40625 L 28 42.8125 C 27.972656 42.945313 27.972656 43.085938 28 43.21875 L 28 47.8125 L 2 42.84375 L 2 7.15625 Z M 30 8 L 47 8 L 47 42 L 30 42 L 30 37 L 34 37 L 34 35 L 30 35 L 30 29 L 34 29 L 34 27 L 30 27 L 30 22 L 34 22 L 34 20 L 30 20 L 30 15 L 34 15 L 34 13 L 30 13 Z M 36 13 L 36 15 L 44 15 L 44 13 Z M 6.6875 15.6875 L 12.15625 25.03125 L 6.1875 34.375 L 11.1875 34.375 L 14.4375 28.34375 C 14.664063 27.761719 14.8125 27.316406 14.875 27.03125 L 14.90625 27.03125 C 15.035156 27.640625 15.160156 28.054688 15.28125 28.28125 L 18.53125 34.375 L 23.5 34.375 L 17.75 24.9375 L 23.34375 15.6875 L 18.65625 15.6875 L 15.6875 21.21875 C 15.402344 21.941406 15.199219 22.511719 15.09375 22.875 L 15.0625 22.875 C 14.898438 22.265625 14.710938 21.722656 14.5 21.28125 L 11.8125 15.6875 Z M 36 20 L 36 22 L 44 22 L 44 20 Z M 36 27 L 36 29 L 44 29 L 44 27 Z M 36 35 L 36 37 L 44 37 L 44 35 Z"></path>
                        </svg>
                    </button>
                    <button wire:click="printPage" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-red-50" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-red-400 transition-all duration-300 hover:text-red-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-2">
            <div class="flex flex-col xl:col-span-2">
                @php
                    $selectedName = array_key_exists('structure_id',$filter) ? $filter['structure_id']['name'] : '---';
                    $selectedId = array_key_exists('structure_id',$filter) ? $filter['structure_id']['id'] : -1;
                @endphp
                <x-select-list class="w-full" :title="__('Structure')" mode="gray" :selected="$selectedName" name="structureId">
                    <x-livewire-input  @click.stop="open = true" mode="gray" name="searchStructure" wire:model.live="searchStructure"></x-livewire-input>

                    <x-select-list-item wire:click="setData('filter','structure_id',null,'---',null)" :selected="'---' ==  $selectedName"
                                        wire:model='filter.structure_id.id'>
                        ---
                    </x-select-list-item>
                    @foreach($_structures as $_structure)
                        <x-select-list-item wire:click="setData('filter','structure_id',null,'{{ trim($_structure->name) }}',{{ $_structure->id }})"
                                            :selected="$_structure->id === $selectedId" wire:model='filter.structure_id.id'>
                            {{ $_structure->name }}
                        </x-select-list-item>
                    @endforeach
                </x-select-list>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.surname">{{ __('Fullname') }}</x-label>
                <x-livewire-input mode="gray" name="filter.fullname" wire:model.defer="filter.fullname"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.order_no">{{ __('Order #') }}</x-label>
                <x-livewire-input mode="gray" name="filter.order_no" wire:model.defer="filter.order_no"></x-livewire-input>
            </div>
            <div class="flex flex-col lg:col-span-2">
                <x-label for="filter.date_range">{{ __('Date range') }}</x-label>
                <div class="flex space-x-1 items-center">
                    <x-pikaday-input mode="gray" name="filter.date.min" format="Y-MM-DD" wire:model.live="filter.date.min">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('filter.date.min', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                    <span>-</span>
                    <x-pikaday-input mode="gray" name="filter.date.max" format="Y-MM-DD" wire:model.live="filter.date.max">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('filter.date.max', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                </div>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.vacation_places">{{ __('Location') }}</x-label>
                <x-livewire-input mode="gray" name="filter.vacation_places" wire:model.defer="filter.vacation_places"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="filter.duration">{{ __('Duration') }}</x-label>
                <x-livewire-input type="number" mode="gray" name="filter.duration" wire:model.defer="filter.duration"></x-livewire-input>
            </div>
            <div class="flex flex-col space-y-1 w-full lg:col-span-2">
                <x-label for="filter.gender">{{ __('Status') }}</x-label>
                <div class="flex flex-row">
                    <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                        <input type="radio" class="form-radio" name="filter.vacation_status" wire:model="filter.vacation_status" value="all">
                        <span class="ml-2 text-sm font-normal">{{ __('All') }}</span>
                    </label>
                    <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                        <input type="radio" class="form-radio" name="filter.vacation_status" wire:model="filter.vacation_status" value="at_work">
                        <span class="ml-2 text-sm font-normal">{{ __('At work') }}</span>
                    </label>
                    <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                        <input type="radio" class="form-radio" name="filter.vacation_status" wire:model="filter.vacation_status" value="in_vacation">
                        <span class="ml-2 text-sm font-normal">{{__('In vacation')}}</span>
                    </label>
                </div>
            </div>
            <div class="flex space-x-2 items-end">
                <x-button mode="primary" wire:click="searchFilter">{{ __('Search') }}</x-button>
                <x-button mode="black" wire:click="resetFilter">{{ __('Reset') }}</x-button>
            </div>
        </div>


        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

                    <x-table.tbl :headers="[__('#'),__('Fullname'),__('Structure'),__('Dates'),__('Locations'),__('Order'),'action']">
                        @forelse ($_vacations as $key => $_vacation)
                            <tr @class([
                                'bg-teal-50' => $_vacation->return_work_date > \Carbon\Carbon::now()
                            ])>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ ($_vacations->currentpage()-1) * $_vacations->perpage() + $key + 1 }}
                                    </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1 items-start">
                                         <span class="text-slate-500 flex justify-center items-center text-sm font-medium rounded-lg drop-shadow-2xl">
                                                {{ $_vacation->personnel?->latestRank?->rank->name }}
                                         </span>
                                        <span class="text-sm font-medium text-slate-900">
                                            {{ $_vacation->personnel?->fullname }}
                                       </span>
                                        @if($_vacation->return_work_date > \Carbon\Carbon::now())
                                            <span class="text-green-700 flex justify-center items-center text-sm font-medium bg-green-200 px-2 py-1 rounded-lg">
                                                {{ __('In vacation') }}
                                            </span>
                                        @endif
                                    </div>

                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-blue-500 text-sm font-medium bg-slate-100 px-2 py-1 rounded-lg">
                                            {{ $_vacation->personnel?->structure?->name }}
                                        </span>
                                        <span class="text-rose-500 text-sm font-medium bg-slate-100 px-2 py-1 rounded-lg">
                                            {{ $_vacation->personnel?->position?->name }}
                                        </span>
                                    </div>

                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{__('Duration')}}:</span>
                                            <span class="text-teal-500">{{ $_vacation->duration }} {{ __('day') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{__('Start date')}}:</span>
                                            <span class="text-sky-500">{{ \Carbon\Carbon::parse($_vacation->start_date)->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{__('End date')}}:</span>
                                            <span class="text-rose-500">{{ \Carbon\Carbon::parse($_vacation->end_date)->format('d.m.Y') }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{__('Return work date')}}:</span>
                                            <span class="text-green-500">{{ \Carbon\Carbon::parse($_vacation->return_work_date)->format('d.m.Y') }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                     <span class="text-sm font-medium text-slate-900">
                                            {{ $_vacation->vacation_places }}
                                     </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{__('Order #')}}:</span>
                                            <a href="{{ route('orders',['search' => ['order_no' =>  $_vacation->order_no ]]) }}" class="text-blue-600">{{ $_vacation->order_no }}</a>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{__('Given by')}}:</span>
                                            <span class="text-black">{{ $_vacation->order_given_by }}</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{__('Given date')}}:</span>
                                            <span class="text-black">{{ \Carbon\Carbon::parse($_vacation->order_given_date)->format('d.m.Y') }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    <button
                                        wire:click="printVacationDocument('{{$_vacation->id}}')"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700"
                                    >
                                        <svg class="w-6 h-6 text-teal-500" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"></path>
                                        </svg>
                                    </button>
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
            {{ $_vacations->links() }}
        </div>
    </div>


    {{-- @can('manage-candidate') --}}
    <x-side-modal>
{{--        @if($showSideMenu == 'add-candidate')--}}
{{--            @livewire('candidates.add-candidate')--}}
{{--        @endif--}}

{{--        @if($showSideMenu == 'edit-candidate')--}}
{{--            <livewire:candidates.edit-candidate :candidateModel="$modelName" />--}}
{{--        @endif--}}
    </x-side-modal>
    {{-- @endcan --}}

    <div>
{{--        @livewire('candidates.delete-candidate')--}}
    </div>

    <x-datepicker :auto=false></x-datepicker>
</div>
