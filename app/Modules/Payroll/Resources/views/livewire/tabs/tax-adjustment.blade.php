@php
    $fmt = fn ($value): string => number_format((float) $value, 2, ',', ' ');
    $diffClass = fn ($value): string => (float) $value >= 0.01 ? 'text-emerald-700' : ((float) $value <= -0.01 ? 'text-rose-700' : 'text-ink-faint');
    $rows = $this->rows;
@endphp

<div class="contents">
    <section class="overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="border-b border-hairline-subtle px-4 py-3">
            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('payroll::tax_adjustment.title') }}</h2>
            <p class="mt-0.5 text-[11.5px] leading-4 text-ink-faint">{{ __('payroll::tax_adjustment.hint') }}</p>
        </div>

        <div class="flex flex-wrap items-end gap-3 px-4 py-3.5">
            <x-ui.input-shell :label="__('payroll::tax_adjustment.from')" :error="$errors->first('fromMonth')">
                <x-ui.input type="month" wire:model="fromMonth" />
            </x-ui.input-shell>
            <x-ui.input-shell :label="__('payroll::tax_adjustment.to')" :error="$errors->first('toMonth')">
                <x-ui.input type="month" wire:model="toMonth" />
            </x-ui.input-shell>
            <x-pill-button variant="primary" wire:click="generate">{{ __('payroll::tax_adjustment.generate') }}</x-pill-button>
            @if ($loaded && $this->canExport())
                <x-pill-button variant="secondary" wire:click="export">{{ __('payroll::tax_adjustment.export') }}</x-pill-button>
            @endif
        </div>

        <p class="border-t border-hairline-subtle bg-[#fafafa] px-4 py-2.5 text-[11.5px] leading-4 text-ink-faint">{{ __('payroll::tax_adjustment.legal_note') }}</p>
    </section>

    @if ($loaded)
        <section class="mt-4 overflow-hidden rounded-xl border border-hairline bg-white">
            @if ($rows === [])
                <div class="px-4 py-8">
                    <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::tax_adjustment.empty')" />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[12px]">
                        <thead class="bg-[#fafafa] text-[10.5px] uppercase tracking-wide text-ink-faint">
                            <tr>
                                @if ($canManage)
                                    <th class="w-8 px-3 py-2"><input type="checkbox" wire:click="toggleAll" @checked(count($selected) === count($rows)) aria-label="{{ __('payroll::tax_adjustment.select_all') }}" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" /></th>
                                @endif
                                <th class="px-3 py-2">{{ __('payroll::tax_adjustment.cols.period') }}</th>
                                <th class="px-3 py-2">{{ __('payroll::dashboard.export.cols.full_name') }}</th>
                                <th class="px-3 py-2">{{ __('payroll::tax_adjustment.cols.regimes') }}</th>
                                @foreach (\App\Modules\Payroll\Application\Services\TaxAdjustmentReportService::COMPONENTS as $code)
                                    <th class="px-3 py-2 text-right">{{ __('payroll::tax_adjustment.components.'.$code) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline-subtle">
                            @foreach ($rows as $row)
                                <tr wire:key="payroll-tax-adjustment-{{ $row['payslip_id'] }}">
                                    @if ($canManage)
                                        <td class="px-3 py-2"><input type="checkbox" wire:model.live="selected" value="{{ $row['payslip_id'] }}" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" /></td>
                                    @endif
                                    <td class="hrm-num px-3 py-2 text-ink-soft">{{ $row['period'] }}</td>
                                    <td class="px-3 py-2 text-ink">
                                        {{ $row['full_name'] }} <span class="text-ink-faint">· {{ $row['tabel_no'] }}</span>
                                        @if ($row['refund_recorded'])
                                            <span class="ml-1 text-[11px] text-emerald-700">{{ __('payroll::tax_adjustment.refund_recorded') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-[11.5px] text-ink-faint">{{ $row['paid_regime'] }} → {{ $row['target_regime'] }}@if ($row['reliefs'] !== '')<br>{{ $row['reliefs'] }}@endif</td>
                                    @foreach (\App\Modules\Payroll\Application\Services\TaxAdjustmentReportService::COMPONENTS as $code)
                                        <td class="hrm-num px-3 py-2 text-right">
                                            <span class="block text-ink-faint">{{ $fmt($row[$code.'_paid']) }} → {{ $fmt($row[$code.'_now']) }}</span>
                                            <span class="block font-medium {{ $diffClass($row[$code.'_diff']) }}">{{ $fmt($row[$code.'_diff']) }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-[#fafafa] font-medium">
                            <tr>
                                <td colspan="{{ $canManage ? 4 : 3 }}" class="px-3 py-2 text-ink">{{ __('payroll::tax_adjustment.total') }}</td>
                                @foreach (\App\Modules\Payroll\Application\Services\TaxAdjustmentReportService::COMPONENTS as $code)
                                    <td class="hrm-num px-3 py-2 text-right {{ $diffClass($this->totals[$code.'_diff'] ?? 0) }}">{{ $fmt($this->totals[$code.'_diff'] ?? 0) }}</td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="border-t border-hairline-subtle px-4 py-2 text-[11.5px] text-ink-faint">{{ __('payroll::tax_adjustment.diff_legend') }}</p>

                @if ($canManage)
                    <div class="space-y-3 border-t border-hairline-subtle px-4 py-3.5">
                        <p class="text-[12.5px] font-medium text-ink">{{ __('payroll::tax_adjustment.refund_title') }}</p>
                        <p class="text-[11.5px] leading-4 text-ink-faint">{{ __('payroll::tax_adjustment.refund_hint') }}</p>
                        <div class="flex flex-wrap gap-4">
                            @foreach (\App\Modules\Payroll\Application\Services\TaxAdjustmentReportService::EMPLOYEE_SIDE as $code)
                                <label class="inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted">
                                    <input type="checkbox" wire:model="refundComponents" value="{{ $code }}" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" />
                                    {{ __('payroll::tax_adjustment.components.'.$code) }}
                                </label>
                            @endforeach
                        </div>
                        @error('refundComponents') <x-validation>{{ $message }}</x-validation> @enderror
                        <div class="grid gap-3 sm:grid-cols-4">
                            <x-ui.input-shell :label="__('payroll::dashboard.fields.year')" :error="$errors->first('payYear')">
                                <x-ui.input type="number" wire:model="payYear" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('payroll::dashboard.fields.month')" :error="$errors->first('payMonth')">
                                <x-ui.input type="number" min="1" max="12" wire:model="payMonth" />
                            </x-ui.input-shell>
                            <x-ui.input-shell class="sm:col-span-2" :label="__('payroll::tax_adjustment.reason')" :error="$errors->first('refundReason')">
                                <x-ui.input wire:model="refundReason" maxlength="500" />
                            </x-ui.input-shell>
                        </div>
                        @error('selected') <x-validation>{{ $message }}</x-validation> @enderror
                        <div class="flex justify-end">
                            <x-pill-button variant="primary" :disabled="count($selected) === 0" x-on:click="{{ $confirm('amber', 'tax_adjustment_refund', 'record_refund', 'recordRefunds()') }}">{{ __('payroll::tax_adjustment.record_refund', ['count' => count($selected)]) }}</x-pill-button>
                        </div>
                    </div>
                @endif
            @endif
        </section>
    @endif
</div>
