@php
    $summary = $this->summary;
    $activeStatus = data_get($filter, 'vacation_status', 'all');
    $statusFilters = [
        'all' => ['label' => __('vacation::common.labels.all'), 'dot' => 'bg-[#a1a1aa]', 'count' => $summary['all']],
        'in_vacation' => ['label' => __('vacation::common.labels.in_vacation'), 'dot' => 'bg-[#0ea5e9]', 'count' => $summary['in_vacation']],
        'at_work' => ['label' => __('vacation::common.labels.at_work'), 'dot' => 'bg-[#10b981]', 'count' => $summary['at_work']],
    ];
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('vacation::common.titles.vacations')"
            :subtitle="$num($summary['all']).' '.__('vacation::common.labels.unit')"
        >
            <x-context-panel.section>
                @foreach ($statusFilters as $value => $option)
                    <x-context-panel.item
                        wire:key="vacation-panel-status-{{ $value }}"
                        wire:click.prevent="setStatus('{{ $value }}')"
                        wire:loading.attr="disabled"
                        wire:target="setStatus"
                        :active="$activeStatus === $value"
                        :dot="$option['dot']"
                        :count="$num($option['count'])"
                    >{{ $option['label'] }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            {{-- a vacation inherits its type from the order it was issued under --}}
            @if ($this->typeFilters !== [])
                <x-context-panel.section :title="__('vacation::common.labels.vacation_type')">
                    @if ($selectedType)
                        <x-context-panel.item wire:click.prevent="selectType('')">
                            &larr; {{ __('vacation::common.labels.show_all') }}
                        </x-context-panel.item>
                    @endif

                    @foreach ($this->typeFilters as $_type)
                        <x-context-panel.item
                            wire:key="vacation-panel-type-{{ $_type['key'] }}"
                            wire:click.prevent="selectType('{{ $_type['key'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="selectType"
                            :active="(string) $selectedType === $_type['key']"
                            :count="$num($_type['count'])"
                        >{{ $_type['label'] }}</x-context-panel.item>
                    @endforeach
                </x-context-panel.section>
            @endif

            <x-context-panel.section :title="__('vacation::common.labels.year')">
                <div class="px-1 pb-1">
                    <select
                        wire:model.live="selectedYear"
                        @disabled(! empty($filter['date']['min'] ?? null) || ! empty($filter['date']['max'] ?? null))
                        class="hrm-num h-[31px] w-full rounded-lg border border-hairline bg-white px-2 text-[12.5px] text-ink focus:border-ink focus:ring-0 disabled:opacity-50"
                    >
                        @foreach ($years as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </x-context-panel.section>

            <x-slot name="footer">
                <button type="button" wire:click="resetFilter" class="text-[12px] font-medium text-ink-muted transition hover:text-ink">
                    {{ __('vacation::common.labels.reset') }}
                </button>
            </x-slot>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('vacation::common.titles.requests')"
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
                    <span class="hrm-eyebrow block pb-1">{{ __('vacation::common.labels.fullname') }}</span>
                    <span class="relative block">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input
                            type="search"
                            wire:model.live.debounce.400ms="filter.fullname"
                            wire:keydown.enter="searchFilter"
                            placeholder="{{ __('vacation::common.labels.search_placeholder') }}"
                            class="h-[34px] w-full rounded-[10px] border border-hairline bg-[#f4f4f5] pl-9 pr-3 text-[12.5px] text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-0"
                        />
                    </span>
                </label>

                <div class="shrink-0">
                    <span class="hrm-eyebrow block pb-1">{{ __('vacation::common.labels.date_range') }}</span>
                    <div class="flex items-center gap-2">
                        <input type="date" wire:model="filter.date.min"
                            aria-label="{{ __('vacation::common.labels.date_start') }}"
                            class="hrm-num h-[34px] w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12.5px] text-ink focus:border-ink focus:bg-white focus:ring-0" />
                        <span class="shrink-0 text-ink-faint">&ndash;</span>
                        <input type="date" wire:model="filter.date.max"
                            aria-label="{{ __('vacation::common.labels.date_end') }}"
                            class="hrm-num h-[34px] w-[150px] rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12.5px] text-ink focus:border-ink focus:bg-white focus:ring-0" />
                    </div>
                </div>

                <div class="min-w-[200px] flex-1">
                    <span class="hrm-eyebrow block pb-1">{{ __('vacation::common.labels.structure') }}</span>
                    <x-ui.select-dropdown
                        placeholder="---"
                        mode="gray"
                        class="w-full"
                        wire:model.live="filter.structure_id"
                        :model="$this->structureOptions"
                        search-model="searchStructure"
                    />
                </div>

                <x-pill-button variant="primary" wire:click="searchFilter" class="!h-[34px]">{{ __('vacation::common.labels.search') }}</x-pill-button>
                <x-pill-button wire:click="resetFilter" class="!h-[34px]">{{ __('vacation::common.labels.reset') }}</x-pill-button>
            </div>

            <p class="text-[11.5px] text-ink-faint">{{ __('vacation::common.hints.approval_note') }}</p>
        </div>
    </x-page-header>

    {{-- ===================== table ===================== --}}
    <x-table.tbl :headers="$this->getTableHeaders()">
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
                        @elseif ($_vacation->submission_source === 'employee_self_service' && $_vacation->approval_status === 'approved')
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
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition hover:bg-teal-50 hover:text-teal-600">
                                    <x-icons.document-icon color="text-current" hover="text-current" />
                                </button>
                            @endcan
                        @elseif ($_vacation->submission_source === 'employee_self_service' && $_vacation->approval_status === 'approved')
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
            <x-table.empty :rows="count($this->getTableHeaders())" />
        @endforelse
    </x-table.tbl>

    <x-pagination :paginator="$this->vacations" :unit="__('vacation::common.labels.unit')" />
</div>
