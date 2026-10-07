@php
    $summary = $this->summary;
    $canBindOrder = $this->canBindOrder;
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $statusOptions = [
        ['id' => 'all', 'label' => __('vacation::common.labels.all').' · '.$num($summary['all'])],
        ['id' => 'in_vacation', 'label' => __('vacation::common.labels.in_vacation').' · '.$num($summary['in_vacation'])],
        ['id' => 'at_work', 'label' => __('vacation::common.labels.at_work').' · '.$num($summary['at_work'])],
    ];
    $typeOptions = collect($this->typeFilters)
        ->map(fn (array $type): array => ['id' => $type['key'], 'label' => $type['label'].' · '.$num($type['count'])])
        ->all();
    $yearOptions = collect($years)->map(fn ($year): array => ['id' => $year, 'label' => (string) $year])->values()->all();
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('vacation::common.titles.vacations')"
            :subtitle="$num($summary['all']).' '.__('vacation::common.labels.unit')"
        ></x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        collapsible-filters
        :filters-active="$this->hasActiveFilters"
        :title="__('vacation::common.titles.vacations')"
        :breadcrumb="__('vacation::common.titles.vacations')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($summary['all'])" :label="__('vacation::common.labels.unit')" />
            <x-page-header.stat :value="$num($summary['in_vacation'])" :label="__('vacation::common.labels.in_vacation')" tone="blue" />
            <x-page-header.stat :value="$num($summary['days'])" :label="__('vacation::common.labels.days')" />
        </x-slot:stats>

        <x-slot:actions>
            @can('review-self-service-requests')
                <x-ui.self-service-review-link />
            @endcan
            @can('add-orders')
                @php
                    // The composer opens in a side panel on this page, so the user stays in Vacation.
                    // One vacation template: the button opens it. Several: it lists them. None: the plain composer.
                    $vacationTemplates = $this->vacationOrderTemplates;
                    $pickTemplate = count($vacationTemplates) > 1;
                    $openComposer = "openSideMenu('order-composer', ".\Illuminate\Support\Js::from((string) array_key_first($vacationTemplates)).')';
                @endphp
                <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                    <x-pill-button
                        variant="primary"
                        :wire:click="$pickTemplate ? null : $openComposer"
                        x-on:click="{{ $pickTemplate ? 'open = ! open' : '' }}"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __('vacation::common.actions.vacation_order') }}
                    </x-pill-button>
                    @if ($pickTemplate)
                        <div x-cloak x-show="open" x-transition.opacity.duration.100ms @click.outside="open = false" class="absolute right-0 z-40 mt-1.5 w-64 overflow-hidden rounded-xl border border-hairline bg-white py-1 shadow-overlay">
                            @foreach ($vacationTemplates as $code => $label)
                                <button type="button" @click="open = false" wire:click="openSideMenu('order-composer', @js((string) $code))" class="flex w-full items-center px-3.5 py-2 text-left text-[12.5px] text-ink-soft transition hover:bg-[#fafafa] hover:text-ink">{{ $label }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endcan
            @can('export-vacations')
                <x-pill-button variant="emerald" :icon="true" wire:click.prevent="exportExcel"
                    wire:loading.attr="disabled" wire:target="exportExcel"
                    title="{{ __('vacation::common.actions.export_excel') }}">
                    <x-icons.excel-icon />
                </x-pill-button>
            @endcan
        </x-slot:actions>

        {{-- toolbar --}}
        <div class="flex flex-col gap-2.5">
            <div class="flex flex-wrap items-end gap-3">
                <label class="w-full flex-1 sm:max-w-[360px]">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('vacation::common.labels.fullname') }}</span>
                    <span class="relative block">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input
                            type="search"
                            wire:model.live.debounce.400ms="filter.fullname"
                            placeholder="{{ __('vacation::common.labels.search_placeholder') }}"
                            class="h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] pl-9 pr-3 text-base sm:text-sm text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-0"
                        />
                    </span>
                </label>

                <div class="shrink-0">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('vacation::common.labels.date_range') }}</span>
                    <div class="flex items-center gap-2">
                        <input type="date" wire:model.live="filter.date.min"
                            aria-label="{{ __('vacation::common.labels.date_start') }}"
                            class="hrm-num h-10 w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base sm:text-sm text-ink focus:border-ink focus:bg-white focus:ring-0" />
                        <span class="shrink-0 text-ink-faint">&ndash;</span>
                        <input type="date" wire:model.live="filter.date.max"
                            aria-label="{{ __('vacation::common.labels.date_end') }}"
                            class="hrm-num h-10 w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base sm:text-sm text-ink focus:border-ink focus:bg-white focus:ring-0" />
                    </div>
                </div>

                <div class="min-w-[200px] flex-1">
                    <span class="block pb-1 text-[12px] font-medium text-ink-muted">{{ __('vacation::common.labels.structure') }}</span>
                    <x-ui.select-dropdown
                        :aria-label="__('vacation::common.labels.structure')"
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        wire:model.live="filter.structure_id"
                        :model="$this->structureOptions"
                        search-model="searchStructure"
                    />
                </div>

                <x-ui.select-dropdown
                    :aria-label="__('vacation::common.labels.status')"
                    wire:key="vacations-status-filter"
                    :placeholder="__('vacation::common.labels.all')"
                    :clearable="false"
                    mode="gray"
                    class="w-full sm:w-52 [&>div]:mt-0"
                    wire:model.live="filter.vacation_status"
                    :model="$statusOptions"
                />

                @if ($typeOptions !== [])
                    {{-- a vacation inherits its type from the order it was issued under --}}
                    <x-ui.select-dropdown
                        :aria-label="__('vacation::common.labels.vacation_type')"
                        wire:key="vacations-type-filter"
                        :placeholder="__('vacation::common.labels.vacation_type')"
                        mode="gray"
                        class="w-full sm:w-64 [&>div]:mt-0"
                        :searchable="count($typeOptions) > 8"
                        wire:model.live="selectedType"
                        :model="$typeOptions"
                    />
                @endif

                <x-ui.select-dropdown
                    :aria-label="__('vacation::common.labels.year')"
                    wire:key="vacations-year-filter"
                    :placeholder="__('vacation::common.labels.year')"
                    :clearable="false"
                    :disabled="! empty($filter['date']['min'] ?? null) || ! empty($filter['date']['max'] ?? null)"
                    mode="gray"
                    class="w-full sm:w-32 [&>div]:mt-0"
                    wire:model.live="selectedYear"
                    :model="$yearOptions"
                />

                <x-filter.reset :active="$this->hasActiveFilters" />
            </div>

            <p class="text-[11.5px] text-ink-faint">{{ __('vacation::common.hints.approval_note') }}</p>
        </div>
    </x-page-header>

    {{-- ===================== table ===================== --}}
    <x-table.tbl sticky :headers="$this->getTableHeaders()">
        @forelse ($this->vacations as $_vacation)
            @php
                $startDate = \Carbon\Carbon::parse($_vacation->start_date);
                $endDate = \Carbon\Carbon::parse($_vacation->end_date);
                $returnWorkDate = \Carbon\Carbon::parse($_vacation->return_work_date);
                $isOnVacation = $_vacation->is_active_vacation;
                $fullname = (string) $_vacation->personnel?->fullname;
                $vacationType = $_vacation->order?->orderType?->name
                    ?? data_get($_vacation->order?->template_snapshot, 'label');
            @endphp
            <tr wire:key="vacation-row-{{ $_vacation->id }}" @class(['bg-[#f0f9ff]/50' => $isOnVacation])>
                <x-table.td standart-width>
                    <div class="flex items-center gap-2.5">
                        <x-avatar :name="$fullname" :tone="$isOnVacation ? 'blue' : 'neutral'" />
                        <div class="min-w-0 max-w-[220px] leading-tight">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $fullname }}</p>
                            <p class="truncate text-[11px] text-ink-faint">
                                {{ $_vacation->personnel?->latestRank?->rank?->name ?? $_vacation->personnel?->position_label }}
                            </p>
                        </div>
                    </div>
                </x-table.td>

                <x-table.td standart-width>
                    <div class="max-w-[200px] leading-tight">
                        <p class="truncate text-[12.5px] text-ink-soft">{{ $_vacation->personnel?->structure?->name }}</p>
                        <p class="truncate text-[11px] text-ink-faint">{{ $_vacation->personnel?->position_label }}</p>
                    </div>
                </x-table.td>

                <x-table.td>
                    @if (filled($vacationType))
                        <x-small-badge mode="secondary">{{ $vacationType }}</x-small-badge>
                    @else
                        <span class="text-ink-faint">&mdash;</span>
                    @endif
                </x-table.td>

                <x-table.td>
                    <div class="flex flex-col leading-tight">
                        <span class="hrm-num text-[13px] font-medium text-ink">
                            {{ $startDate->format('d.m') }} &ndash; {{ $endDate->format('d.m.Y') }}
                        </span>
                        <span class="text-[11px] text-ink-faint">
                            {{ __('vacation::common.labels.return_work_date') }}:
                            <span class="hrm-num">{{ $returnWorkDate->format('d.m.Y') }}</span>
                        </span>
                        @if (filled($_vacation->vacation_places))
                            <span class="truncate text-[11px] text-ink-faint">{{ $_vacation->vacation_places }}</span>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="flex w-[132px] flex-col gap-1.5">
                        <div class="flex items-center gap-2">
                            <span class="hrm-num text-[13px] font-semibold text-ink">
                                {{ $_vacation->duration }} <span class="text-[11px] font-normal text-ink-faint">{{ __('vacation::common.labels.day') }}</span>
                            </span>
                            <x-small-badge :mode="$isOnVacation ? 'blue' : 'secondary'" dot>
                                {{ $isOnVacation ? __('vacation::common.labels.in_vacation') : __('vacation::common.labels.at_work') }}
                            </x-small-badge>
                        </div>
                        @if ((int) $_vacation->vacation_days_total > 0)
                            <div class="h-1 overflow-hidden rounded-full bg-[#f4f4f5]">
                                <div @class([
                                    'h-full rounded-full',
                                    'bg-[#e11d48]' => $_vacation->remaining_color === 'rose',
                                    'bg-[#0ea5e9]' => $_vacation->remaining_color === 'blue',
                                    'bg-[#10b981]' => $_vacation->remaining_color === 'teal',
                                ]) style="width: {{ $_vacation->remaining_percentage }}%"></div>
                            </div>
                            <span class="hrm-num text-[10.5px] text-ink-faint">
                                {{ __('vacation::common.labels.remaining') }}: {{ $_vacation->remaining_days }}/{{ $_vacation->vacation_days_total }}
                            </span>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td>
                    <div class="flex flex-col leading-tight">
                        @if (filled($_vacation->order_no))
                            <a href="{{ route('orders', ['search' => ['order_no' => $_vacation->order_no]]) }}"
                                class="hrm-num text-[13px] font-medium text-[#0369a1] transition hover:underline">{{ $_vacation->order_no }}</a>
                        @elseif ($canBindOrder && $_vacation->submission_source === 'employee_self_service' && $_vacation->approval_status === 'approved')
                            <button type="button" wire:click="bindOperationalOrder('{{ $_vacation->id }}')"
                                class="inline-flex h-[26px] w-max items-center rounded-lg border border-hairline bg-[#fafafa] px-2 text-[11.5px] font-semibold text-ink-soft transition hover:border-zinc-300 hover:bg-white">
                                {{ __('vacation::common.actions.bind_order') }}
                            </button>
                        @else
                            <span class="text-ink-faint">&mdash;</span>
                        @endif
                        <span class="truncate text-[11px] text-ink-faint">{{ $_vacation->order_given_by }}</span>
                        @if ($_vacation->order_date)
                            <span class="hrm-num text-[11px] text-ink-faint">{{ \Carbon\Carbon::parse($_vacation->order_date)->format('d.m.Y') }}</span>
                        @endif
                    </div>
                </x-table.td>

                <x-table.td :isButton="true">
                    <div class="flex items-center justify-end gap-1">
                        @if (filled($_vacation->order_no))
                            <a href="{{ route('orders', ['search' => ['order_no' => $_vacation->order_no]]) }}"
                                title="{{ __('vacation::common.actions.open_order') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                                <x-icons.edit-icon color="text-current" hover="text-current" />
                            </a>
                            @can('export-vacations')
                                <button type="button" wire:click="printVacationDocument('{{ $_vacation->id }}')"
                                    title="{{ __('vacation::common.actions.print_document') }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink">
                                    <x-icons.document-icon color="text-current" hover="text-current" />
                                </button>
                            @endcan
                        @elseif ($canBindOrder && $_vacation->submission_source === 'employee_self_service' && $_vacation->approval_status === 'approved')
                            <button type="button" wire:click="bindOperationalOrder('{{ $_vacation->id }}')"
                                title="{{ __('vacation::common.actions.bind_order') }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-amber-50 hover:text-amber-600">
                                <x-icons.document-icon color="text-current" hover="text-current" />
                            </button>
                        @endif
                    </div>
                </x-table.td>
            </tr>
        @empty
            <x-table.empty :rows="count($this->getTableHeaders())" :filtered="$this->hasActiveFilters" />
        @endforelse
    </x-table.tbl>

    <x-pagination :paginator="$this->vacations" :unit="__('vacation::common.labels.unit')" />

    @can('add-orders')
        <x-side-modal size="xx-large">
            @if ($showSideMenu === 'order-composer')
                <livewire:orders.order-composer :presetCode="(string) $modelName" :key="'vacation-order-'.($modelName ?: 'any')" />
            @endif
        </x-side-modal>
    @endcan
</div>
