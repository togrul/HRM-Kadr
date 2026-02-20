<div 
    class="flex flex-col" 
    x-data 
    x-init="
        const root = $el;
        const paintPaginator = (isUpdate = false) => {
            const paginator = root.querySelector('span[aria-current=page]>span');
            if (!paginator) return;
            paginator.classList.remove('bg-blue-50', 'text-blue-600', 'bg-green-100', 'text-green-600');
            paginator.classList.add(isUpdate ? 'bg-green-100' : 'bg-blue-50', isUpdate ? 'text-green-600' : 'text-blue-600');
        };
        paintPaginator();
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('commit', ({ component, succeed }) => {
                if (component.id !== $wire.__instance.id) return;
                succeed(() => queueMicrotask(() => paintPaginator(true)));
            });
        }
    ">
    {{-- sidebar  --}}
    <x-slot name="sidebar">
        <livewire:structure.sidebar wire:key="staff-structure-sidebar" />
    </x-slot>
    {{-- end sidebar --}}

    <div class="flex flex-col items-center justify-between px-6 py-4 space-y-4 sm:flex-row">
        <div class="flex flex-col pl-3 space-y-1">
            @if ($selectedPage == 'all')
                <button wire:click.prevent="showPage('vacancies')"
                    class="flex items-center justify-center px-4 py-2 space-x-2 text-white transition-all duration-300 rounded-lg shadow-sm bg-slate-900 hover:bg-slate-200 hover:text-slate-900"
                    type="button">
                    <span>{{ __('Get all vacancies') }}</span>
                </button>
            @endif
            @if ($selectedPage == 'vacancies')
                <button wire:click.prevent="showPage('all')"
                    class="flex items-center justify-center px-4 py-2 space-x-2 text-white transition-all duration-300 shadow-sm rounded-xl bg-slate-900 hover:bg-slate-200 hover:text-slate-900"
                    type="button">
                    <span>{{ __('All data') }}</span>
                </button>
            @endif
        </div>

        <div class="flex items-center justify-end space-x-2">
            @can('add-staff')
                <button wire:click="openSideMenu('add-staff')"
                    class="flex items-center justify-center p-2 space-x-2 transition-all duration-300 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200"
                    type="button">
                    @include('components.icons.add-icon')
                </button>
            @endcan
            @can('export-staff')
                <button
                    class="flex items-center justify-center p-2 space-x-2 transition-all duration-300 rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-100"
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
                        class="flex items-center justify-center p-2 space-x-2 text-green-500 transition-all duration-300 rounded-xl bg-green-50 hover:bg-green-100"
                        type="button">
                        <x-icons.excel-icon />
                    </button>
                @endif
            @endcan
        </div>
    </div>

    @if ($selectedPage == 'all')
        <div class="flex flex-col px-4 mt-4 space-y-4">
            @if ($staffs->isNotEmpty())
                <div class="grid grid-cols-1 gap-3">
                    @foreach ($staffs as $group)
                        <x-staff.root
                            :title="$group['title']"
                            :structureId="$group['structure_id']"
                            :hasParent="$group['has_parent']"
                            :total_sum="$group['total_sum']"
                            :total_filled="$group['total_filled']"
                            :total_vacant="$group['total_vacant']"
                        >
                            @foreach ($group['items'] as $st)
                                <x-staff.item :hasParent="$group['has_parent']" :model="$st" />
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
        <div class="flex flex-col px-6 space-y-2">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <span class="font-medium text-gray-500">{{ __('Count') }}:</span>
                    <span>{{ $staffs->count() }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="font-medium text-gray-500">{{ __('Total') }}:</span>
                    <span>{{ $staffs->sum('vacant') }}</span>
                </div>
            </div>

            <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-visible">
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
                <livewire:staff-schedule.add-staff wire:key="staff-add-modal" />
            @endif
        @endcan

        @can('edit-staff')
            @if ($showSideMenu == 'edit-staff')
                <livewire:staff-schedule.edit-staff
                    :staffModel="$modelName"
                    :key="'staff-edit-staff-modal-' . ($modelName ?? 'none')"
                />
            @endif
        @endcan

        @can('show-staff')
            @if ($showSideMenu == 'show-staff')
                <livewire:staff-schedule.show-staff
                    :structureModel="$modelName"
                    :positionModel="$secondModel"
                    :key="'staff-show-staff-modal-' . ($modelName ?? 'none') . '-' . ($secondModel ?? 'none')"
                />
            @endif
        @endcan
    </x-side-modal>
    {{-- @endcan --}}
    @can('delete-staff')
        <div>
            <livewire:staff-schedule.delete-staff wire:key="staff-delete-modal" />
        </div>
    @endcan
</div>
