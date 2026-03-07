<div class="space-y-4">
    <x-surface-card :title="__('Daily monitor')" icon="icons.pending-icon">
        <div class="space-y-3">
            <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ __('Filters') }}</p>
                    <p class="text-sm text-zinc-500">{{ __('Review today attendance status, late arrivals and missing records for the selected scope.') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <div>
                    <x-label for="attendance-monitor-date">{{ __('Date') }}</x-label>
                    <input
                        id="attendance-monitor-date"
                        wire:model.live="date"
                        type="date"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    />
                </div>
                <div>
                    <x-label for="attendance-monitor-status">{{ __('Status') }}</x-label>
                    <select
                        id="attendance-monitor-status"
                        wire:model.live="statusFilter"
                        class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500"
                    >
                        <option value="all">{{ __('all') }}</option>
                        <option value="present">{{ __('present') }}</option>
                        <option value="late">{{ __('late') }}</option>
                        <option value="absent">{{ __('absent') }}</option>
                        <option value="missing">{{ __('missing ledger') }}</option>
                    </select>
                </div>
                <div>
                    <x-label for="attendance-monitor-search">{{ __('Search') }}</x-label>
                    <x-livewire-input
                        id="attendance-monitor-search"
                        mode="gray"
                        name="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Name or tabel no') }}"
                    />
                </div>
            </div>
        </div>
    </x-surface-card>

    @if($selectedStructureLabel)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('Structure scope') }}</x-small-badge>
            <span>{{ __('Showing personnel from the selected structure tree only.') }}</span>
            <span class="font-medium">{{ $selectedStructureLabel }}</span>
        </div>
    @endif

    <div class="space-y-2">
        <div class="flex items-center justify-between gap-2">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ __('Daily status breakdown') }}</p>
            <span class="text-xs text-zinc-500">{{ __('Live counters for selected date and structure scope.') }}</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-surface-card :title="__('Present')"><div class="text-2xl font-semibold text-emerald-600">{{ $totals['present'] }}</div></x-surface-card>
        <x-surface-card :title="__('Late')"><div class="text-2xl font-semibold text-amber-600">{{ $totals['late'] }}</div></x-surface-card>
        <x-surface-card :title="__('Absent')"><div class="text-2xl font-semibold text-rose-600">{{ $totals['absent'] }}</div></x-surface-card>
        <x-surface-card :title="__('Missing ledger')"><div class="text-2xl font-semibold text-blue-600">{{ $totals['missing'] }}</div></x-surface-card>
        </div>
    </div>

    <div class="space-y-3">
    <div class="relative min-h-[220px] overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-visible">
                <x-table.tbl :headers="[
                    __('Tabel no'),
                    __('Full name'),
                    __('Status'),
                    __('Worked (hours)'),
                    __('Late (min)'),
                    __('Early (min)')
                ]" :title="__('Personnel status list')">
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
                                <span class="inline-flex rounded-full px-2 py-1 uppercase text-xs font-medium {{ $badgeClass }}">{{ __($status) }}</span>
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
