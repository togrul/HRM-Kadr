<div class="contents">
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex flex-col gap-1 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('payroll::dashboard.runs.title') }}</h2>
                @if ($this->canViewAmounts())
                    <p class="mt-0.5 text-[11.5px] text-ink-faint">
                        {{ __('payroll::dashboard.runs.forecast') }} <span class="px-0.5">—</span>
                        <span class="hrm-num">{{ $money($this->forecastBaseTotal) }}</span> {{ $periodCurrency ?? 'AZN' }}
                    </p>
                @endif
            </div>
            <span class="shrink-0 text-[11.5px] text-ink-faint">{{ $activePeriodLabel }}</span>
        </div>

        <x-table.tbl :headers="[
            __('payroll::dashboard.fields.period'),
            __('payroll::dashboard.runs.type'),
            __('payroll::dashboard.runs.employees'),
            __('payroll::dashboard.fields.gross'),
            __('payroll::dashboard.fields.deductions'),
            __('payroll::dashboard.fields.net'),
            __('payroll::dashboard.fields.status'),
            __('payroll::dashboard.columns.actions'),
        ]">
            @forelse ($this->runs as $run)
                <tr wire:key="payroll-run-{{ $run->id }}" @class(['bg-[#fafafa]' => $selectedRunId === $run->id])>
                    <x-table.td standart-width>
                        <button type="button" wire:click="$parent.selectRun({{ $run->id }})" class="min-w-0 max-w-[220px] text-left">
                            <p class="truncate text-[13px] font-medium text-ink">{{ $this->periodLabel($run->period) }}</p>
                            <p class="truncate text-[11px] text-ink-faint">{{ $run->regime?->name ?? __('payroll::dashboard.fields.all_regimes') }}</p>
                        </button>
                    </x-table.td>

                    <x-table.td>
                        <span class="{{ $chip }} bg-[#f4f4f5] text-ink-muted">{{ __('payroll::dashboard.run_types.'.$run->run_type) }}</span>
                    </x-table.td>

                    <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $num($run->employee_count) }}</span></x-table.td>
                    <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $money($run->gross_total) }}</span></x-table.td>
                    <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $money($run->deduction_total) }}</span></x-table.td>
                    <x-table.td><span class="hrm-num text-[13px] font-semibold text-ink">{{ $money($run->net_total) }}</span></x-table.td>

                    <x-table.td>
                        <span class="{{ $statusChip($run->status) }}">{{ __('payroll::dashboard.status.'.$run->status) }}</span>
                    </x-table.td>

                    <x-table.td :isButton="true">
                        <div class="flex items-center justify-end gap-1.5">
                            <x-pill-button variant="secondary" wire:click="$parent.selectRun({{ $run->id }})">{{ __('payroll::dashboard.actions.view_payslips') }}</x-pill-button>

                            @if ($canManage && $run->isEditable())
                                <x-pill-button variant="secondary" wire:click="calculateRun({{ $run->id }})">{{ __('payroll::dashboard.actions.calculate') }}</x-pill-button>
                            @endif

                            @if ($this->canApprove() && $run->status === 'calculated')
                                <x-pill-button variant="secondary" wire:click="approveRun({{ $run->id }})">{{ __('payroll::dashboard.actions.approve') }}</x-pill-button>
                            @endif

                            @if ($this->canLock() && in_array($run->status, ['calculated', 'approved'], true))
                                <x-pill-button variant="primary" x-on:click="{{ $confirm('emerald', 'lock', 'lock', 'lockRun('.$run->id.')') }}">{{ __('payroll::dashboard.actions.lock') }}</x-pill-button>
                            @endif

                            @if ($this->canLock() && $run->status === 'locked')
                                <x-pill-button variant="secondary" x-on:click="{{ $confirm('amber', 'reopen', 'reopen', 'reopenRun('.$run->id.')') }}">{{ __('payroll::dashboard.actions.reopen') }}</x-pill-button>
                            @endif

                            @if ($canManage && $run->isEditable())
                                <button type="button" x-on:click="{{ $confirmDelete('deleteRun('.$run->id.')') }}" title="{{ __('payroll::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                            @endif
                        </div>
                    </x-table.td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-4">
                        <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.runs.empty')" />
                    </td>
                </tr>
            @endforelse
        </x-table.tbl>
    </section>

    <div class="grid gap-4 xl:grid-cols-2">
        {{-- statutory deductions --}}
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('payroll::dashboard.statutory.title') }}</h2>
                <span class="shrink-0 text-[11.5px] text-ink-faint">{{ $activePeriodLabel }}</span>
            </div>

            @if (count($this->statutoryTotals))
                <div class="space-y-3.5 px-4 py-4">
                    @foreach ($this->statutoryTotals as $row)
                        <x-context-panel.progress :label="$row['label']" :value="$row['pct']" :caption="$money($row['amount'])" />
                    @endforeach
                </div>
            @else
                <div class="px-4 py-4">
                    <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.statutory.empty')" />
                </div>
            @endif
        </section>

        {{-- loans / advances --}}
        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('payroll::dashboard.loans.title') }}</h2>
                <span class="shrink-0 text-[11.5px] text-ink-faint">{{ __('payroll::dashboard.loans.active', ['count' => $num($activeLoanCount)]) }}</span>
            </div>

            <x-table.tbl :headers="[
                __('payroll::dashboard.columns.employee'),
                __('payroll::dashboard.fields.loan_type'),
                __('payroll::dashboard.fields.principal'),
                __('payroll::dashboard.fields.remaining'),
            ]">
                @forelse ($this->activeLoans as $loan)
                    <tr wire:key="payroll-active-loan-{{ $loan->id }}">
                        <x-table.td standart-width>
                            <button type="button" wire:click="$parent.manageLoans(@js($loan->tabel_no), @js(trim($loan->personnel?->surname.' '.$loan->personnel?->name)))" class="min-w-0 max-w-[220px] text-left">
                                <p class="truncate text-[13px] font-medium text-ink">{{ trim($loan->personnel?->surname.' '.$loan->personnel?->name) ?: $loan->tabel_no }}</p>
                                <p class="hrm-num truncate text-[11px] text-ink-faint">{{ $loan->tabel_no }}</p>
                            </button>
                        </x-table.td>
                        <x-table.td>
                            <span class="{{ $chip }} {{ $loan->type === 'loan' ? 'bg-sky-50 text-sky-700' : 'bg-[#f4f4f5] text-ink-muted' }}">{{ __('payroll::dashboard.loans.types.'.$loan->type) }}</span>
                        </x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $money($loan->principal) }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] font-semibold text-ink">{{ $money($loan->remaining) }}</span></x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-4">
                            <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.loans.empty')" />
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </section>
    </div>
</div>
