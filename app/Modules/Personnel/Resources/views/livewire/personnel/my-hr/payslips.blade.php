<div class="flex flex-col gap-4">
    <section class="rounded-xl border border-hairline bg-white px-4 py-3">
        <p class="hrm-eyebrow">{{ __('personnel::my_hr.payslips.kicker') }}</p>
        <p class="mt-1 max-w-2xl text-[12.5px] leading-relaxed text-ink-muted">{{ __('personnel::my_hr.payslips.description') }}</p>
    </section>

    <div class="grid gap-4 xl:grid-cols-2">
        <section class="rounded-xl border border-hairline bg-white">
            <div class="border-b border-hairline-subtle px-4 py-3">
                <p class="hrm-eyebrow">{{ __('personnel::my_hr.payslips.list') }} ({{ $this->payslips->count() }})</p>
            </div>

            <div class="space-y-2 p-3">
                @forelse ($this->payslips as $payslip)
                    <button
                        type="button"
                        wire:key="my-hr-payslip-{{ $payslip->id }}"
                        wire:click="viewPayslip({{ $payslip->id }})"
                        @class([
                            'flex w-full items-center justify-between gap-3 rounded-xl border px-3.5 py-2.5 text-left transition',
                            'border-ink bg-[#fafafa]' => $selectedPayslipId === $payslip->id,
                            'border-hairline bg-white hover:border-zinc-300 hover:shadow-card' => $selectedPayslipId !== $payslip->id,
                        ])
                    >
                        <div class="min-w-0 leading-tight">
                            <p class="hrm-num truncate text-[13px] font-medium text-ink">{{ $payslip->run?->period?->code }}</p>
                            <p class="hrm-num mt-0.5 text-[11.5px] text-ink-faint">{{ optional($payslip->run?->locked_at)->format('d.m.Y') }}</p>
                        </div>
                        <span class="hrm-num shrink-0 text-[13px] font-semibold text-ink">{{ number_format((float) $payslip->net, 2) }} {{ $payslip->currency }}</span>
                    </button>
                @empty
                    <x-ui.empty-state icon="icons.document-icon" :title="__('personnel::my_hr.payslips.empty')" />
                @endforelse
            </div>
        </section>

        @if ($this->selectedPayslip)
            @php $ps = $this->selectedPayslip; @endphp
            <section class="rounded-xl border border-hairline bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                    <div class="min-w-0">
                        <p class="hrm-eyebrow">{{ __('personnel::my_hr.payslips.detail') }}</p>
                        <p class="hrm-num mt-1 truncate text-[14px] font-semibold tracking-[-0.02em] text-ink">{{ $ps->run?->period?->code }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <x-pill-button :href="route('payroll.payslip.print', $ps->id)" target="_blank">PDF</x-pill-button>
                        <x-pill-button wire:click="closePayslip" wire:loading.attr="disabled" wire:target="closePayslip">
                            {{ __('personnel::my_hr.payslips.close') }}
                        </x-pill-button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 p-3">
                    <x-fact-tile :label="__('personnel::my_hr.payslips.gross')" :value="number_format((float) $ps->gross, 2)" />
                    <x-fact-tile :label="__('personnel::my_hr.payslips.net')" :value="number_format((float) $ps->net, 2)" tone="green" />
                </div>

                <div class="divide-y divide-hairline-subtle border-t border-hairline-subtle">
                    @foreach ($ps->lines->where('kind', '!=', 'employer') as $line)
                        <div wire:key="my-hr-payslip-line-{{ $line->id }}" class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <div class="flex min-w-0 items-center gap-2">
                                <x-small-badge :mode="$line->kind === 'deduction' ? 'rose' : 'green'">{{ __('payroll::dashboard.kinds.'.$line->kind) }}</x-small-badge>
                                <span class="truncate text-[12.5px] text-ink-soft">{{ $line->name }}</span>
                            </div>
                            <span @class([
                                'hrm-num shrink-0 text-[13px] font-semibold',
                                'text-[#be123c]' => $line->kind === 'deduction',
                                'text-ink' => $line->kind !== 'deduction',
                            ])>{{ $line->kind === 'deduction' ? '−' : '' }}{{ number_format((float) $line->amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
