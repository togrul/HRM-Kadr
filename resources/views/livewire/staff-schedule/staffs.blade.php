<div class="flex flex-col" x-data x-init="paginator = document.querySelector('span[aria-current=page]>span');
if (paginator != null) {
    paginator.classList.add('bg-blue-50', 'text-blue-600')
}
Livewire.hook('message.processed', (message, component) => {
    const paginator = document.querySelector('span[aria-current=page]>span')
    if (
        ['gotoPage', 'previousPage', 'nextPage', 'resetFilter'].includes(message.updateQueue[0].payload.method) || ['openSideMenu', 'closeSideMenu', 'staffAdded', 'staffWasDeleted'].includes(message.updateQueue[0].payload.event)
    ) {
        if (paginator != null) {
            paginator.classList.add('bg-green-100', 'text-green-600')
        }
    }
})">
    {{-- sidebar  --}}
    <x-slot name="sidebar">
        @livewire('structure.sidebar')
    </x-slot>
    {{-- end sidebar --}}

    <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 px-6 py-4">
        <div class="flex flex-col space-y-1 pl-3">
            @if ($selectedPage == 'all')
                <button wire:click="showPage('vacancies')"
                    class="flex items-center justify-center shadow-sm transition-all duration-300 rounded-lg bg-slate-900 text-white hover:bg-slate-200 hover:text-slate-900 space-x-2 px-4 py-2"
                    type="button">
                    <span>{{ __('Get all vacancies') }}</span>
                </button>
            @endif
            @if ($selectedPage == 'vacancies')
                <button wire:click="showPage('all')"
                    class="flex items-center justify-center shadow-sm transition-all duration-300 rounded-xl bg-slate-900 text-white hover:bg-slate-200 hover:text-slate-900 space-x-2 px-4 py-2"
                    type="button">
                    <span>{{ __('All data') }}</span>
                </button>
            @endif
        </div>

        <div class="flex justify-end items-center space-x-2">
            @can('add-staff')
                <button wire:click="openSideMenu('add-staff')"
                    class="flex items-center justify-center transition-all duration-300 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 space-x-2 p-2"
                    type="button">
                    @include('components.icons.add-icon')
                </button>
            @endcan
            @can('export-staff')
                <button
                    class="flex items-center justify-center transition-all duration-300 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-100 space-x-2 p-2"
                    type="button">
                    @include('components.icons.print-file', [
                        'color' => 'text-rose-400',
                        'hover' => 'text-rose-500',
                    ])
                </button>
            @endcan
            @can('export-staff')
                @if ($selectedPage == 'vacancies')
                    <button wire:click.prevent="exportExcel"
                        class="flex items-center justify-center rounded-xl transition-all duration-300 bg-green-50 text-green-500 hover:bg-green-100 space-x-2 p-2"
                        type="button">
                        <x-icons.excel-icon />
                    </button>
                @endif
            @endcan
        </div>
    </div>

    @if ($selectedPage == 'all')
        <div class="flex flex-col space-y-4 px-4 mt-4">
            @if ($staffs->isNotEmpty())
                <div class="grid grid-cols-1 gap-3">
                    @foreach ($staffs as $str => $stf)
                        @php
                            $structure = $stf[0]->structure;
                            $hasParent = !empty($structure->parent_id);
                            $total_sum = $stf->sum('total');
                            $total_filled = $stf->sum('filled');
                            $total_vacant = $stf->sum('vacant');
                        @endphp
                        <x-staff.root :title="$str" :structureId="$stf[0]->structure_id" :$hasParent :$total_sum :$total_filled
                            :$total_vacant>
                            @foreach ($stf as $st)
                                <x-staff.item :$hasParent :model="$st" />
                            @endforeach
                        </x-staff.root>
                    @endforeach
                </div>
            @else
                <x-table.empty :rows="5" />
            @endif
        </div>
    @endif

    {{-- vacancy page --}}
    @if ($selectedPage == 'vacancies')
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
                        <x-table.tbl :headers="[__('#'), __('Structure'), __('Position'), __('Vacant')]">
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
            @if ($showSideMenu == 'add-staff')
                <livewire:staff-schedule.add-staff />
            @endif
        @endcan

        @can('edit-staff')
            @if ($showSideMenu == 'edit-staff')
                <livewire:staff-schedule.edit-staff :staffModel="$modelName" />
            @endif
        @endcan

        @can('show-staff')
            @if ($showSideMenu == 'show-staff')
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
