@php
    $summary = $this->summary;
    $activeStatus = data_get($filter, 'business_trip_status', 'all');
    $statusFilters = [
        'all' => ['label' => __('business_trips::common.filters.all'), 'dot' => 'bg-[#a1a1aa]', 'count' => $summary['all']],
        'in_business_trip' => ['label' => __('business_trips::common.filters.in_business_trip'), 'dot' => 'bg-[#0ea5e9]', 'count' => $summary['in_business_trip']],
        'at_work' => ['label' => __('business_trips::common.filters.at_work'), 'dot' => 'bg-[#10b981]', 'count' => $summary['at_work']],
        'deleted' => ['label' => __('business_trips::common.filters.deleted'), 'dot' => 'bg-[#f43f5e]', 'count' => $summary['deleted']],
    ];
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $isDeletedView = \Illuminate\Support\Arr::get($search, 'business_trip_status', '') === 'deleted';
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('business_trips::common.table.title')"
            :subtitle="$num($summary['all']).' '.__('business_trips::common.table.unit')"
        >
            <x-context-panel.section>
                @foreach ($statusFilters as $value => $option)
                    <x-context-panel.item
                        wire:key="trip-panel-status-{{ $value }}"
                        wire:click.prevent="setStatus('{{ $value }}')"
                        wire:loading.attr="disabled"
                        wire:target="setStatus"
                        :active="$activeStatus === $value"
                        :dot="$option['dot']"
                        :count="$num($option['count'])"
                    >{{ $option['label'] }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            {{-- destinations, straight from the trips' own location column --}}
            @if ($this->locationFilters !== [])
                <x-context-panel.section :title="__('business_trips::common.filters.locations')">
                    @if ($selectedLocation)
                        <x-context-panel.item wire:click.prevent="selectLocation('')">
                            &larr; {{ __('business_trips::common.filters.show_all') }}
                        </x-context-panel.item>
                    @endif

                    @foreach ($this->locationFilters as $_location)
                        <x-context-panel.item
                            wire:key="trip-panel-location-{{ md5($_location['key']) }}"
                            wire:click.prevent="selectLocation('{{ addslashes($_location['key']) }}')"
                            wire:loading.attr="disabled"
                            wire:target="selectLocation"
                            :active="(string) $selectedLocation === $_location['key']"
                            :count="$num($_location['count'])"
                        >{{ $_location['key'] }}</x-context-panel.item>
                    @endforeach
                </x-context-panel.section>
            @endif

            <x-slot name="footer">
                <button type="button" wire:click="resetFilter" class="text-[12px] font-medium text-ink-muted transition hover:text-ink">
                    {{ __('business_trips::common.filters.reset') }}
                </button>
            </x-slot>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('business_trips::common.table.title')"
        :breadcrumb="__('business_trips::common.table.title')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5"/><path d="M21 3 9 15"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($summary['all'])" :label="__('business_trips::common.table.unit')" />
            <x-page-header.stat :value="$num($summary['in_business_trip'])" :label="__('business_trips::common.filters.in_business_trip')" tone="blue" />
            <x-page-header.stat :value="$num($summary['at_work'])" :label="__('business_trips::common.filters.at_work')" tone="green" />
        </x-slot:stats>

        <x-slot:actions>
            @can('review-self-service-requests')
                <x-ui.self-service-review-link />
            @endcan
            <x-pill-button wire:click="resetFilter">{{ __('business_trips::common.filters.reset') }}</x-pill-button>
            @can('export-business_trips')
                <x-pill-button variant="emerald" :icon="true" wire:click.prevent="exportExcel"
                    wire:loading.attr="disabled" wire:target="exportExcel"
                    title="{{ __('business_trips::common.actions.export_excel') }}">
                    <x-icons.excel-icon />
                </x-pill-button>
            @endcan
        </x-slot:actions>

        {{-- toolbar --}}
        <div class="flex flex-wrap items-end gap-3">
            <label class="w-full flex-1 sm:max-w-[300px]">
                <span class="hrm-eyebrow block pb-1">{{ __('business_trips::common.filters.fullname') }}</span>
                <x-livewire-input mode="gray" name="filter.fullname" wire:model="filter.fullname" />
            </label>

            <div class="shrink-0">
                <span class="hrm-eyebrow block pb-1">{{ __('business_trips::common.filters.date_range') }}</span>
                <div class="flex items-center gap-2">
                    <input type="date" wire:model.live="filter.date.min"
                        aria-label="{{ __('business_trips::common.filters.date_start') }}"
                        class="hrm-num h-[34px] w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12.5px] text-ink focus:border-ink focus:bg-white focus:ring-0" />
                    <span class="shrink-0 text-ink-faint">&ndash;</span>
                    <input type="date" wire:model.live="filter.date.max"
                        aria-label="{{ __('business_trips::common.filters.date_end') }}"
                        class="hrm-num h-[34px] w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12.5px] text-ink focus:border-ink focus:bg-white focus:ring-0" />
                </div>
            </div>

            <div class="min-w-[190px] flex-1">
                <span class="hrm-eyebrow block pb-1">{{ __('business_trips::common.filters.structure') }}</span>
                <x-ui.select-dropdown
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="filter.structure_id"
                    :model="$this->structureOptions"
                    search-model="searchStructure"
                />
            </div>

            <div class="min-w-[170px] flex-1">
                <span class="hrm-eyebrow block pb-1">{{ __('business_trips::common.filters.order_types') }}</span>
                <x-ui.select-dropdown
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="filter.order_type_id"
                    :model="$this->orderTypeOptions"
                />
            </div>

            <x-pill-button variant="primary" wire:click="searchFilter" class="!h-[34px]">{{ __('business_trips::common.filters.search') }}</x-pill-button>
        </div>
    </x-page-header>

    {{-- ===================== table ===================== --}}
    <x-table.tbl :headers="$this->getTableHeaders()">
        @forelse ($this->businessTrips as $_bTrip)
            @php
                $tripAttributes = is_array($_bTrip->attributes) ? $_bTrip->attributes : [];
                $tripRank = data_get($tripAttributes, '$rank.value') ?: null;
                $tripFullname = data_get($tripAttributes, '$fullname.value') ?: ($_bTrip->personnel?->fullname ?? '—');
                $tripStructure = data_get($tripAttributes, '$structure.value') ?: ($_bTrip->personnel?->structure?->name ?? '—');
            @endphp
            <tr wire:key="trip-row-{{ $_bTrip->id }}" @class(['bg-[#f0f9ff]/50' => $_bTrip->is_active_trip])>
                <x-table.td standart-width>
                    <div class="flex items-center gap-2.5">
                        <x-avatar :name="(string) $tripFullname" :tone="$_bTrip->is_active_trip ? 'blue' : 'neutral'" />
                        <div class="min-w-0 max-w-[240px] leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $tripFullname }}</p>
                            <p class="truncate text-[11px] text-ink-faint">{{ $tripRank ?: $tripStructure }}</p>
                        </div>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="flex flex-col leading-tight">
                        <span class="hrm-num text-[13px] font-medium text-ink">
                            {{ \Carbon\Carbon::parse($_bTrip->start_date)->format('d.m') }} &ndash; {{ $_bTrip->end_date_label }}
                        </span>
                        @if ($isDeletedView)
                            <span class="text-[11px] text-ink-faint">
                                {{ __('business_trips::common.table.deleted_date') }}:
                                <span class="hrm-num">{{ $_bTrip->deleted_at_label ?? '—' }}</span>
                            </span>
                            <span class="text-[11px] text-ink-faint">{{ __('business_trips::common.table.deleted_by') }}: {{ $_bTrip->personDidDelete?->name ?? '—' }}</span>
                        @elseif ($_bTrip->is_active_trip)
                            <x-small-badge mode="blue" dot class="mt-1">{{ __('business_trips::common.filters.in_business_trip') }}</x-small-badge>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td standart-width>
                    <div class="max-w-[220px] leading-tight">
                        <p class="truncate text-[13px] text-ink">{{ $_bTrip->location ?: '—' }}</p>
                        <p class="truncate text-[11px] text-ink-faint">{{ $_bTrip->order?->orderType?->name ?: '—' }}</p>
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="flex flex-col leading-tight">
                        @if (filled($_bTrip->order_no))
                            <a href="{{ route('orders', ['search' => ['order_no' => $_bTrip->order_no]]) }}"
                                class="hrm-num text-[13px] font-medium text-[#0369a1] transition hover:underline">{{ $_bTrip->order_no }}</a>
                        @else
                            <span class="text-ink-faint">&mdash;</span>
                        @endif
                        <span class="truncate text-[11px] text-ink-faint">{{ $_bTrip->order_given_by }}</span>
                        <span class="hrm-num text-[11px] text-ink-faint">{{ $_bTrip->order_date_label }}</span>
                    </div>
                </x-table.td>

                <x-table.td :isButton="true">
                    <div class="flex items-center justify-end gap-1">
                        @can('export-business_trips')
                            @if (filled($_bTrip->order_no))
                                <button type="button"
                                    wire:click="printBusinessTripDocument('{{ $_bTrip->id }}',{{ $_bTrip->is_multi_order_trip ? 'true' : 'false' }})"
                                    title="{{ __('business_trips::common.table.print_document') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-teal-50 hover:text-teal-600">
                                    <x-icons.document-icon color="text-current" hover="text-current" />
                                </button>
                            @endif
                        @endcan
                        @if (filled($_bTrip->order_no))
                            <a href="{{ route('orders', ['search' => ['order_no' => $_bTrip->order_no]]) }}"
                                title="{{ __('business_trips::common.table.open_order') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                            </a>
                        @endif
                    </div>
                </x-table.td>
            </tr>
        @empty
            <x-table.empty :rows="count($this->getTableHeaders())" />
        @endforelse
    </x-table.tbl>

    <x-pagination
        :paginator="$this->businessTrips"
        :summary="$num($this->businessTrips->total()).' '.__('business_trips::common.table.unit')
            .' · '.__('business_trips::common.table.in_trip_summary', ['count' => $num($this->scopedPeopleAway)])"
    />
</div>
