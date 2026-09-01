<div class="space-y-4">
    {{-- toolbar: search + scope, flush on the page like the other converted lists --}}
    <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
        <label class="relative w-full sm:max-w-[360px]">
            <span class="sr-only">{{ __('attendance::manager_summary.filters.search') }}</span>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input
                id="attendance-manager-summary-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('attendance::manager_summary.filters.search_placeholder') }}"
                class="h-[34px] w-full rounded-[10px] border border-hairline bg-[#f4f4f5] pl-9 pr-3 text-[12.5px] text-ink placeholder:text-ink-faint focus:border-ink focus:bg-white focus:ring-0"
            />
        </label>

        <label class="inline-flex h-[34px] shrink-0 cursor-pointer items-center gap-2 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12.5px] font-medium text-ink-soft transition hover:bg-[#fafafa] hover:text-ink">
            <input type="checkbox" wire:model.live="onlyProblematic" class="h-3.5 w-3.5 rounded border-hairline text-ink focus:ring-0 focus:ring-offset-0" />
            <span>{{ __('attendance::manager_summary.filters.only_problematic') }}</span>
        </label>

        @if($selectedStructureLabel)
            <div class="flex min-w-0 items-center gap-2 text-[11.5px] text-ink-faint sm:ml-auto">
                <span class="hrm-eyebrow shrink-0">{{ __('attendance::manager_summary.scope.badge') }}</span>
                <span class="truncate text-ink-soft" title="{{ $selectedStructureLabel }}">{{ $selectedStructureLabel }}</span>
            </div>
        @endif
    </div>

    {{-- KPI strip --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <x-ui.metric-tile :label="__('attendance::manager_summary.cards.personnel_count')" :value="$totals['personnel_count']" />
        <x-ui.metric-tile :label="__('attendance::manager_summary.cards.problem_personnel')" :value="$totals['problem_personnel_count']" tone="rose" />
        <x-ui.metric-tile :label="__('attendance::manager_summary.cards.absence_days')" :value="$totals['absence_days']" tone="amber" />
        <x-ui.metric-tile :label="__('attendance::manager_summary.cards.late_minutes')" :value="$totals['late_minutes']" tone="amber" />
        <x-ui.metric-tile :label="__('attendance::manager_summary.cards.early_leave_minutes')" :value="$totals['early_leave_minutes']" />
        <x-ui.metric-tile :label="__('attendance::manager_summary.cards.open_exceptions')" :value="$totals['open_exception_count']" tone="blue" />
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
                        <tr class="group/row transition-colors hover:bg-[#fafafa]">
                            <x-table.td>
                                <div class="min-w-0 max-w-[240px] leading-tight">
                                    <p class="truncate text-[13px] font-medium text-ink">{{ $row->surname }} {{ $row->name }} {{ $row->patronymic }}</p>
                                    <p class="hrm-num mt-0.5 text-[11.5px] text-ink-faint">#{{ $row->tabel_no }}</p>
                                    @if($hasProblem)
                                        <x-small-badge mode="rose" dot class="mt-1">{{ __('attendance::manager_summary.labels.problematic') }}</x-small-badge>
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td standart-width>
                                {{-- the column shows the current unit only; the full chain stays on hover --}}
                                <p class="max-w-[280px] truncate text-[13px] text-ink-soft" title="{{ $row->structure_path }}">
                                    {{ $row->structure_name ?: '—' }}
                                </p>
                            </x-table.td>
                            <x-table.td extraClasses="text-center">
                                <span class="hrm-num text-[13px] text-ink-soft">{{ (int) $row->scheduled_days }}</span>
                            </x-table.td>
                            <x-table.td extraClasses="text-center">
                                <span class="hrm-num text-[13px] text-[#047857]">{{ (int) $row->present_days }}</span>
                            </x-table.td>
                            <x-table.td extraClasses="text-center">
                                <span @class(['hrm-num text-[13px]', 'text-[#be123c]' => (int) $row->absence_days > 0, 'text-ink-faint' => (int) $row->absence_days === 0])>{{ (int) $row->absence_days }}</span>
                            </x-table.td>
                            <x-table.td extraClasses="text-center">
                                <span @class(['hrm-num block text-[13px]', 'text-[#b45309]' => (int) $row->late_minutes > 0, 'text-ink-faint' => (int) $row->late_minutes === 0])>{{ (int) $row->late_minutes }}</span>
                                <span class="mt-0.5 block text-[11px] text-ink-faint">{{ __('attendance::manager_summary.labels.day_count', ['count' => (int) $row->late_days]) }}</span>
                            </x-table.td>
                            <x-table.td extraClasses="text-center">
                                <span @class(['hrm-num block text-[13px]', 'text-ink-soft' => (int) $row->early_leave_minutes > 0, 'text-ink-faint' => (int) $row->early_leave_minutes === 0])>{{ (int) $row->early_leave_minutes }}</span>
                                <span class="mt-0.5 block text-[11px] text-ink-faint">{{ __('attendance::manager_summary.labels.day_count', ['count' => (int) $row->early_leave_days]) }}</span>
                            </x-table.td>
                            <x-table.td extraClasses="text-center">
                                <span class="hrm-num text-[13px] text-[#047857]">{{ round(((int) $row->overtime_minutes) / 60, 1) }}</span>
                            </x-table.td>
                            <x-table.td extraClasses="text-center">
                                <x-small-badge :mode="(int) $row->open_exception_count > 0 ? 'rose' : 'secondary'" dot>
                                    {{ (int) $row->open_exception_count }}
                                </x-small-badge>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty :rows="9" />
                    @endforelse
                </x-table.tbl>
            </div>
        </div>
    </div>

    <div>
        {{ $rows->links() }}
    </div>
</div>
