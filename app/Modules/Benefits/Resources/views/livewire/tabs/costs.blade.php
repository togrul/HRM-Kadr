@php
    $report = $this->report;
    $totals = $report['totals'];
@endphp

<div class="contents">
    <section class="rounded-xl border border-hairline bg-white px-4 py-3.5">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-ui.input-shell :label="__('benefits::dashboard.fields.month')">
                <x-ui.input type="month" wire:model.live="month" />
            </x-ui.input-shell>
            <x-ui.input-shell :label="__('benefits::dashboard.fields.plan')">
                <x-ui.select wire:model.live="planFilter">
                    <option value="">{{ __('benefits::dashboard.filters.all_plans') }}</option>
                    @foreach ($this->planOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.input-shell>
            <x-ui.input-shell :label="__('benefits::dashboard.fields.structure')">
                <x-ui.select wire:model.live="structureFilter">
                    <option value="">{{ __('benefits::dashboard.filters.all_structures') }}</option>
                    @foreach ($this->structureOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </x-ui.select>
            </x-ui.input-shell>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-4">
            @foreach (['employer' => 'employer_cost', 'employee' => 'employee_contributions', 'enrollees' => 'enrollees', 'dependents' => 'dependents'] as $key => $label)
                <div class="rounded-[10px] border border-hairline bg-[#fafafa] px-3 py-2.5">
                    <p class="text-[11px] font-medium text-ink-faint">{{ __('benefits::dashboard.costs.'.$label) }}</p>
                    <p class="hrm-num mt-1 text-[18px] font-semibold tracking-[-0.02em] text-ink">{{ in_array($key, ['employer', 'employee'], true) ? $money($totals[$key]) : (int) $totals[$key] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    @foreach (['by_plan' => 'plan', 'by_structure' => 'structure'] as $group => $column)
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('benefits::dashboard.costs.'.$group) }}</h2>
            </div>
            <x-table.tbl :headers="[
                __('benefits::dashboard.columns.'.$column),
                __('benefits::dashboard.costs.enrollees'),
                __('benefits::dashboard.costs.dependents'),
                __('benefits::dashboard.costs.employer_cost'),
                __('benefits::dashboard.costs.employee_contributions'),
                __('benefits::dashboard.costs.total'),
            ]">
                @forelse ($report[$group] as $row)
                    <tr wire:key="benefits-cost-{{ $group }}-{{ $loop->index }}">
                        <x-table.td standart-width>
                            <p class="max-w-[260px] truncate text-[13px] font-medium text-ink">{{ $row[$column] }}</p>
                            @if ($column === 'plan')
                                <p class="text-[11px] text-ink-faint">{{ __('benefits::dashboard.types.'.$row['type']) }}</p>
                            @endif
                        </x-table.td>
                        <x-table.td><span class="hrm-num text-[13px]">{{ $row['enrollees'] }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-[13px]">{{ $row['dependents'] }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] text-ink">{{ $money($row['employer']) }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] text-ink">{{ $money($row['employee']) }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] font-medium text-ink">{{ $money($row['total']) }}</span></x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10">
                            <x-ui.empty-state icon="icons.report-chart-icon" :title="__('benefits::dashboard.costs.empty')" />
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </section>
    @endforeach
</div>
