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
            ['gotoPage','previousPage','nextPage','resetFilter'].includes(message.updateQueue[0].payload.method)
            || ['openSideMenu','closeSideMenu','staffAdded','staffWasDeleted'].includes(message.updateQueue[0].payload.event)
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

    <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 px-6 py-4">
        <div class="flex flex-col space-y-1">
            @if($selectedPage == 'all')
            <button wire:click="showPage('vacancies')" class="flex items-center justify-center shadow-sm transition-all duration-300 rounded-xl bg-blue-100 text-blue-500 hover:bg-slate-200 space-x-2 p-2" type="button">
                <span>{{ __('Get all vacancies') }}</span>
            </button>
            @endif
            @if($selectedPage == 'vacancies')
            <button wire:click="showPage('all')" class="flex items-center justify-center shadow-sm transition-all duration-300 rounded-xl bg-blue-100 text-blue-500 hover:bg-slate-200 space-x-2 p-2" type="button">
                <span>{{ __('All data') }}</span>
            </button>
            @endif
        </div>

        <div class="flex justify-end items-center space-x-2">
            @can('add-staff')
            <button wire:click="openSideMenu('add-staff')" class="flex items-center justify-center transition-all duration-300 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 space-x-2 p-2" type="button">
                @include('components.icons.add-icon')
            </button>
            @endcan
            @can('export-staff')
            <button class="flex items-center justify-center transition-all duration-300 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-100 space-x-2 p-2" type="button">
                @include('components.icons.print-file',['color' => 'text-rose-400', 'hover' => 'text-rose-500'])
            </button>
            @endcan
            @can('export-staff')
            @if($selectedPage == 'vacancies')
            <button wire:click.prevent="exportExcel" class="flex items-center justify-center rounded-xl transition-all duration-300 bg-green-50 text-green-500 hover:bg-green-100 space-x-2 p-2" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"  viewBox="0 0 50 50" class="w-6 h-6 fill-green-400 transition-all duration-300 hover:fill-green-500">
                    <path d="M 28.875 0 C 28.855469 0.0078125 28.832031 0.0195313 28.8125 0.03125 L 0.8125 5.34375 C 0.335938 5.433594 -0.0078125 5.855469 0 6.34375 L 0 43.65625 C -0.0078125 44.144531 0.335938 44.566406 0.8125 44.65625 L 28.8125 49.96875 C 29.101563 50.023438 29.402344 49.949219 29.632813 49.761719 C 29.859375 49.574219 29.996094 49.296875 30 49 L 30 44 L 47 44 C 48.09375 44 49 43.09375 49 42 L 49 8 C 49 6.90625 48.09375 6 47 6 L 30 6 L 30 1 C 30.003906 0.710938 29.878906 0.4375 29.664063 0.246094 C 29.449219 0.0546875 29.160156 -0.0351563 28.875 0 Z M 28 2.1875 L 28 6.53125 C 27.867188 6.808594 27.867188 7.128906 28 7.40625 L 28 42.8125 C 27.972656 42.945313 27.972656 43.085938 28 43.21875 L 28 47.8125 L 2 42.84375 L 2 7.15625 Z M 30 8 L 47 8 L 47 42 L 30 42 L 30 37 L 34 37 L 34 35 L 30 35 L 30 29 L 34 29 L 34 27 L 30 27 L 30 22 L 34 22 L 34 20 L 30 20 L 30 15 L 34 15 L 34 13 L 30 13 Z M 36 13 L 36 15 L 44 15 L 44 13 Z M 6.6875 15.6875 L 12.15625 25.03125 L 6.1875 34.375 L 11.1875 34.375 L 14.4375 28.34375 C 14.664063 27.761719 14.8125 27.316406 14.875 27.03125 L 14.90625 27.03125 C 15.035156 27.640625 15.160156 28.054688 15.28125 28.28125 L 18.53125 34.375 L 23.5 34.375 L 17.75 24.9375 L 23.34375 15.6875 L 18.65625 15.6875 L 15.6875 21.21875 C 15.402344 21.941406 15.199219 22.511719 15.09375 22.875 L 15.0625 22.875 C 14.898438 22.265625 14.710938 21.722656 14.5 21.28125 L 11.8125 15.6875 Z M 36 20 L 36 22 L 44 22 L 44 20 Z M 36 27 L 36 29 L 44 29 L 44 27 Z M 36 35 L 36 37 L 44 37 L 44 35 Z"></path>
                </svg>
            </button>
            @endif
            @endcan
        </div>
    </div>

    @if($selectedPage == 'all')
    <div class="flex flex-col space-y-4 px-4 mt-4">
        @if(count($staffs) > 0)
        <div class="grid grid-cols-1 gap-3">
            @foreach ($staffs as $str => $stf)
            @php
                $total_sum = 0;
                $total_filled = 0;
                $total_vacant = 0;
            @endphp
            <div @class([
                "rounded-xl px-4 py-3 border-2 border-gray-300 flex flex-col space-y-2",
                // "" => !empty($stf->structure->parent_id)
            ])>
                <div class="flex items-center justify-between bg-gray-900 rounded-xl px-4 py-2">
                    <span></span>
                    <h1 class="text-lg font-medium flex items-center">{!! $str !!} </h1>
                    <div class="flex space-x-2 items-center">
                        @can('edit-staff')
                            <button wire:click="openSideMenu('edit-staff',{{ $stf[0]->structure_id }})" class="appearance-none w-8 h-8 flex justify-center items-center rounded-lg bg-slate-700 transition-all duration-300 hover:bg-slate-800">
                                @include('components.icons.edit-icon',['color' => 'text-slate-100', 'hover' => 'text-slate-200'])
                            </button>
                        @endcan
                        @can('delete-staff')
                            <button wire:click.prevent="setDeleteStaff({{ $stf[0]->structure_id  }})" class="appearance-none w-8 h-8 flex justify-center items-center rounded-lg bg-slate-700 transition-all duration-300 hover:bg-slate-800">
                                @include('components.icons.delete-icon',['color' => 'text-rose-400', 'hover' => 'text-rose-300'])
                            </button>
                        @endcan
                    </div>
                </div>

                    <div @class([
                        'grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 divide-x divide-gray-300' => !empty($stf[0]->structure->parent_id),
                    ])>
                      <div class="md:col-span-2">

                        <table class="w-full">
                            <thead>
                                <tr>
                                    @if(!empty($stf[0]->structure->parent_id))
                                    <th class="py-3 text-xs font-semibold tracking-wider text-left text-gray-400 uppercase">{{ __('Position') }}</th>
                                    @endif
                                    <th class="py-3 text-xs font-semibold tracking-wider text-left text-gray-400 uppercase">{{ __('Total') }}</th>
                                    <th class="py-3 text-xs font-semibold tracking-wider text-left text-gray-400 uppercase">{{ __('Filled') }}</th>
                                    <th class="py-3 text-xs font-semibold tracking-wider text-left text-gray-400 uppercase">{{ __('Vacant') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($stf as $st)
                                @php
                                    $total_sum += $st->total;
                                    $total_filled += $st->filled;
                                    $total_vacant += $st->vacant;
                                @endphp
                                <tr>
                                    @if(!empty($st->structure->parent_id))
                                    <td>{{ $st->position->name }}</td>
                                    @endif
                                    <td>
                                        <div class="w-10 h-10 rounded-lg flex justify-center items-center bg-slate-100 text-lg">
                                            <span class="text-blue-500">{{ $st->total }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <button
                                            @if(!empty($st->structure->parent_id))
                                                wire:click="openSideMenu('show-staff',{{ $st->structure_id }},{{ $st->position_id }})"
                                            @endif
                                            class="appearance-none w-10 h-10 relative rounded-lg flex justify-center items-center bg-slate-100 text-lg">
                                            <span class="text-rose-500">{{ $st->filled }}</span>
                                        </button>
                                    </td>
                                    <td>
                                        <div class="w-10 h-10 rounded-lg flex justify-center items-center bg-slate-100 text-lg">
                                            <span class="text-green-500">{{ $st->vacant }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        </div>
                         @if(!empty($st->structure->parent_id))
                         <div class="px-6 flex flex-col space-y-2">
                             <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-500">{{ __('Total count') }}</span>
                                <span class="text-blue-600 text-xl font-medium">{{ $total_sum }}</span>
                             </div>
                             <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-500">{{ __('Total filled') }}</span>
                                <span class="text-rose-500 text-xl font-medium">{{ $total_filled }}</span>
                             </div>
                             <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-500">{{ __('Total vacant') }}</span>
                                <span class="text-green-500 text-xl font-medium">{{ $total_vacant }}</span>
                             </div>
                         </div>
                          @endif
                    </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="w-full rounded-lg bg-slate-50 flex justify-center items-center px-8 py-6 flex-col space-y-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-16 h-16 text-slate-500">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <h1 class="text-lg text-slate-700">{{ __('No data exist!') }}</h1>
        </div>
        @endif

    </div>
    @endif

    {{-- vacancy page --}}
    @if($selectedPage == 'vacancies')
    <div class="flex flex-col space-y-2 px-6">
        <div class="flex space-x-4 items-center">
            <div class="flex space-x-2 items-center">
                <span class="text-gray-500 font-medium">{{ __('Count') }}:</span>
                <span>{{ $staffs->count() }}</span>
            </div>
            <div class="flex space-x-2 items-center">
                <span class="text-gray-500 font-medium">{{ __('Total') }}:</span>
                <span>{{ $staffs->sum('vacant') }}</span>
            </div>
        </div>

        <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <x-table.tbl :headers="[__('#'),__('Structure'),__('Position'),__('Vacant')]">
                    @foreach ($staffs as $staff)
                        <tr>
                            <x-table.td>
                                <span class="text-sm font-medium">
                                    {{ $loop->iteration }}
                               </span>
                            </x-table.td>

                            <x-table.td>
                                <span class="text-sm font-medium">
                                    {{ $staff->structure->name }}
                               </span>
                            </x-table.td>

                            <x-table.td>
                                <span class="text-sm font-medium">
                                    {{ $staff->position->name }}
                               </span>
                            </x-table.td>

                           <x-table.td>
                                <span class="text-sm font-normal text-gray-700">
                                    {{ $staff->vacant }}
                                </span>
                            </x-table.td>

                        </tr>
                    @endforeach
                </x-table.tbl>

            </div>
            </div>
        </div>

    </div>
    @endif

    <x-side-modal>
        @can('add-staff')
            @if($showSideMenu == 'add-staff')
                <livewire:staff-schedule.add-staff />
            @endif
        @endcan

        @can('edit-staff')
            @if($showSideMenu == 'edit-staff')
                <livewire:staff-schedule.edit-staff :staffModel="$modelName" />
            @endif
        @endcan

        @can('show-staff')
            @if($showSideMenu == 'show-staff')
                <livewire:staff-schedule.show-staff :structureModel="$modelName" :positionModel="$secondModel" />
            @endif
        @endcan
   </x-side-modal>
   {{-- @endcan --}}
   @can('delete-staff')
       <div>
            <livewire:staff-schedule.delete-staff />
       </div>
   @endcan
</div>
