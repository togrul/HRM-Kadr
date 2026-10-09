@php
    $summary = $this->summary;
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $statusOptions = [
        ['id' => 'all', 'label' => __('business_trips::common.filters.all').' · '.$num($summary['all'])],
        ['id' => 'in_business_trip', 'label' => __('business_trips::common.filters.in_business_trip').' · '.$num($summary['in_business_trip'])],
        ['id' => 'at_work', 'label' => __('business_trips::common.filters.at_work').' · '.$num($summary['at_work'])],
        ['id' => 'deleted', 'label' => __('business_trips::common.filters.deleted').' · '.$num($summary['deleted'])],
    ];
    $locationOptions = collect($this->locationFilters)
        ->map(fn (array $location): array => ['id' => $location['key'], 'label' => $location['key'].' · '.$num($location['count'])])
        ->all();
    $isDeletedView = \Illuminate\Support\Arr::get($search, 'business_trip_status', '') === 'deleted';
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('business_trips::common.table.title')"
            :subtitle="$num($summary['all']).' '.__('business_trips::common.table.unit')"
        ></x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        collapsible-filters
        :filters-active="$this->hasActiveFilters"
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
            @if ($this->businessTripOrderPreset !== null)
                @can('add-orders')
                    <x-pill-button variant="primary" :href="route('orders', ['create' => 1, 'preset' => $this->businessTripOrderPreset])" wire:navigate>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __('business_trips::common.actions.business_trip_order') }}
                    </x-pill-button>
                @endcan
            @endif
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
                <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('business_trips::common.filters.fullname') }}</span>
                <x-livewire-input mode="gray" name="filter.fullname" wire:model.live.debounce.400ms="filter.fullname" />
            </label>

            <div class="shrink-0">
                <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('business_trips::common.filters.date_range') }}</span>
                <div class="flex items-center gap-2">
                    <div class="w-[150px] shrink-0">
                        <x-ui.date-input
                            wire:model.live="filter.date.min"
                            aria-label="{{ __('business_trips::common.filters.date_start') }}"
                        />
                    </div>
                    <span class="shrink-0 text-ink-faint">&ndash;</span>
                    <div class="w-[150px] shrink-0">
                        <x-ui.date-input
                            wire:model.live="filter.date.max"
                            aria-label="{{ __('business_trips::common.filters.date_end') }}"
                        />
                    </div>
                </div>
            </div>

            <div class="min-w-[190px] flex-1">
                <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('business_trips::common.filters.structure') }}</span>
                <x-ui.select-dropdown
                    :aria-label="__('business_trips::common.filters.structure')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="filter.structure_id"
                    :model="$this->structureOptions"
                    search-model="searchStructure"
                />
            </div>

            <div class="min-w-[170px] flex-1">
                <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('business_trips::common.filters.order_types') }}</span>
                <x-ui.select-dropdown
                    :aria-label="__('business_trips::common.filters.order_types')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="filter.order_type_id"
                    :model="$this->orderTypeOptions"
                />
            </div>

            <x-ui.select-dropdown
                :aria-label="__('business_trips::common.filters.status')"
                wire:key="business-trips-status-filter"
                :placeholder="__('business_trips::common.filters.all')"
                :clearable="false"
                mode="gray"
                class="w-full sm:w-52 [&>div]:mt-0"
                wire:model.live="filter.business_trip_status"
                :model="$statusOptions"
            />

            @if ($locationOptions !== [])
                {{-- destinations, straight from the trips' own location column --}}
                <x-ui.select-dropdown
                    :aria-label="__('business_trips::common.filters.locations')"
                    wire:key="business-trips-location-filter"
                    :placeholder="__('business_trips::common.filters.locations')"
                    mode="gray"
                    class="w-full sm:w-56 [&>div]:mt-0"
                    :searchable="count($locationOptions) > 8"
                    wire:model.live="selectedLocation"
                    :model="$locationOptions"
                />
            @endif

            <x-filter.reset :active="$this->hasActiveFilters" />
        </div>
    </x-page-header>

    {{-- ===================== table ===================== --}}
    <x-table.tbl sticky :headers="$this->getTableHeaders()">
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
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
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
            <x-table.empty :rows="count($this->getTableHeaders())" :filtered="$this->hasActiveFilters" :hint="__('business_trips::common.hints.from_orders')">
                <x-slot:action>
                    @can('viewAny', App\Models\Order::class)
                        <x-pill-button variant="secondary" :href="route('orders')" wire:navigate>{{ __('business_trips::common.actions.go_to_orders') }}</x-pill-button>
                    @endcan
                </x-slot:action>
            </x-table.empty>
        @endforelse
    </x-table.tbl>

    <x-pagination
        :paginator="$this->businessTrips"
        :summary="$num($this->businessTrips->total()).' '.__('business_trips::common.table.unit')
            .' · '.__('business_trips::common.table.in_trip_summary', ['count' => $num($this->scopedPeopleAway)])"
    />
</div>
