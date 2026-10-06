<div class="flex flex-col">
    @php
        $attendanceTabRoute = function (string $tab) use ($year, $month, $selectedStructureId) {
            return route('attendance', array_filter([
                'tab' => $tab,
                'year' => $year,
                'month' => $month,
                'structure_id' => $selectedStructureId,
            ], fn ($value) => $value !== null && $value !== ''));
        };

        $attendanceTabs = [
            'overview' => 'overview',
            'manager-summary' => 'manager_summary',
            'daily-monitor' => 'daily_monitor',
            'puantaj' => 'puantaj',
            'exceptions' => 'exceptions',
            'overtime' => 'overtime',
            'month-close' => 'month_close',
            'manual' => 'manual',
            'history' => 'history',
            'settings' => 'settings',
            'shifts' => 'shifts',
            'calendar-regimes' => 'calendar_regimes',
        ];

        // Three groups, by what the user comes to do: daily work, review/approval, configuration.
        $tabGroups = [
            'work_group' => ['overview', 'daily-monitor', 'manager-summary', 'puantaj', 'manual'],
            'review_group' => ['exceptions', 'overtime', 'month-close', 'history'],
            'settings_group' => ['settings', 'shifts', 'calendar-regimes'],
        ];
        $tabGroups = array_filter(array_map(
            fn (array $tabs) => array_values(array_intersect($tabs, $availableTabs)),
            $tabGroups
        ));

        // Work waiting in a section, shown as the tab's count (real overview figures only).
        $tabCounts = array_filter([
            'manual' => (int) ($overview['manual_pending_count'] ?? 0),
            'daily-monitor' => (int) ($overview['raw_pending_count'] ?? 0),
            'exceptions' => (int) ($overview['open_exception_count'] ?? 0),
            'overtime' => (int) ($overview['pending_overtime_count'] ?? 0),
        ]);

        // Durations read as hours ("198", "7:30"); the unit sits beside the number as a suffix.
        $asHours = function (int|float|null $minutes): string {
            $minutes = (int) round((float) $minutes);
            $rest = $minutes % 60;

            return number_format(intdiv($minutes, 60), 0, ',', ' ').($rest > 0 ? ':'.str_pad((string) $rest, 2, '0', STR_PAD_LEFT) : '');
        };
    @endphp

    {{-- The panel carries ONE thing, the structure tree; the section nav lives in the page
         header, so the two never compete in the same column. --}}
    <x-slot name="sidebar">
        <x-context-panel>
            <livewire:structure.sidebar :selected="$selectedStructureId" wire:key="attendance-structure-sidebar" />
        </x-context-panel>
    </x-slot>

    @php
        $activeLabelKey = $attendanceTabs[$activeTab] ?? 'overview';
    @endphp

    <x-page-header
        :title="__('attendance::dashboard.tabs.'.$activeLabelKey)"
        :breadcrumb="__('attendance::dashboard.tabs.'.$activeLabelKey)"
        :breadcrumb-root="__('attendance::dashboard.title')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            {{-- period control: step month by month; the label reads as a date, not two fields --}}
            <div class="inline-flex h-10 items-center rounded-[10px] border border-hairline bg-[#f4f4f5]" role="group" aria-label="{{ __('attendance::dashboard.filters.month') }}">
                <button type="button" wire:click="shiftMonth(-1)" wire:loading.attr="disabled" wire:target="shiftMonth" class="flex h-10 w-9 items-center justify-center rounded-l-[10px] text-ink-muted transition hover:bg-[#e4e4e7] hover:text-ink" aria-label="{{ __('attendance::dashboard.filters.previous_month') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <span class="min-w-[124px] px-1 text-center text-[13.5px] font-semibold capitalize text-ink" aria-live="polite">
                    {{ \Carbon\Carbon::create((int) $year, (int) $month, 1)->translatedFormat('F Y') }}
                </span>
                <button type="button" wire:click="shiftMonth(1)" wire:loading.attr="disabled" wire:target="shiftMonth" class="flex h-10 w-9 items-center justify-center rounded-r-[10px] text-ink-muted transition hover:bg-[#e4e4e7] hover:text-ink" aria-label="{{ __('attendance::dashboard.filters.next_month') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>

        </x-slot:actions>

        {{-- Two-level section nav: a segmented control picks the group (İş / Yoxlama / Ayarlar),
             the underline tabs beside it are that group's sections. Switching a group only
             reveals its sections (no request); every permitted section stays in the markup.
             Counts are real pending figures from the overview. --}}
        @php
            $activeGroup = collect($tabGroups)->search(fn (array $tabs) => in_array($activeTab, $tabs, true)) ?: array_key_first($tabGroups);
        @endphp
        <nav x-data="{ group: @js($activeGroup) }" class="flex flex-col gap-3 md:flex-row md:items-center md:gap-4" aria-label="{{ __('attendance::dashboard.title') }}">
            <div class="inline-flex shrink-0 self-start rounded-full bg-[#f4f4f5] p-1" role="tablist">
                @foreach ($tabGroups as $groupKey => $groupTabs)
                    @php $groupHasWork = collect($groupTabs)->contains(fn (string $tab) => isset($tabCounts[$tab])); @endphp
                    <button
                        type="button"
                        role="tab"
                        id="attendance-group-{{ $groupKey }}"
                        aria-controls="attendance-group-panel-{{ $groupKey }}"
                        x-on:click="group = '{{ $groupKey }}'"
                        x-bind:aria-selected="(group === '{{ $groupKey }}').toString()"
                        aria-selected="{{ $groupKey === $activeGroup ? 'true' : 'false' }}"
                        class="relative inline-flex h-8 items-center gap-1.5 rounded-full px-3.5 text-[13px] font-medium text-ink-muted transition hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 aria-selected:bg-white aria-selected:font-semibold aria-selected:text-ink aria-selected:shadow-[0_1px_2px_rgba(0,0,0,0.08)]"
                    >
                        {{ __('attendance::dashboard.tabs.'.$groupKey) }}
                        @if ($groupHasWork)
                            <span class="h-1.5 w-1.5 rounded-full bg-[#f97316]" aria-hidden="true"></span>
                        @endif
                    </button>
                @endforeach
            </div>

            <span class="hidden h-5 w-px shrink-0 bg-hairline md:block" aria-hidden="true"></span>

            @foreach ($tabGroups as $groupKey => $groupTabs)
                <ul
                    id="attendance-group-panel-{{ $groupKey }}"
                    role="tabpanel"
                    aria-labelledby="attendance-group-{{ $groupKey }}"
                    data-group="{{ $groupKey }}"
                    x-show="group === '{{ $groupKey }}'"
                    @if ($groupKey !== $activeGroup) style="display: none" @endif
                    class="hrm-scroll-hidden -mb-3.5 flex min-w-0 items-stretch gap-5 overflow-x-auto"
                >
                    @foreach ($groupTabs as $tab)
                        @php $isActive = $activeTab === $tab; @endphp
                        <li class="shrink-0">
                            <a
                                href="{{ $attendanceTabRoute($tab) }}"
                                wire:navigate
                                @if ($isActive) aria-current="page" @endif
                                @class([
                                    'inline-flex h-10 items-center gap-1.5 border-b-2 text-[13px] transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400',
                                    'border-ink font-semibold text-ink' => $isActive,
                                    'border-transparent font-medium text-ink-muted hover:border-hairline hover:text-ink' => ! $isActive,
                                ])
                            >
                                {{ __('attendance::dashboard.tabs.'.$attendanceTabs[$tab]) }}
                                @if (isset($tabCounts[$tab]))
                                    <span class="hrm-num rounded-full bg-[#fff7ed] px-1.5 text-[11px] font-semibold leading-[18px] text-[#c2410c]">{{ $tabCounts[$tab] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>
    </x-page-header>

    <div class="space-y-4 px-4 py-4 sm:px-5">
    @if($activeTab === 'overview')
        @php
            $kpi = $overview['kpi'] ?? [];
            $trendDirection = $kpi['overtime_trend_direction'] ?? 'flat';
            // rising overtime is the bad direction here, so up reads rose and down green
            $trendTone = match($trendDirection) {
                'up' => 'rose',
                'down' => 'green',
                default => 'ink',
            };
        @endphp

        {{-- work waiting on someone comes first, and each count opens the list behind it --}}
        @php
            $queueTiles = [
                ['metric' => 'manual_pending', 'value' => (int) ($overview['manual_pending_count'] ?? 0), 'tone' => 'amber', 'tab' => 'manual'],
                ['metric' => 'unprocessed_punches', 'value' => (int) ($overview['raw_pending_count'] ?? 0), 'tone' => 'amber', 'tab' => 'daily-monitor'],
                ['metric' => 'open_exceptions', 'value' => (int) ($overview['open_exception_count'] ?? 0), 'tone' => 'rose', 'tab' => 'exceptions'],
                ['metric' => 'pending_overtime', 'value' => (int) ($overview['pending_overtime_count'] ?? 0), 'tone' => 'amber', 'tab' => 'overtime'],
            ];
        @endphp

        <section class="space-y-3">
            <p class="hrm-eyebrow">{{ __('attendance::dashboard.cards.needs_attention') }}</p>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($queueTiles as $tile)
                    @php
                        $tileHref = in_array($tile['tab'], $availableTabs, true) ? $attendanceTabRoute($tile['tab']) : null;
                        $tileEmpty = $tile['value'] === 0;
                        $tileDot = $tileEmpty ? 'bg-zinc-300' : ($tile['tone'] === 'rose' ? 'bg-[#e11d48]' : 'bg-[#d97706]');
                        $tileNumber = $tileEmpty ? 'text-ink-faint' : ($tile['tone'] === 'rose' ? 'text-[#be123c]' : 'text-[#b45309]');
                    @endphp
                    {{-- the whole card is the link to the queue behind the number; an empty queue
                         reads quieter but still opens its section --}}
                    <{{ $tileHref ? 'a' : 'div' }}
                        @if ($tileHref) href="{{ $tileHref }}" wire:navigate @endif
                        @class([
                            'group flex flex-col rounded-2xl border px-4 py-3.5 transition',
                            'cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2' => $tileHref,
                            'border-hairline bg-white shadow-card hover:border-zinc-300 hover:shadow-md' => ! $tileEmpty,
                            'border-hairline bg-[#fafafa] hover:border-zinc-300 hover:bg-white' => $tileEmpty,
                        ])
                    >
                        <div class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $tileDot }}" aria-hidden="true"></span>
                            <x-ui.field-label as="div" class="tracking-tight">{{ __('attendance::dashboard.metrics.'.$tile['metric']) }}</x-ui.field-label>
                        </div>
                        <p class="hrm-num mt-auto pt-2 text-[21px] font-semibold tracking-[-0.03em] {{ $tileNumber }}">{{ $tile['value'] }}</p>
                        @if ($tileHref)
                            <div class="mt-2 flex items-center justify-between gap-2 border-t border-hairline-subtle pt-2 text-[12px]">
                                <span class="text-ink-faint">{{ $tileEmpty ? __('attendance::dashboard.cards.queue_empty') : '' }}</span>
                                <span class="inline-flex items-center gap-0.5 font-medium text-ink-muted transition group-hover:text-ink">
                                    {{ __('attendance::dashboard.cards.open_queue') }}
                                    <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                </span>
                            </div>
                        @endif
                    </{{ $tileHref ? 'a' : 'div' }}>
                @endforeach
            </div>
        </section>

        <section class="space-y-3">
            <p class="hrm-eyebrow">{{ __('attendance::dashboard.cards.attendance_statistics') }}</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.workdays')" :value="$overview['workdays'] ?? 0" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.holiday_weekend')" :value="$overview['holidays'] ?? 0" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.scheduled_minutes')" :value="$asHours($overview['scheduled_minutes'] ?? 0)" :suffix="__('attendance::dashboard.units.hours')" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.worked_minutes')" :value="$asHours($overview['worked_minutes'] ?? 0)" :suffix="__('attendance::dashboard.units.hours')" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.overtime_minutes')" :value="$asHours($overview['overtime_minutes'] ?? 0)" :suffix="__('attendance::dashboard.units.hours')" />
            </div>
        </section>

        <section class="space-y-3">
            <p class="hrm-eyebrow">{{ __('attendance::dashboard.cards.process_statistics') }}</p>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.metric-tile
                    :label="__('attendance::dashboard.metrics.coverage')"
                    :value="($kpi['coverage_pct'] ?? 0).'%'"
                    :hint="__('attendance::dashboard.metrics.coverage_hint')"
                />
                <x-ui.metric-tile
                    :label="__('attendance::dashboard.metrics.absence_rate')"
                    :value="($kpi['absence_rate_pct'] ?? 0).'%'"
                    :hint="__('attendance::dashboard.metrics.absence_rate_hint', ['absence' => $kpi['absence_days'] ?? 0, 'scheduled' => $kpi['scheduled_days'] ?? 0])"
                />
                <x-ui.metric-tile
                    :label="__('attendance::dashboard.metrics.compliance')"
                    :value="($kpi['compliance_pct'] ?? 0).'%'"
                    :hint="__('attendance::dashboard.metrics.compliance_hint')"
                />
                <x-ui.metric-tile
                    :label="__('attendance::dashboard.metrics.overtime_trend')"
                    :value="($kpi['overtime_trend_pct'] ?? 0).'%'"
                    :tone="$trendTone"
                    :hint="__('attendance::dashboard.metrics.overtime_trend_hint', ['hours' => $asHours($kpi['overtime_previous_minutes'] ?? 0)])"
                />
            </div>
        </section>
    @endif

    @if($activeTab === 'manual' && in_array('manual', $availableTabs, true))
        <livewire:attendance.manual-entries :embedded="true" :selectedStructureId="$selectedStructureId" :key="'attendance-manual-'.$year.'-'.$month.'-'.($selectedStructureId ?? 'all')" />
    @endif

    @if($activeTab === 'daily-monitor' && in_array('daily-monitor', $availableTabs, true))
        <livewire:attendance.daily-monitor :selectedStructureId="$selectedStructureId" :key="'attendance-monitor-'.$year.'-'.$month.'-'.($selectedStructureId ?? 'all')" />
    @endif

    @if($activeTab === 'manager-summary' && in_array('manager-summary', $availableTabs, true))
        <livewire:attendance.manager-summary
            :year="$year"
            :month="$month"
            :selectedStructureId="$selectedStructureId"
            :key="'attendance-manager-summary-'.$year.'-'.$month.'-'.($selectedStructureId ?? 'all')"
        />
    @endif

    @if($activeTab === 'puantaj' && in_array('puantaj', $availableTabs, true))
        <livewire:attendance.puantaj-grid
            :year="$year"
            :month="$month"
            :selectedStructureId="$selectedStructureId"
            :key="'attendance-puantaj-'.$year.'-'.$month.'-'.($selectedStructureId ?? 'all')"
        />
    @endif

    @if($activeTab === 'exceptions' && in_array('exceptions', $availableTabs, true))
        <livewire:attendance.exceptions-inbox
            :year="$year"
            :month="$month"
            :selectedStructureId="$selectedStructureId"
            :key="'attendance-exceptions-'.$year.'-'.$month.'-'.($selectedStructureId ?? 'all')"
        />
    @endif

    @if($activeTab === 'overtime' && in_array('overtime', $availableTabs, true))
        <livewire:attendance.overtime-board
            :year="$year"
            :month="$month"
            :selectedStructureId="$selectedStructureId"
            :key="'attendance-overtime-'.$year.'-'.$month.'-'.($selectedStructureId ?? 'all')"
        />
    @endif

    @if($activeTab === 'month-close' && in_array('month-close', $availableTabs, true))
        <livewire:attendance.month-close
            :year="$year"
            :month="$month"
            :key="'attendance-month-close-'.$year.'-'.$month"
        />
    @endif

    @if($activeTab === 'settings' && in_array('settings', $availableTabs, true))
        <livewire:attendance.settings />
    @endif

    @if($activeTab === 'history' && in_array('history', $availableTabs, true))
        <livewire:attendance.history-log
            :year="$year"
            :month="$month"
            :initialType="$historyType"
            :initialSubjectId="$historySubjectId"
            :key="'attendance-history-'.$year.'-'.$month.'-'.$historyType.'-'.($historySubjectId ?? 'all')"
        />
    @endif

    @if($activeTab === 'shifts' && in_array('shifts', $availableTabs, true))
        <livewire:attendance.shift-management />
    @endif

    @if($activeTab === 'calendar-regimes' && in_array('calendar-regimes', $availableTabs, true))
        <livewire:attendance.calendar-regimes
            :year="$year"
            :month="$month"
            :key="'attendance-calendar-regimes-'.$year.'-'.$month"
        />
    @endif

    </div>

    <x-datepicker :auto="false"></x-datepicker>
</div>
