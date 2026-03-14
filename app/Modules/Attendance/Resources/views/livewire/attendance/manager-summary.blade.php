<div class="space-y-4">
    <x-surface-card :title="__('attendance::manager_summary.title')" icon="icons.line-settings-icon">
        <div class="space-y-3">
            <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('attendance::manager_summary.filters.title') }}</p>
                    <p class="text-sm text-zinc-500">{{ __('attendance::manager_summary.filters.description') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <x-label for="attendance-manager-summary-search">{{ __('attendance::manager_summary.filters.search') }}</x-label>
                    <x-livewire-input
                        id="attendance-manager-summary-search"
                        mode="gray"
                        name="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('attendance::manager_summary.filters.search_placeholder') }}"
                    />
                </div>
                <div class="flex items-end">
                    <label class="flex h-10 w-full items-center gap-2 rounded-lg bg-neutral-100 px-3 text-sm text-zinc-700 shadow-sm">
                        <input type="checkbox" wire:model.live="onlyProblematic" class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500" />
                        <span>{{ __('attendance::manager_summary.filters.only_problematic') }}</span>
                    </label>
                </div>
            </div>
        </div>
    </x-surface-card>

    @if($selectedStructureLabel)
        <div class="flex flex-wrap items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
            <x-small-badge mode="sky">{{ __('attendance::manager_summary.scope.badge') }}</x-small-badge>
            <span>{{ __('attendance::manager_summary.scope.description') }}</span>
            <span class="font-medium">{{ $selectedStructureLabel }}</span>
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <x-surface-card :title="__('attendance::manager_summary.cards.personnel_count')"><div class="text-2xl font-semibold text-zinc-800">{{ $totals['personnel_count'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::manager_summary.cards.problem_personnel')"><div class="text-2xl font-semibold text-rose-600">{{ $totals['problem_personnel_count'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::manager_summary.cards.absence_days')"><div class="text-2xl font-semibold text-amber-600">{{ $totals['absence_days'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::manager_summary.cards.late_minutes')"><div class="text-2xl font-semibold text-amber-600">{{ $totals['late_minutes'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::manager_summary.cards.early_leave_minutes')"><div class="text-2xl font-semibold text-zinc-800">{{ $totals['early_leave_minutes'] }}</div></x-surface-card>
        <x-surface-card :title="__('attendance::manager_summary.cards.open_exceptions')"><div class="text-2xl font-semibold text-blue-600">{{ $totals['open_exception_count'] }}</div></x-surface-card>
    </div>

    <div class="relative overflow-x-auto">
        <div class="inline-block min-w-full py-2 align-middle">
            <div class="overflow-visible">
                <x-table.tbl :headers="[
                    __('attendance::manager_summary.table.personnel'),
                    __('attendance::manager_summary.table.structure'),
                    __('attendance::manager_summary.table.scheduled_days'),
                    __('attendance::manager_summary.table.present_days'),
                    __('attendance::manager_summary.table.absence_days'),
                    __('attendance::manager_summary.table.late'),
                    __('attendance::manager_summary.table.early_leave'),
                    __('attendance::manager_summary.table.overtime'),
                    __('attendance::manager_summary.table.exceptions')
                ]" :title="__('attendance::manager_summary.table.title')">
                    @forelse($rows as $row)
                        @php
                            $hasProblem = (int) $row->absence_days > 0
                                || (int) $row->late_minutes > 0
                                || (int) $row->early_leave_minutes > 0
                                || (int) $row->open_exception_count > 0;
                        @endphp
                        <tr>
                            <x-table.td extraClasses="font-medium text-zinc-800">
                                <div class="flex flex-col gap-1">
                                    <span>{{ $row->surname }} {{ $row->name }} {{ $row->patronymic }}</span>
                                    <span class="font-mono uppercase text-xs text-zinc-500">{{ $row->tabel_no }}</span>
                                    @if($hasProblem)
                                        <x-small-badge mode="red">{{ __('attendance::manager_summary.labels.problematic') }}</x-small-badge>
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td extraClasses="text-zinc-600">
                                <span class="max-w-[18rem] truncate md:max-w-[24rem]" title="{{ $row->structure_path }}">
                                    {{ $row->structure_path ?: '—' }}
                                </span>
                            </x-table.td>
                            <x-table.td extraClasses="text-center">{{ (int) $row->scheduled_days }}</x-table.td>
                            <x-table.td extraClasses="text-center text-emerald-700">{{ (int) $row->present_days }}</x-table.td>
                            <x-table.td extraClasses="text-center text-rose-600">{{ (int) $row->absence_days }}</x-table.td>
                            <x-table.td extraClasses="text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="font-medium text-amber-600">{{ (int) $row->late_minutes }}</span>
                                    <span class="text-[11px] text-zinc-500">{{ __('attendance::manager_summary.labels.day_count', ['count' => (int) $row->late_days]) }}</span>
                                </div>
                            </x-table.td>
                            <x-table.td extraClasses="text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="font-medium text-zinc-700">{{ (int) $row->early_leave_minutes }}</span>
                                    <span class="text-[11px] text-zinc-500">{{ __('attendance::manager_summary.labels.day_count', ['count' => (int) $row->early_leave_days]) }}</span>
                                </div>
                            </x-table.td>
                            <x-table.td extraClasses="text-center text-emerald-700">{{ round(((int) $row->overtime_minutes) / 60, 1) }}</x-table.td>
                            <x-table.td extraClasses="text-center">
                                <x-small-badge :mode="(int) $row->open_exception_count > 0 ? 'red' : 'secondary'">
                                    {{ (int) $row->open_exception_count }}
                                </x-small-badge>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty :rows="8" />
                    @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

    <div>
        {{ $rows->links() }}
    </div>
</div>
