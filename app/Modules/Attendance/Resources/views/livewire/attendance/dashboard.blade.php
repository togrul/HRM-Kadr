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

        // Configuration, not daily work: rendered as a separate group after the work tabs.
        $settingsTabs = ['settings', 'shifts', 'calendar-regimes'];

        // Durations read as hours ("198", "7:30"); the unit sits beside the number as a suffix.
        $asHours = function (int|float|null $minutes): string {
            $minutes = (int) round((float) $minutes);
            $rest = $minutes % 60;

            return number_format(intdiv($minutes, 60), 0, ',', ' ').($rest > 0 ? ':'.str_pad((string) $rest, 2, '0', STR_PAD_LEFT) : '');
        };
    @endphp

    {{-- The panel carries the structure tree; the section nav is a horizontal strip in the page. --}}
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

            <x-pill-button variant="secondary" :href="route('docs.guide', ['focus' => 'attendance']).'#attendance-module'">
                {{ __('attendance::dashboard.actions.open_user_guide') }}
            </x-pill-button>
        </x-slot:actions>

        {{-- section nav: stays on the page so the panel can give the structure tree its
             full height, and wraps instead of scrolling so every section is reachable --}}
        {{-- day-to-day sections first; configuration sits apart as a quieter second group --}}
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <x-filter.nav wrap class="min-w-0">
                @foreach ($attendanceTabs as $tab => $labelKey)
                    @continue(! in_array($tab, $availableTabs, true) || in_array($tab, $settingsTabs, true))
                    <x-filter.item wire:navigate href="{{ $attendanceTabRoute($tab) }}" :active="$activeTab === $tab">
                        {{ __('attendance::dashboard.tabs.'.$labelKey) }}
                    </x-filter.item>
                @endforeach
            </x-filter.nav>

            @if (array_intersect($settingsTabs, $availableTabs) !== [])
                <div class="flex flex-wrap items-center gap-2 border-l border-hairline pl-3">
                    <span class="flex items-center gap-1 text-[11.5px] font-medium text-ink-faint">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        {{ __('attendance::dashboard.tabs.settings_group') }}
                    </span>
                    {{-- x-filter.item renders an <li>; outside its <ul> every item grows a list bullet --}}
                    <x-filter.nav wrap class="min-w-0">
                        @foreach ($settingsTabs as $tab)
                            @continue(! in_array($tab, $availableTabs, true))
                            <x-filter.item wire:navigate href="{{ $attendanceTabRoute($tab) }}" :active="$activeTab === $tab" class="text-ink-muted">
                                {{ __('attendance::dashboard.tabs.'.$attendanceTabs[$tab]) }}
                            </x-filter.item>
                        @endforeach
                    </x-filter.nav>
                </div>
            @endif
        </div>
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
                    <x-ui.metric-tile
                        :label="__('attendance::dashboard.metrics.'.$tile['metric'])"
                        :value="$tile['value']"
                        :tone="$tile['value'] > 0 ? $tile['tone'] : 'ink'"
                        :href="in_array($tile['tab'], $availableTabs, true) ? $attendanceTabRoute($tile['tab']) : null"
                    />
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
