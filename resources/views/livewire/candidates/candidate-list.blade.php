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
                || ['openSideMenu','closeSideMenu','candidateAdded','filterResetted','candidateWasDeleted'].includes(message.updateQueue[0].payload.event)
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
            <div class="flex flex-col items-center justify-between sm:flex-row filter bg-white py-2 px-2 rounded-xl">
                <x-filter.nav>
                    <x-filter.item  wire:click.prevent="setStatus('all')" :active="$status === 'all'">
                        {{ __('All') }}
                    </x-filter.item>
                    @foreach($_appeal_statuses as $_status)
                        <x-filter.item  wire:click.prevent="setStatus({{ $_status->id }})" :active="$status === $_status->id">
                            {{ $_status->name  }}
                        </x-filter.item>
                    @endforeach
                    {{--                 @can('manage-candidates')--}}
                    <x-filter.item  wire:click.prevent="setStatus('deleted')" :active="$status === 'deleted'">
                        {{ __('Deleted') }}
                    </x-filter.item>
                    {{--                 @endcan--}}
                </x-filter.nav>
            </div>

            <div class="flex flex-col">
                <div class="flex space-x-4">
                    <button  wire:click="openSideMenu('add-candidate')" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-blue-50" type="button">
                        @include('components.icons.add-file')
                    </button>
                    <button wire:click.prevent="exportExcel" class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-green-50" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"  viewBox="0 0 50 50" class="w-7 h-7 fill-green-400 transition-all duration-300 hover:fill-green-500">
                            <path d="M 28.875 0 C 28.855469 0.0078125 28.832031 0.0195313 28.8125 0.03125 L 0.8125 5.34375 C 0.335938 5.433594 -0.0078125 5.855469 0 6.34375 L 0 43.65625 C -0.0078125 44.144531 0.335938 44.566406 0.8125 44.65625 L 28.8125 49.96875 C 29.101563 50.023438 29.402344 49.949219 29.632813 49.761719 C 29.859375 49.574219 29.996094 49.296875 30 49 L 30 44 L 47 44 C 48.09375 44 49 43.09375 49 42 L 49 8 C 49 6.90625 48.09375 6 47 6 L 30 6 L 30 1 C 30.003906 0.710938 29.878906 0.4375 29.664063 0.246094 C 29.449219 0.0546875 29.160156 -0.0351563 28.875 0 Z M 28 2.1875 L 28 6.53125 C 27.867188 6.808594 27.867188 7.128906 28 7.40625 L 28 42.8125 C 27.972656 42.945313 27.972656 43.085938 28 43.21875 L 28 47.8125 L 2 42.84375 L 2 7.15625 Z M 30 8 L 47 8 L 47 42 L 30 42 L 30 37 L 34 37 L 34 35 L 30 35 L 30 29 L 34 29 L 34 27 L 30 27 L 30 22 L 34 22 L 34 20 L 30 20 L 30 15 L 34 15 L 34 13 L 30 13 Z M 36 13 L 36 15 L 44 15 L 44 13 Z M 6.6875 15.6875 L 12.15625 25.03125 L 6.1875 34.375 L 11.1875 34.375 L 14.4375 28.34375 C 14.664063 27.761719 14.8125 27.316406 14.875 27.03125 L 14.90625 27.03125 C 15.035156 27.640625 15.160156 28.054688 15.28125 28.28125 L 18.53125 34.375 L 23.5 34.375 L 17.75 24.9375 L 23.34375 15.6875 L 18.65625 15.6875 L 15.6875 21.21875 C 15.402344 21.941406 15.199219 22.511719 15.09375 22.875 L 15.0625 22.875 C 14.898438 22.265625 14.710938 21.722656 14.5 21.28125 L 11.8125 15.6875 Z M 36 20 L 36 22 L 44 22 L 44 20 Z M 36 27 L 36 29 L 44 29 L 44 27 Z M 36 35 L 36 37 L 44 37 L 44 35 Z"></path>
                        </svg>
                    </button>
                    <button class="flex items-center justify-center rounded-xl w-12 h-12 transition-all duration-300 hover:bg-red-50" type="button">
                        @include('components.icons.print-file',['color' => 'text-rose-500','hover' => 'text-rose-600','size' => 'w-8 h-8'])
                    </button>
                </div>
            </div>
        </div>


        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">

                    <x-table.tbl :headers="[__('#'),__('Fullname'),__('Structure'),__('Tests'),__('Dates'),__('Status'),'action','action']">
                        @forelse ($_candidates as $key => $_candidate)
                            <tr>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ ($_candidates->currentpage()-1) * $_candidates->perpage() + $key + 1 }}
                                   </span>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col space-y-1">
                                        <span class="text-sm font-medium text-slate-900">
                                            {{ $_candidate->fullname_max }}
                                       </span>
                                        @if(!empty($_candidate->deleted_at))
                                            <div class="flex flex-col text-xs font-medium">
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{__('Deleted date')}}:</span>
                                                    <span class="text-black">{{ \Carbon\Carbon::parse($_candidate->deleted_at)->format('d-m-Y H:i') }}</span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <span class="text-gray-500">{{__('Deleted by')}}:</span>
                                                    <span class="text-black">{{$_candidate->personDidDelete->name}}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                </x-table.td>

                                <x-table.td>
                                    <span class="text-gray-900 text-sm font-medium bg-slate-100 px-2 py-2 rounded-lg">{{ $_candidate->structure->name }}</span>
                                </x-table.td>

                                <x-table.td>
                                    @php
                                        $_status_color = [
                                                2 => 'rose',
                                                3 => 'orange',
                                                4 => 'blue',
                                                5 => 'green'
                                        ];
                                    @endphp
                                    <div class="flex flex-col space-y-1">
                                        <div class="flex space-x-1 items-center">
                                            <span class="text-gray-500 text-sm font-medium">{{ __('Knowledge') }}:</span>
                                            <span class="text-sm font-medium px-2 py-1 rounded-lg bg-{{ $_status_color[$_candidate->knowledge_test] }}-100 text-{{ $_status_color[$_candidate->knowledge_test] }}-500">{{ $_candidate->knowledge_test }}</span>
                                        </div>
                                        <div class="flex space-x-1 items-center">
                                            <span class="text-gray-500 text-sm font-medium">{{ __('Physical fitness') }}:</span>
                                            <span class="text-sm font-medium px-2 py-1 rounded-lg bg-{{ $_status_color[$_candidate->physical_fitness_exam] }}-100 text-{{ $_status_color[$_candidate->physical_fitness_exam] }}-500">{{ $_candidate->physical_fitness_exam }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                    <div class="flex flex-col text-sm font-medium">
                                        <div class="flex items-center space-x-1">
                                            <span class="text-gray-500">{{__('Appeal date')}}:</span>
                                            <span class="text-black">{{ \Carbon\Carbon::parse($_candidate->appeal_date)->format('d.m.Y') }}</span>
                                        </div>
                                    </div>
                                </x-table.td>

                                <x-table.td>
                                        @php
                                            $_color = [
                                             10 => 'slate',
                                             20 => 'orange',
                                             30 => 'blue',
                                             70 => 'green',
                                             90 => 'rose'
                                        ];
                                        @endphp
                                        <span class="text-sm font-medium px-3 py-2 rounded-lg bg-{{ $_color[$_candidate->status_id] }}-100 text-{{ $_color[$_candidate->status_id] }}-500">{{ $_candidate->status->name }}</span>
                                </x-table.td>

                                <x-table.td :isButton="true">
                                    @if($status != 'deleted')
                                        {{-- @can('manage-candidates') --}}
                                        <a href="#" wire:click="openSideMenu('edit-candidate',{{ $_candidate->id }})" class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg text-gray-500 bg-gray-100 hover:bg-gray-200 hover:text-gray-700">
                                            @include('components.icons.profile-icon')
                                        </a>
                                        {{-- @endcan --}}
                                    @else
                                        <button
                                            wire:click="restoreData('{{$_candidate->id}}')"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-teal-50 hover:text-gray-700"
                                        >
                                            @include('components.icons.recover',['color' => 'text-teal-500','hover' => 'text-teal-600'])
                                        </button>
                                    @endif
                                </x-table.td>


                                <x-table.td :isButton="true">
                                    @if($status != 'deleted')
                                        {{-- @can('manage-candidates') --}}
                                        <button
                                            wire:click="setDeleteCandidate('{{ $_candidate->id }}')"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700"
                                        >
                                            @include('components.icons.delete-icon')
                                        </button>
                                        {{-- @endcan --}}
                                    @else
                                        <button
                                            wire:confirm="{{ __('Are you sure you want to remove this data?') }}"
                                            wire:click="forceDeleteData('{{ $_candidate->id }}')"
                                            class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                        >
                                            @include('components.icons.force-delete')
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
            {{ $_candidates->links() }}
        </div>
    </div>


    {{-- @can('manage-candidate') --}}
    <x-side-modal>
        @if($showSideMenu == 'add-candidate')
            @livewire('candidates.add-candidate')
        @endif

        @if($showSideMenu == 'edit-candidate')
            <livewire:candidates.edit-candidate :candidateModel="$modelName" />
        @endif
    </x-side-modal>
    {{-- @endcan --}}

    <div>
        @livewire('candidates.delete-candidate')
    </div>

    <x-datepicker :auto=false></x-datepicker>
</div>
