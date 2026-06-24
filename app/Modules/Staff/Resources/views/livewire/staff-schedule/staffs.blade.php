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
        <div class="px-4 pt-4 sm:px-6">
            @if (! empty($staffTree))
                <div
                    wire:key="staff-tree"
                    x-data="{
                        open: {},
                        editMode: false,
                        isOpen(id) { return this.open[id] !== false },
                        toggle(id) { this.open[id] = (this.open[id] === false) },
                        expandAll() { Object.keys(this.open).forEach(k => this.open[k] = true) },
                        collapseAll() { Object.keys(this.open).forEach(k => this.open[k] = false) },
                    }"
                    x-init="@js($staffTreeIds).forEach(id => open[id] = true)"
                    class="overflow-hidden rounded-2xl border border-zinc-200/70 bg-white shadow-sm"
                >
                    {{-- toolbar (tree controls — separate from the page header above) --}}
                    <div class="flex flex-col gap-3 border-b border-zinc-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[13px] text-zinc-500">{{ __('staff::common.fields.tree_hint') }}</p>
                        <div class="flex shrink-0 items-center gap-2">
                            <button type="button" x-on:click="expandAll()"
                                class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-[13px] font-medium text-zinc-600 transition-colors hover:bg-zinc-50">
                                {{ __('staff::common.actions.expand_all') }}
                            </button>
                            <button type="button" x-on:click="collapseAll()"
                                class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-[13px] font-medium text-zinc-600 transition-colors hover:bg-zinc-50">
                                {{ __('staff::common.actions.collapse_all') }}
                            </button>
                            @if ($canEditStaff || $canDeleteStaff)
                                <button type="button" x-on:click="editMode = ! editMode"
                                    :class="editMode ? 'border-blue-500 bg-blue-500 text-white' : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50'"
                                    class="flex items-center gap-2 rounded-lg border px-3 py-1.5 text-[13px] font-medium transition-colors">
                                    <span class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors" :class="editMode ? 'bg-white/30' : 'bg-zinc-300'">
                                        <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition-transform" :class="editMode ? 'translate-x-3.5' : 'translate-x-0.5'"></span>
                                    </span>
                                    {{ __('staff::common.actions.edit_mode') }}
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- column header (widths mirror staff.tree-node rows) --}}
                    <div class="flex items-center gap-3 border-b border-zinc-100 bg-zinc-50/70 px-3 py-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-400">
                        <div class="min-w-0 flex-1">{{ __('staff::common.fields.structure') }} / {{ __('staff::common.fields.position') }}</div>
                        <div class="hidden w-28 shrink-0 text-center sm:block">{{ __('staff::common.fields.fill_rate') }}</div>
                        <div class="w-12 shrink-0 text-center">{{ __('staff::common.fields.total') }}</div>
                        <div class="w-12 shrink-0 text-center">{{ __('staff::common.fields.filled') }}</div>
                        <div class="w-12 shrink-0 text-center">{{ __('staff::common.fields.vacant') }}</div>
                        <div class="w-12 shrink-0 text-center">{{ __('staff::common.fields.percent') }}</div>
                        <div class="w-[108px] shrink-0 text-right" x-show="editMode" x-cloak>{{ __('staff::common.fields.operations') }}</div>
                    </div>

                    {{-- tree --}}
                    <div>
                        @foreach ($staffTree as $node)
                            <x-staff.tree-node wire:key="staff-node-{{ $node['id'] }}" :node="$node" :depth="0" />
                        @endforeach
                    </div>
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
