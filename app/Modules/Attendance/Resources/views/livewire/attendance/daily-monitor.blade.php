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

    // The tiles carry each bucket's share of the day, as in the design.
    $dailyMonitorCounted = (int) ($totals['present'] + $totals['late'] + $totals['absent'] + $totals['missing']);
    $dailyMonitorShare = fn (int $value): string => $dailyMonitorCounted > 0
        ? round($value / $dailyMonitorCounted * 100).'%'
        : '0%';

    $dailyMonitorChips = ['all', 'present', 'late', 'absent', 'missing'];
@endphp

<div class="space-y-4">
    {{-- day at a glance --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.metric-tile
            :label="__('attendance::daily_monitor.cards.present')"
            :value="$totals['present']"
            :suffix="$dailyMonitorShare((int) $totals['present'])"
            tone="green"
        />
        <x-ui.metric-tile
            :label="__('attendance::daily_monitor.cards.late')"
            :value="$totals['late']"
            :suffix="$dailyMonitorShare((int) $totals['late'])"
            tone="amber"
        />
        <x-ui.metric-tile
            :label="__('attendance::daily_monitor.cards.absent')"
            :value="$totals['absent']"
            :suffix="$dailyMonitorShare((int) $totals['absent'])"
            tone="rose"
        />
        <x-ui.metric-tile
            :label="__('attendance::daily_monitor.cards.missing')"
            :value="$totals['missing']"
            :suffix="$dailyMonitorShare((int) $totals['missing'])"
        />
    </div>

    {{-- employee status list --}}
    <section class="overflow-hidden rounded-2xl border border-hairline bg-white shadow-card">
        <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <h2 class="text-[15px] font-semibold tracking-[-0.02em] text-ink">{{ __('attendance::daily_monitor.table.title') }}</h2>
                <p class="mt-0.5 text-[12px] text-ink-faint">
                    {{ $selectedStructureLabel ? __('attendance::daily_monitor.scope.description') : __('attendance::daily_monitor.filters.description') }}
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <label class="inline-flex h-[34px] items-center gap-1.5 rounded-[10px] border border-hairline bg-[#f4f4f5] px-2.5">
                    <span class="sr-only">{{ __('attendance::daily_monitor.filters.date') }}</span>
                    <svg class="h-3.5 w-3.5 shrink-0 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/></svg>
                    <input
                        id="attendance-monitor-date"
                        wire:model.live="date"
                        type="date"
                        class="hrm-num h-7 border-0 bg-transparent p-0 text-[12.5px] text-ink focus:ring-0"
                    />
                </label>

                <label class="relative w-full sm:w-[260px]">
                    <span class="sr-only">{{ __('attendance::daily_monitor.filters.search') }}</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input
                        id="attendance-monitor-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('attendance::daily_monitor.filters.search_placeholder') }}"
                        class="h-[34px] w-full rounded-[10px] border border-hairline bg-[#f4f4f5] pl-9 pr-3 text-[12.5px] text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-0"
                    />
                </label>
            </div>
        </div>

        {{-- status buckets as chips: the five the list can actually filter by --}}
        <div class="border-b border-hairline-subtle px-4 py-2.5">
            <x-filter.nav wrap class="min-w-0">
                @foreach ($dailyMonitorChips as $chip)
                    <x-filter.item
                        wire:click.prevent="$set('statusFilter', '{{ $chip }}')"
                        wire:loading.attr="disabled"
                        :active="$statusFilter === $chip"
                    >{{ $dailyMonitorStatusLabels[$chip] }}</x-filter.item>
                @endforeach
            </x-filter.nav>
        </div>

        <div class="relative min-h-[220px] overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[
                        __('attendance::daily_monitor.table.tabel_no'),
                        __('attendance::daily_monitor.table.full_name'),
                        __('attendance::daily_monitor.table.status'),
                        __('attendance::daily_monitor.table.worked_hours'),
                        __('attendance::daily_monitor.table.late_minutes'),
                        __('attendance::daily_monitor.table.early_minutes')
                    ]">
                        @forelse($rows as $row)
                            @php
                                $status = $row->attendance_status ?? ($row->ledger_id ? 'unknown' : 'missing');
                                $statusTone = match($status) {
                                    'present', 'manual_present', 'holiday_worked', 'weekend_worked' => 'green',
                                    'late' => 'amber',
                                    'absent', 'manual_absence' => 'rose',
                                    'missing' => 'blue',
                                    default => 'secondary',
                                };
                                $fullname = trim($row->surname.' '.$row->name.' '.$row->patronymic);
                            @endphp
                            <tr class="group/row transition-colors hover:bg-[#fafafa]">
                                <x-table.td>
                                    @if($row->personnel_id)
                                        <a
                                            href="{{ route('personnel.show', $row->personnel_id) }}"
                                            wire:navigate
                                            class="hrm-num text-[13px] font-medium text-[#0369a1] transition hover:underline"
                                        >{{ $row->tabel_no }}</a>
                                    @else
                                        <span class="hrm-num text-[13px] text-ink-soft">{{ $row->tabel_no }}</span>
                                    @endif
                                </x-table.td>
                                <x-table.td standart-width>
                                    <div class="flex items-center gap-2.5">
                                        <x-avatar :name="$fullname" />
                                        <div class="min-w-0 max-w-[240px] leading-tight">
                                            <p class="truncate text-[13px] font-medium text-ink">{{ $fullname }}</p>
                                            @if($row->structure_path)
                                                {{-- current unit only; the full chain stays on hover --}}
                                                <p class="mt-0.5 truncate text-[11.5px] text-ink-faint" title="{{ $row->structure_path }}">
                                                    {{ $row->structure_name }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </x-table.td>
                                <x-table.td>
                                    <x-small-badge :mode="$statusTone" dot>{{ $dailyMonitorStatusLabels[$status] ?? $status }}</x-small-badge>
                                </x-table.td>
                                <x-table.td extraClasses="text-center">
                                    <span class="hrm-num text-[13px] text-ink-soft">{{ (int) round(((int) $row->worked_minutes) / 60) }}</span>
                                </x-table.td>
                                <x-table.td extraClasses="text-center">
                                    <span @class(['hrm-num text-[13px]', 'text-[#be123c]' => (int) $row->late_minutes > 0, 'text-ink-faint' => (int) $row->late_minutes === 0])>{{ (int) $row->late_minutes }}</span>
                                </x-table.td>
                                <x-table.td extraClasses="text-center">
                                    <span @class(['hrm-num text-[13px]', 'text-[#b45309]' => (int) $row->early_leave_minutes > 0, 'text-ink-faint' => (int) $row->early_leave_minutes === 0])>{{ (int) $row->early_leave_minutes }}</span>
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty :rows="6" />
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>

        <div class="border-t border-hairline-subtle px-4 py-2.5">
            {{ $rows->links() }}
        </div>
    </section>
</div>
