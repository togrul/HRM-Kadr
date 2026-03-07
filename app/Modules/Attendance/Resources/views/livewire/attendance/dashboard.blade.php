<div class="flex flex-col space-y-4 px-6 py-4">
    <x-slot name="sidebar">
        <livewire:structure.sidebar wire:key="attendance-structure-sidebar" />
    </x-slot>

    <x-surface-card :title="__('Attendance tracking')" icon="icons.clock-icon">
        <div class="space-y-4">
            <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ __('Workspace') }}</p>
                    <span class="text-sm text-zinc-500">{{ __('This screen manages attendance, shifts and manual entries.') }}</span>
                </div>

                <div class="grid grid-cols-2 gap-2 self-start md:self-auto">
                    <div class="flex flex-col">
                        <x-label for="attendance-year">{{ __('Year') }}</x-label>
                        <input
                            id="attendance-year"
                            type="number"
                            min="2000"
                            max="2100"
                            wire:model.live="year"
                            class="h-10 w-24 rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                        />
                    </div>
                    <div class="flex flex-col">
                        <x-label for="attendance-month">{{ __('Month') }}</x-label>
                        <select
                            id="attendance-month"
                            wire:model.live="month"
                            class="h-10 w-24 rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                        >
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ __('Attendance sections') }}</p>
                    <span class="text-xs text-zinc-500">{{ __('Switch views without leaving the attendance workspace.') }}</span>
                </div>

                <x-filter.nav class="min-w-0">
                    @if(in_array('overview', $availableTabs, true))
                        <x-filter.item wire:click.prevent="switchTab('overview')" :active="$activeTab === 'overview'">
                            {{ __('Summary') }}
                        </x-filter.item>
                    @endif
                    @if(in_array('daily-monitor', $availableTabs, true))
                        <x-filter.item wire:click.prevent="switchTab('daily-monitor')" :active="$activeTab === 'daily-monitor'">
                            {{ __('Daily monitor') }}
                        </x-filter.item>
                    @endif
                    @if(in_array('puantaj', $availableTabs, true))
                        <x-filter.item wire:click.prevent="switchTab('puantaj')" :active="$activeTab === 'puantaj'">
                            {{ __('Timesheet grid') }}
                        </x-filter.item>
                    @endif
                    @if(in_array('exceptions', $availableTabs, true))
                        <x-filter.item wire:click.prevent="switchTab('exceptions')" :active="$activeTab === 'exceptions'">
                            {{ __('Exceptions inbox') }}
                        </x-filter.item>
                    @endif
                    @if(in_array('overtime', $availableTabs, true))
                        <x-filter.item wire:click.prevent="switchTab('overtime')" :active="$activeTab === 'overtime'">
                            {{ __('Overtime board') }}
                        </x-filter.item>
                    @endif
                    @if(in_array('month-close', $availableTabs, true))
                        <x-filter.item wire:click.prevent="switchTab('month-close')" :active="$activeTab === 'month-close'">
                            {{ __('Month close') }}
                        </x-filter.item>
                    @endif
                    @if(in_array('manual', $availableTabs, true))
                        <x-filter.item wire:click.prevent="switchTab('manual')" :active="$activeTab === 'manual'">
                            {{ __('Manual entries') }}
                        </x-filter.item>
                    @endif
                    @if(in_array('settings', $availableTabs, true))
                        <x-filter.item wire:click.prevent="switchTab('settings')" :active="$activeTab === 'settings'">
                            {{ __('Settings') }}
                        </x-filter.item>
                    @endif
                    @if(in_array('shifts', $availableTabs, true))
                        <x-filter.item wire:click.prevent="switchTab('shifts')" :active="$activeTab === 'shifts'">
                            {{ __('Shifts') }}
                        </x-filter.item>
                    @endif
                </x-filter.nav>
            </div>
        </div>
    </x-surface-card>

    @if($activeTab === 'overview')
        @php
            $kpi = $overview['kpi'] ?? [];
            $trendDirection = $kpi['overtime_trend_direction'] ?? 'flat';
            $trendClass = match($trendDirection) {
                'up' => 'text-rose-600',
                'down' => 'text-emerald-600',
                default => 'text-zinc-600',
            };
        @endphp

        <x-surface-card :title="__('Attendance statistics')" icon="icons.calendar-icon">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <x-surface-card :title="__('Workdays')"><div class="text-2xl font-semibold text-zinc-800">{{ $overview['workdays'] ?? 0 }}</div></x-surface-card>
                <x-surface-card :title="__('Holiday / Weekend')"><div class="text-2xl font-semibold text-zinc-800">{{ $overview['holidays'] ?? 0 }}</div></x-surface-card>
                <x-surface-card :title="__('Scheduled minutes')"><div class="text-2xl font-semibold text-zinc-800">{{ $overview['scheduled_minutes'] ?? 0 }}</div></x-surface-card>
                <x-surface-card :title="__('Worked minutes')"><div class="text-2xl font-semibold text-zinc-800">{{ $overview['worked_minutes'] ?? 0 }}</div></x-surface-card>
                <x-surface-card :title="__('Overtime minutes')"><div class="text-2xl font-semibold text-zinc-800">{{ $overview['overtime_minutes'] ?? 0 }}</div></x-surface-card>
            </div>
        </x-surface-card>

        <x-surface-card :title="__('Process statistics')" icon="icons.pending-icon">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-surface-card :title="__('Coverage')">
                    <div class="text-2xl font-semibold text-zinc-800">{{ $kpi['coverage_pct'] ?? 0 }}%</div>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('Actual / planned work hours') }}</p>
                </x-surface-card>
                <x-surface-card :title="__('Absence rate')">
                    <div class="text-2xl font-semibold text-zinc-800">{{ $kpi['absence_rate_pct'] ?? 0 }}%</div>
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ __(':absence / :scheduled planned days', ['absence' => $kpi['absence_days'] ?? 0, 'scheduled' => $kpi['scheduled_days'] ?? 0]) }}
                    </p>
                </x-surface-card>
                <x-surface-card :title="__('Compliance')">
                    <div class="text-2xl font-semibold text-zinc-800">{{ $kpi['compliance_pct'] ?? 0 }}%</div>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('Days without late or early leave') }}</p>
                </x-surface-card>
                <x-surface-card :title="__('Overtime trend')">
                    <div class="text-2xl font-semibold {{ $trendClass }}">{{ $kpi['overtime_trend_pct'] ?? 0 }}%</div>
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ __('Previous month: :minutes minutes', ['minutes' => $kpi['overtime_previous_minutes'] ?? 0]) }}
                    </p>
                </x-surface-card>
            </div>

            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <x-surface-card :title="__('Manual pending')"><div class="text-2xl font-semibold text-amber-600">{{ $overview['manual_pending_count'] ?? 0 }}</div></x-surface-card>
                <x-surface-card :title="__('Unprocessed punches')"><div class="text-2xl font-semibold text-blue-600">{{ $overview['raw_pending_count'] ?? 0 }}</div></x-surface-card>
                <x-surface-card :title="__('Open exceptions')"><div class="text-2xl font-semibold text-rose-600">{{ $overview['open_exception_count'] ?? 0 }}</div></x-surface-card>
                <x-surface-card :title="__('Pending overtime')"><div class="text-2xl font-semibold text-amber-600">{{ $overview['pending_overtime_count'] ?? 0 }}</div></x-surface-card>
            </div>
        </x-surface-card>
    @endif

    @if($activeTab === 'manual' && in_array('manual', $availableTabs, true))
        <livewire:attendance.manual-entries :embedded="true" :selectedStructureId="$selectedStructureId" :key="'attendance-manual-'.$year.'-'.$month.'-'.($selectedStructureId ?? 'all')" />
    @endif

    @if($activeTab === 'daily-monitor' && in_array('daily-monitor', $availableTabs, true))
        <livewire:attendance.daily-monitor :selectedStructureId="$selectedStructureId" :key="'attendance-monitor-'.$year.'-'.$month.'-'.($selectedStructureId ?? 'all')" />
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

    @if($activeTab === 'shifts' && in_array('shifts', $availableTabs, true))
        <livewire:attendance.shift-management />
    @endif
</div>
