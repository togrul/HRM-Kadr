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
            {{-- period control: one pill instead of two labelled form fields --}}
            <div class="inline-flex h-9 items-center gap-1 rounded-[10px] border border-hairline bg-[#f4f4f5] px-2">
                <svg class="h-3.5 w-3.5 shrink-0 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/></svg>
                <label class="sr-only" for="attendance-month">{{ __('attendance::dashboard.filters.month') }}</label>
                <select
                    id="attendance-month"
                    wire:model.live="month"
                    class="hrm-num h-7 border-0 bg-transparent py-0 pl-1 pr-5 text-[12.5px] text-ink focus:ring-0"
                >
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}">{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
                <span class="text-ink-faint">.</span>
                <label class="sr-only" for="attendance-year">{{ __('attendance::dashboard.filters.year') }}</label>
                <input
                    id="attendance-year"
                    type="number"
                    min="2000"
                    max="2100"
                    wire:model.live="year"
                    class="hrm-num h-7 w-[62px] border-0 bg-transparent px-1 py-0 text-[12.5px] text-ink focus:ring-0"
                />
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
