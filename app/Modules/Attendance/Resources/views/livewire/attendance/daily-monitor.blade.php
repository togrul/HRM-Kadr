@php
    $dailyMonitorStatusLabels = [
        'all' => __('attendance::daily_monitor.statuses.all'),
        'present' => __('attendance::daily_monitor.statuses.present'),
        'late' => __('attendance::daily_monitor.statuses.late'),
        'absent' => __('attendance::daily_monitor.statuses.absent'),
        'missing' => __('attendance::daily_monitor.statuses.missing'),
        'manual_present' => __('attendance::daily_monitor.statuses.manual_present'),
        'holiday_worked' => __('attendance::daily_monitor.statuses.holiday_worked'),
        'weekend_worked' => __('attendance::daily_monitor.statuses.weekend_worked'),
        'manual_absence' => __('attendance::daily_monitor.statuses.manual_absence'),
        'unknown' => __('attendance::daily_monitor.statuses.unknown'),
    ];
@endphp

<div class="space-y-4">
    <x-surface-card :title="__('attendance::daily_monitor.title')" icon="icons.pending-icon">
        <div class="space-y-3">
            <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold uppercase  text-zinc-400">{{ __('attendance::daily_monitor.filters.title') }}</p>
                    <p class="text-sm text-zinc-500">{{ __('attendance::daily_monitor.filters.description') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <div>
                    <x-label for="attendance-monitor-date">{{ __('attendance::daily_monitor.filters.date') }}</x-label>
                    <input
                        id="attendance-monitor-date"
                        wire:model.live="date"
                        type="date"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    />
                </div>
                <div>
                    <x-label for="attendance-monitor-status">{{ __('attendance::daily_monitor.filters.status') }}</x-label>
                    <select
                        id="attendance-monitor-status"
                        wire:model.live="statusFilter"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    >
                        <option value="all">{{ __('attendance::daily_monitor.statuses.all') }}</option>
                        <option value="present">{{ __('attendance::daily_monitor.statuses.present') }}</option>
                        <option value="late">{{ __('attendance::daily_monitor.statuses.late') }}</option>
                        <option value="absent">{{ __('attendance::daily_monitor.statuses.absent') }}</option>
                        <option value="missing">{{ __('attendance::daily_monitor.statuses.missing') }}</option>
                    </select>
                </div>
                <div>
                    <x-label for="attendance-monitor-search">{{ __('attendance::daily_monitor.filters.search') }}</x-label>
                    <x-livewire-input
                        id="attendance-monitor-search"
                        mode="gray"
                        name="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('attendance::daily_monitor.filters.search_placeholder') }}"
                    />
                </div>
            </div>
        </div>
    </x-surface-card>

    @if($selectedStructureLabel)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('attendance::daily_monitor.scope.badge') }}</x-small-badge>
            <span>{{ __('attendance::daily_monitor.scope.description') }}</span>
            <span class="font-medium">{{ $selectedStructureLabel }}</span>
        </div>
    @endif

    <div class="space-y-2">
        <div class="flex items-center justify-between gap-2">
            <p class="text-[11px] font-semibold uppercase  text-zinc-400">{{ __('attendance::daily_monitor.breakdown.title') }}</p>
            <span class="text-xs text-zinc-500">{{ __('attendance::daily_monitor.breakdown.description') }}</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-surface-card :title="__('attendance::daily_monitor.cards.present')"><div class="text-2xl font-semibold text-emerald-600">{{ $totals['present'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::daily_monitor.cards.late')"><div class="text-2xl font-semibold text-amber-600">{{ $totals['late'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::daily_monitor.cards.absent')"><div class="text-2xl font-semibold text-rose-600">{{ $totals['absent'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::daily_monitor.cards.missing')"><div class="text-2xl font-semibold text-blue-600">{{ $totals['missing'] }}</div></x-surface-card>
        </div>
    </div>

    <div class="space-y-3">
    <div class="relative min-h-[220px] overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-visible">
                <x-table.tbl :headers="[
                    __('attendance::daily_monitor.table.tabel_no'),
                    __('attendance::daily_monitor.table.full_name'),
                    __('attendance::daily_monitor.table.status'),
                    __('attendance::daily_monitor.table.worked_hours'),
                    __('attendance::daily_monitor.table.late_minutes'),
                    __('attendance::daily_monitor.table.early_minutes')
                ]" :title="__('attendance::daily_monitor.table.title')">
                    @forelse($rows as $row)
                        @php
                            $status = $row->attendance_status ?? ($row->ledger_id ? 'unknown' : 'missing');
                            $badgeClass = match($status) {
                                'present', 'manual_present', 'holiday_worked', 'weekend_worked' => 'bg-emerald-100 text-emerald-700',
                                'absent', 'manual_absence' => 'bg-rose-100 text-rose-700',
                                'missing' => 'bg-blue-100 text-blue-700',
                                default => 'bg-zinc-100 text-zinc-700',
                            };
                        @endphp
                        <tr>
                            <x-table.td extraClasses="font-medium font-mono uppercase !text-zinc-500">{{ $row->tabel_no }}</x-table.td>
                            <x-table.td extraClasses="text-zinc-700">
                                <div class="flex flex-col">
                                    <span>{{ $row->surname }} {{ $row->name }} {{ $row->patronymic }}</span>
                                    @if($row->structure_path)
                                        <span class="max-w-[18rem] truncate text-xs text-zinc-500 md:max-w-[24rem]" title="{{ $row->structure_path }}">
                                            {{ $row->structure_path }}
                                        </span>
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td>
                                <span class="inline-flex rounded-full px-2 py-1 uppercase text-xs font-medium {{ $badgeClass }}">{{ $dailyMonitorStatusLabels[$status] ?? $status }}</span>
                            </x-table.td>
                            <x-table.td extraClasses="text-center text-zinc-700">{{ (int) round(((int) $row->worked_minutes) / 60) }}</x-table.td>
                            <x-table.td extraClasses="text-center !text-rose-500">{{ (int) $row->late_minutes }}</x-table.td>
                            <x-table.td extraClasses="text-center !text-amber-500">{{ (int) $row->early_leave_minutes }}</x-table.td>
                        </tr>
                    @empty
                        <x-table.empty :rows="6" />
                    @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

    <div>
        {{ $rows->links() }}
    </div>
    </div>
</div>
