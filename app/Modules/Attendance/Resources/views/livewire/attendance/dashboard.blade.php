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
        <x-filter.nav wrap class="min-w-0">
            @foreach ($attendanceTabs as $tab => $labelKey)
                @continue(! in_array($tab, $availableTabs, true))
                <x-filter.item wire:navigate href="{{ $attendanceTabRoute($tab) }}" :active="$activeTab === $tab">
                    {{ __('attendance::dashboard.tabs.'.$labelKey) }}
                </x-filter.item>
            @endforeach
        </x-filter.nav>
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

        <section class="space-y-3">
            <p class="hrm-eyebrow">{{ __('attendance::dashboard.cards.attendance_statistics') }}</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.workdays')" :value="$overview['workdays'] ?? 0" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.holiday_weekend')" :value="$overview['holidays'] ?? 0" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.scheduled_minutes')" :value="$overview['scheduled_minutes'] ?? 0" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.worked_minutes')" :value="$overview['worked_minutes'] ?? 0" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.overtime_minutes')" :value="$overview['overtime_minutes'] ?? 0" tone="amber" />
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
                    :hint="__('attendance::dashboard.metrics.overtime_trend_hint', ['minutes' => $kpi['overtime_previous_minutes'] ?? 0])"
                />
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.manual_pending')" :value="$overview['manual_pending_count'] ?? 0" tone="amber" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.unprocessed_punches')" :value="$overview['raw_pending_count'] ?? 0" tone="blue" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.open_exceptions')" :value="$overview['open_exception_count'] ?? 0" tone="rose" />
                <x-ui.metric-tile :label="__('attendance::dashboard.metrics.pending_overtime')" :value="$overview['pending_overtime_count'] ?? 0" tone="amber" />
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
