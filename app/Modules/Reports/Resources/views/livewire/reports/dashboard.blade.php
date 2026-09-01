@php
    $tabRoute = fn (string $tab) => $this->tabRoute($tab);
    $tabDescriptions = [
        'overview' => __('reports::dashboard.tab_descriptions.overview'),
        'standard' => __('reports::dashboard.tab_descriptions.standard'),
        'dynamic' => __('reports::dashboard.tab_descriptions.dynamic'),
        'comparisons' => __('reports::dashboard.tab_descriptions.comparisons'),
    ];
    $tabOrder = array_keys($tabDescriptions);
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel :title="__('reports::dashboard.title')" :subtitle="__('reports::dashboard.eyebrow')">
            <x-context-panel.section>
                @foreach ($tabOrder as $tab)
                    <x-context-panel.item
                        wire:key="reports-panel-tab-{{ $tab }}"
                        :href="$tabRoute($tab)"
                        wire:navigate
                        :active="$activeTab === $tab"
                        :note="$tabDescriptions[$tab]"
                    >{{ __('reports::dashboard.tabs.'.$tab) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            {{-- The period drives every tab, so it belongs to the panel rather than to one
                 workspace's toolbar. --}}
            <x-context-panel.section :title="__('reports::dashboard.fields.period')" :padded="false">
                <div class="space-y-2.5 px-3.5 pb-3.5 pt-1">
                    <label class="block">
                        <span class="hrm-eyebrow block pb-1">{{ __('reports::dashboard.fields.year') }}</span>
                        <x-ui.select wire:model.live="year">
                            @foreach (range(now()->year - 4, now()->year + 1) as $yearOption)
                                <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                            @endforeach
                        </x-ui.select>
                    </label>

                    <label class="block">
                        <span class="hrm-eyebrow block pb-1">{{ __('reports::dashboard.fields.month') }}</span>
                        <x-ui.select wire:model.live="month">
                            @foreach (range(1, 12) as $monthOption)
                                <option value="{{ $monthOption }}">{{ \Carbon\Carbon::create()->month($monthOption)->translatedFormat('F') }}</option>
                            @endforeach
                        </x-ui.select>
                    </label>

                    <label class="block">
                        <span class="hrm-eyebrow block pb-1">{{ __('reports::dashboard.fields.structure') }}</span>
                        <x-ui.select wire:model.live="structureId">
                            <option value="">{{ __('reports::dashboard.labels.all_structures') }}</option>
                            @foreach ($structureOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-ui.select>
                    </label>
                </div>
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('reports::dashboard.hero_title')"
        :breadcrumb="__('reports::dashboard.title')"
    >
        <x-slot:icon>
            <x-icons.report-chart-icon size="w-[18px] h-[18px]" color="text-current" hover="text-current" class="hrm-icon" />
        </x-slot:icon>

        <x-slot:actions>
            <x-pill-button :href="$tabRoute('dynamic')" wire:navigate>
                {{ __('reports::dashboard.actions.build_report') }}
            </x-pill-button>

            @if ($canExport)
                <x-pill-button variant="emerald" :icon="true" wire:click="exportExcel"
                    wire:loading.attr="disabled" wire:target="exportExcel"
                    title="{{ __('reports::dashboard.actions.export_excel') }}">
                    <x-icons.excel-icon />
                </x-pill-button>
            @endif

            <x-pill-button variant="primary" :href="$this->printUrl()" target="_blank">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                {{ __('reports::dashboard.actions.print') }}
            </x-pill-button>
        </x-slot:actions>

        <p class="max-w-3xl text-[13px] leading-6 text-ink-muted">{{ __('reports::dashboard.subtitle') }}</p>

        {{-- small-screen fallback for the panel's section list --}}
        <x-filter.nav wrap class="min-w-0 lg:hidden">
            @foreach ($tabOrder as $tab)
                <x-filter.item
                    wire:key="reports-chip-{{ $tab }}"
                    :href="$tabRoute($tab)"
                    wire:navigate
                    :active="$activeTab === $tab"
                >{{ __('reports::dashboard.tabs.'.$tab) }}</x-filter.item>
            @endforeach
        </x-filter.nav>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="px-4 py-4 sm:px-5">
        @if ($activeTab === 'overview')
            <livewire:reports.overview
                lazy
                :key="'reports-overview-'.$year.'-'.$month.'-'.($structureId ?? 'all')"
                :year="$year"
                :month="$month"
                :structure-id="$structureId"
            />
        @elseif ($activeTab === 'standard')
            <livewire:reports.standard-reports
                lazy
                :key="'reports-standard-'.$report.'-'.$year.'-'.$month.'-'.($structureId ?? 'all')"
                :report="$report"
                :year="$year"
                :month="$month"
                :structure-id="$structureId"
            />
        @elseif ($activeTab === 'dynamic')
            <livewire:reports.dynamic-builder
                lazy
                :key="'reports-dynamic-'.$source.'-'.$groupBy.'-'.$metric.'-'.$year.'-'.$month.'-'.($structureId ?? 'all')"
                :source="$source"
                :group-by="$groupBy"
                :metric="$metric"
                :year="$year"
                :month="$month"
                :structure-id="$structureId"
            />
        @elseif ($activeTab === 'comparisons')
            <livewire:reports.comparisons lazy />
        @endif
    </div>
</div>
