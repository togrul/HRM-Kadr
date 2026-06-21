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
    @php
        $canEditStaff = auth()->user()?->can('edit-staff') ?? false;
        $canDeleteStaff = auth()->user()?->can('delete-staff') ?? false;
    @endphp

    {{-- sidebar  --}}
    <x-slot name="sidebar">
        <livewire:structure.sidebar wire:key="staff-structure-sidebar" />
    </x-slot>
    {{-- end sidebar --}}

    {{-- ===================== Premium header ===================== --}}
    <div class="px-4 pt-4 sm:px-6">
        <x-page-header :title="__('staff::common.titles.staff_schedule')">
            <x-slot:icon>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 4v16M15 4v16"/></svg>
            </x-slot:icon>
            <x-slot:actions>
                @if ($selectedPage == 'all')
                    <x-pill-button variant="secondary" wire:click.prevent="showPage('vacancies')">
                        {{ __('staff::common.actions.get_all_vacancies') }}
                    </x-pill-button>
                @else
                    <x-pill-button variant="secondary" wire:click.prevent="showPage('all')">
                        {{ __('staff::common.actions.all_data') }}
                    </x-pill-button>
                @endif
                @can('add-staff')
                    <x-pill-button variant="primary" wire:click="openSideMenu('add-staff')"
                        title="{{ __('staff::common.actions.add_staff') }}" aria-label="{{ __('staff::common.actions.add_staff') }}">
                        <x-icons.add-icon color="text-white" hover="text-white" size="w-5 h-5" />
                        {{ __('staff::common.actions.add_staff') }}
                    </x-pill-button>
                @endcan
                @can('export-staff')
                    @if ($selectedPage == 'vacancies')
                        <x-pill-button variant="emerald" :icon="true" wire:click.prevent="exportExcel"
                            title="{{ __('staff::common.actions.export_excel') }}" aria-label="{{ __('staff::common.actions.export_excel') }}">
                            <x-icons.excel-icon />
                        </x-pill-button>
                    @endif
                @endcan
            </x-slot:actions>
        </x-page-header>
    </div>

    @if ($selectedPage == 'all')
        <div class="flex flex-col px-4 mt-4 space-y-4">
            @if ($staffs->isNotEmpty())
                <div class="grid grid-cols-1 gap-3">
                    @foreach ($staffs as $group)
                        <div wire:key="staff-group-{{ $group['structure_id'] }}">
                            <x-staff.root
                                :title="$group['title']"
                                :structureId="$group['structure_id']"
                                :hasParent="$group['has_parent']"
                                :total_sum="$group['total_sum']"
                                :total_filled="$group['total_filled']"
                                :total_vacant="$group['total_vacant']"
                                :canEditStaff="$canEditStaff"
                                :canDeleteStaff="$canDeleteStaff"
                            >
                                @foreach ($group['items'] as $st)
                                    <div wire:key="staff-item-{{ $st->id ?? ($group['structure_id'] . '-' . $loop->index) }}">
                                        <x-staff.item :hasParent="$group['has_parent']" :model="$st" />
                                    </div>
                                @endforeach
                            </x-staff.root>
                        </div>
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
                    <span class="font-medium text-gray-500">{{ __('staff::common.fields.count') }}:</span>
                    <span>{{ $staffs->count() }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="font-medium text-gray-500">{{ __('staff::common.fields.total') }}:</span>
                    <span>{{ $staffs->sum('vacant') }}</span>
                </div>
            </div>

            <div class="relative min-h-[300px] -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-visible">
                        <x-table.tbl :headers="[__('personnel::common.labels.number'), __('staff::common.fields.structure'), __('staff::common.fields.position'), __('staff::common.fields.vacant')]">
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
                <livewire:staff-schedule.add-staff
                    :selectedStructureId="$selectedStructureId"
                    :key="'staff-add-modal-' . ($selectedStructureId ?? 'all')"
                />
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
