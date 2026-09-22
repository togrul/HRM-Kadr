@php
    $exports = [
        ['bank', 'exportBankFile'],
        ['bank_csv', 'exportBankCsv'],
        ['gl', 'exportGl'],
        ['state', 'exportStateReport'],
    ];
@endphp

<div class="contents">
    @if (! $this->selectedRun)
        <section class="rounded-xl border border-hairline bg-white px-4 py-6">
            <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.payslips.select_run')" />
        </section>
    @else
        @php $run = $this->selectedRun; @endphp

        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                <div class="min-w-0">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ $this->periodLabel($run->period) }}</h2>
                    <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ $run->regime?->name ?? __('payroll::dashboard.fields.all_regimes') }}</p>
                </div>
                <span class="{{ $statusChip($run->status) }}">{{ __('payroll::dashboard.status.'.$run->status) }}</span>
            </div>

            <div class="grid gap-3 px-4 py-4 sm:grid-cols-3">
                <x-context-panel.meta :columns="3" :items="[
                    ['label' => __('payroll::dashboard.fields.gross'), 'value' => $money($run->gross_total)],
                    ['label' => __('payroll::dashboard.fields.deductions'), 'value' => $money($run->deduction_total)],
                    ['label' => __('payroll::dashboard.fields.net'), 'value' => $money($run->net_total)],
                ]" class="sm:col-span-3" />
            </div>

            @if ($this->canExport())
                <div class="flex flex-wrap items-center gap-2 border-t border-hairline-subtle px-4 py-3">
                    <span class="hrm-eyebrow mr-1">{{ __('payroll::dashboard.export.title') }}</span>
                    @foreach ($exports as [$key, $method])
                        <x-pill-button variant="secondary" wire:key="payroll-run-export-{{ $key }}" wire:click="$parent.{{ $method }}({{ $run->id }})">{{ __('payroll::dashboard.export.actions.'.$key) }}</x-pill-button>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-xl border border-hairline bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('payroll::dashboard.payslips.title') }}</h2>
                <span class="hrm-num shrink-0 text-[11.5px] text-ink-faint">{{ $num($this->runPayslips->count()) }}</span>
            </div>

            <x-table.tbl :headers="[
                __('payroll::dashboard.columns.employee'),
                __('payroll::dashboard.fields.gross'),
                __('payroll::dashboard.fields.deductions'),
                __('payroll::dashboard.fields.net'),
                __('payroll::dashboard.columns.actions'),
            ]">
                @forelse ($this->runPayslips as $payslip)
                    <tr wire:key="payroll-payslip-{{ $payslip->id }}" @class(['bg-[#fafafa]' => $selectedPayslipId === $payslip->id])>
                        <x-table.td standart-width>
                            <button type="button" wire:click="viewPayslip({{ $payslip->id }})" class="min-w-0 max-w-[260px] text-left">
                                <p class="truncate text-[13px] font-medium text-ink">{{ $payslip->personnel?->surname }} {{ $payslip->personnel?->name }}</p>
                                <p class="hrm-num truncate text-[11px] text-ink-faint">{{ $payslip->tabel_no }}</p>
                            </button>
                        </x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $money($payslip->gross) }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $money($payslip->total_deductions) }}</span></x-table.td>
                        <x-table.td><span class="hrm-num text-[13px] font-semibold text-ink">{{ $payslip->mask($payslip->net) }} {{ $payslip->currency }}</span></x-table.td>
                        <x-table.td :isButton="true">
                            @if ($canManage && ! $run->isLocked())
                                <button type="button" x-on:click="{{ $confirmDelete('deletePayslip('.$payslip->id.')') }}" title="{{ __('payroll::dashboard.actions.delete') }}" class="{{ $delBtn }} ml-auto">{!! $delIcon !!}</button>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4">
                            <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.payslips.empty')" />
                        </td>
                    </tr>
                @endforelse
            </x-table.tbl>
        </section>

        @if ($this->selectedPayslip)
            @php $ps = $this->selectedPayslip; @endphp
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                    <div class="min-w-0">
                        <p class="hrm-eyebrow">{{ __('payroll::dashboard.payslips.detail') }}</p>
                        <h2 class="mt-0.5 text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ $ps->personnel?->surname }} {{ $ps->personnel?->name }}</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($ps->status === 'locked')
                            <x-pill-button variant="secondary" :href="route('payroll.payslip.print', $ps->id)" target="_blank">{{ __('payroll::dashboard.export.title') }} (PDF)</x-pill-button>
                        @endif
                        <x-pill-button variant="secondary" wire:click="closePayslip">{{ __('payroll::dashboard.actions.close') }}</x-pill-button>
                    </div>
                </div>

                @if ((float) $ps->proration_factor < 1)
                    <div class="border-b border-hairline-subtle px-4 py-2.5">
                        <span class="{{ $chip }} bg-amber-50 text-amber-700">
                            {{ __('payroll::dashboard.fields.proration') }}: <span class="hrm-num ml-1">{{ number_format((float) $ps->proration_factor * 100, 1) }}%</span>
                        </span>
                    </div>
                @endif

                <div class="divide-y divide-hairline-subtle">
                    @foreach ($ps->lines as $line)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="{{ $chip }} {{ $line->kind === 'deduction' ? 'bg-rose-50 text-rose-700' : ($line->kind === 'employer' ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700') }}">{{ __('payroll::dashboard.kinds.'.$line->kind) }}</span>
                                <span class="truncate text-[13px] text-ink-soft">{{ $line->name }}</span>
                            </div>
                            <span class="hrm-num shrink-0 text-[13px] font-semibold {{ $line->kind === 'deduction' ? 'text-rose-600' : 'text-ink' }}">{{ $line->kind === 'deduction' ? '−' : '' }}{{ $money($line->amount) }}</span>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between gap-3 bg-[#fafafa] px-4 py-3">
                        <span class="hrm-eyebrow">{{ __('payroll::dashboard.fields.net') }}</span>
                        <span class="hrm-num text-[14px] font-semibold text-ink">{{ $ps->mask($ps->net) }} {{ $ps->currency }}</span>
                    </div>

                    @if (abs((float) $this->retro['total']) >= 0.01)
                        <div class="flex items-center justify-between gap-3 bg-amber-50 px-4 py-2.5">
                            <span class="hrm-eyebrow text-amber-700">{{ __('payroll::dashboard.fields.retro') }}</span>
                            <span class="hrm-num text-[13px] font-semibold text-amber-800">{{ $money($this->retro['total']) }} {{ $ps->currency }}</span>
                        </div>
                    @endif
                </div>
            </section>
        @endif
    @endif
</div>
