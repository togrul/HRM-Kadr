@php
    $canEditStaff = auth()->user()?->can('edit-staff') ?? false;
    $canDeleteStaff = auth()->user()?->can('delete-staff') ?? false;
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    // The URL only carries the nested id list, so after a reload the highlight comes from its head.
    $panelSelectedId = $selectedStructureId ?? ($structure[0] ?? null);
@endphp

<div
    class="flex flex-col"
    x-data="{ editMode: false }"
>
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('staff::common.titles.staff_schedule')"
            :subtitle="$selectedPage == 'all' ? $num($staffSummary['total']).' '.__('staff::common.fields.staff_unit') : $num($staffs->sum('vacant')).' '.__('staff::common.fields.vacant_lower')"
        >
            @if ($selectedPage == 'all')
            <x-context-panel.section>
                <div class="px-2 pb-1.5 pt-1.5">
                    <x-context-panel.progress
                        :label="__('staff::common.fields.fill_rate')"
                        :value="$staffSummary['rate']"
                    />

                    <div class="mt-3">
                        <x-context-panel.meta :columns="3" :items="[
                            ['label' => __('staff::common.fields.total'), 'value' => $num($staffSummary['total'])],
                            ['label' => __('staff::common.fields.filled'), 'value' => $num($staffSummary['filled']), 'dot' => 'bg-[#10b981]'],
                            ['label' => __('staff::common.fields.vacant'), 'value' => $num($staffSummary['vacant']), 'dot' => $staffSummary['vacant'] > 0 ? 'bg-[#f43f5e]' : 'bg-[#a1a1aa]'],
                        ]" />
                    </div>
                </div>
            </x-context-panel.section>

            @endif

            @if ($selectedPage != 'all')
                <x-context-panel.section :title="__('staff::common.actions.get_all_vacancies')">
                    <div class="px-2 pb-1.5 pt-1.5">
                        <x-context-panel.meta :columns="2" :items="[
                            ['label' => __('staff::common.fields.count'), 'value' => $num($staffs->count())],
                            ['label' => __('staff::common.fields.vacant'), 'value' => $num($staffs->sum('vacant')), 'dot' => 'bg-[#f43f5e]'],
                        ]" />
                    </div>
                </x-context-panel.section>
            @endif

            @if (! empty($staffTree))
                {{-- the panel keeps its own expand state: a teleport lands outside the page's Alpine scope --}}
                <x-context-panel.section :title="__('staff::common.fields.structure')">
                    <div
                        x-data="{
                            open: {},
                            isOpen(id) { return this.open[id] !== false },
                            toggle(id) { this.open[id] = (this.open[id] === false) },
                        }"
                        class="space-y-0.5 px-0.5"
                    >
                        @if (! empty($structure))
                            <button type="button" wire:click.prevent="clearStructure"
                                class="flex h-[30px] w-full items-center gap-1.5 rounded-lg px-2.5 text-left text-[14px] font-medium text-ink-muted transition hover:bg-[#fafafa] hover:text-ink">
                                &larr; {{ __('staff::common.actions.show_all') }}
                            </button>
                        @endif

                        @foreach ($staffTree as $node)
                            <x-staff.panel-node
                                wire:key="staff-panel-node-{{ $node['id'] }}"
                                :node="$node"
                                :depth="0"
                                :selected="$panelSelectedId"
                            />
                        @endforeach
                    </div>
                </x-context-panel.section>
            @endif

            <x-slot name="footer">
                <p class="text-[11.5px] leading-snug text-ink-faint">{{ __('staff::common.fields.tree_hint') }}</p>
            </x-slot>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('staff::common.titles.staff_schedule')"
        :breadcrumb="__('staff::common.titles.staff_schedule')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M9 4v16M15 4v16"/></svg>
        </x-slot:icon>

        @if ($selectedPage == 'all')
            <x-slot:stats>
                <x-page-header.stat :value="$num($staffSummary['total'])" :label="__('staff::common.fields.total')" />
                <x-page-header.stat :value="$num($staffSummary['filled'])" :label="__('staff::common.fields.filled')" tone="green" />
                <x-page-header.stat :value="$num($staffSummary['vacant'])" :label="__('staff::common.fields.vacant')" tone="rose" />
            </x-slot:stats>
        @else
            <x-slot:stats>
                <x-page-header.stat :value="$num($staffs->count())" :label="__('staff::common.fields.position')" />
                <x-page-header.stat :value="$num($staffs->sum('vacant'))" :label="__('staff::common.fields.vacant')" tone="rose" />
            </x-slot:stats>
        @endif

        <x-slot:actions>
            @if ($selectedPage == 'all')
                <x-pill-button wire:click="{{ $staffAllOpen ? 'collapseAllNodes' : 'expandAllNodes' }}">
                    {{ $staffAllOpen ? __('staff::common.actions.collapse_all') : __('staff::common.actions.expand_all') }}
                </x-pill-button>
                <x-pill-button wire:click.prevent="showPage('vacancies')">
                    {{ __('staff::common.actions.get_all_vacancies') }}
                </x-pill-button>
                @if ($canEditStaff || $canDeleteStaff)
                    <x-pill-button
                        x-on:click="editMode = ! editMode"
                        x-bind:class="editMode ? 'border-ink bg-ink text-white' : ''"
                    >
                        <span class="relative inline-flex h-4 w-7 items-center rounded-full transition-colors"
                            x-bind:class="editMode ? 'bg-white/30' : 'bg-hairline'">
                            <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow transition-transform"
                                x-bind:class="editMode ? 'translate-x-3.5' : 'translate-x-0.5'"></span>
                        </span>
                        {{ __('staff::common.actions.edit_mode') }}
                    </x-pill-button>
                @endif
            @else
                <x-pill-button wire:click.prevent="showPage('all')">
                    {{ __('staff::common.actions.all_data') }}
                </x-pill-button>
                @can('export-staff')
                    <x-pill-button variant="emerald" :icon="true" wire:click.prevent="exportExcel"
                        wire:loading.attr="disabled" wire:target="exportExcel"
                        title="{{ __('staff::common.actions.export_excel') }}">
                        <x-icons.excel-icon />
                    </x-pill-button>
                @endcan
            @endif
            @can('add-staff')
                <x-pill-button variant="primary" wire:click="openSideMenu('add-staff')">
                    <x-icons.add-icon color="text-white" hover="text-white" size="w-4 h-4" />
                    {{ __('staff::common.actions.add_staff') }}
                </x-pill-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($selectedPage == 'all')
        @if (! empty($staffTree))
            {{-- column header (widths mirror staff.tree-node rows) --}}
            <div class="flex items-center gap-3 border-b border-hairline bg-white px-4 py-2.5">
                <div class="hrm-eyebrow min-w-0 flex-1">{{ __('staff::common.fields.structure') }} / {{ __('staff::common.fields.position') }}</div>
                <div class="hrm-eyebrow w-12 shrink-0 text-center">{{ __('staff::common.fields.total') }}</div>
                <div class="hrm-eyebrow w-12 shrink-0 text-center">{{ __('staff::common.fields.filled') }}</div>
                <div class="hrm-eyebrow w-12 shrink-0 text-center">{{ __('staff::common.fields.vacant') }}</div>
                <div class="hrm-eyebrow w-[108px] shrink-0 text-right" x-show="editMode" x-cloak>{{ __('staff::common.fields.operations') }}</div>
            </div>

            <div class="bg-white">
                @foreach ($staffTree as $node)
                    <x-staff.tree-node wire:key="staff-node-{{ $node['id'] }}" :node="$node" :depth="0" :open-ids="$openNodes ?? []" />
                @endforeach
            </div>
        @else
            <x-table.empty :rows="4" />
        @endif
    @endif

    {{-- vacancy page --}}
    @if ($selectedPage == 'vacancies')
        <x-table.tbl :headers="[__('personnel::common.labels.number'), __('staff::common.fields.structure'), __('staff::common.fields.position'), __('staff::common.fields.vacant')]">
            @forelse ($staffs as $staff)
                <tr wire:key="staff-vacancy-{{ $staff->id }}">
                    <x-table.td><span class="hrm-num text-[12px] text-ink-faint">{{ $loop->iteration }}</span></x-table.td>
                    <x-table.td standart-width>
                        <span class="block max-w-[420px] truncate text-[13px] text-ink-soft">{{ $staff->structure?->name }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-[13px] font-medium text-ink">{{ $staff->position?->name }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="hrm-num text-[13px] font-semibold text-[#e11d48]">{{ $staff->vacant }}</span>
                    </x-table.td>
                </tr>
            @empty
                <x-table.empty :rows="4" />
            @endforelse
        </x-table.tbl>
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
