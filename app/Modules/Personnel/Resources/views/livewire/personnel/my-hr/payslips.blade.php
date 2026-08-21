@php
    $lbl = 'text-[11px] font-semibold uppercase tracking-wider text-zinc-500';
    $ghostBtn = 'inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#f5f5f7] px-4 py-2.5 text-sm font-semibold tracking-tight text-zinc-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.85)] transition hover:bg-zinc-200/70 hover:text-zinc-900 active:scale-[0.98]';
@endphp

<div class="space-y-6">
    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-zinc-900 to-zinc-700 text-white shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 7h6m-6 4h6m-6 4h4M5 4h14a1 1 0 011 1v15l-3-2-2 2-2-2-2 2-2-2-3 2V5a1 1 0 011-1z" />
                </svg>
            </span>
            <div class="space-y-1">
                <span class="{{ $lbl }}">{{ __('personnel::my_hr.payslips.kicker') }}</span>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('personnel::my_hr.payslips.title') }}</h2>
                <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('personnel::my_hr.payslips.description') }}</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <span class="{{ $lbl }}">{{ __('personnel::my_hr.payslips.list') }} ({{ $this->payslips->count() }})</span>
            <div class="mt-3 space-y-2">
                @forelse ($this->payslips as $payslip)
                    <button type="button" wire:click="viewPayslip({{ $payslip->id }})" @class([
                        'flex w-full items-center justify-between gap-3 rounded-2xl border px-4 py-3 text-left transition hover:border-zinc-300 hover:shadow-sm',
                        'border-sky-200 bg-sky-50/60' => $selectedPayslipId === $payslip->id,
                        'border-zinc-200 bg-white' => $selectedPayslipId !== $payslip->id,
                    ])>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold tracking-tight text-zinc-900">{{ $payslip->run?->period?->code }}</p>
                            <p class="mt-0.5 text-xs text-zinc-500">{{ optional($payslip->run?->locked_at)->format('d.m.Y') }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-semibold tracking-tight text-zinc-900">{{ number_format((float) $payslip->net, 2) }} {{ $payslip->currency }}</span>
                    </button>
                @empty
                    <x-ui.empty-state icon="icons.document-icon" :title="__('personnel::my_hr.payslips.empty')" />
                @endforelse
            </div>
        </div>

        @if ($this->selectedPayslip)
            @php $ps = $this->selectedPayslip; @endphp
            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="{{ $lbl }}">{{ __('personnel::my_hr.payslips.detail') }}</span>
                        <p class="mt-1 text-lg font-semibold tracking-tight text-zinc-950">{{ $ps->run?->period?->code }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('payroll.payslip.print', $ps->id) }}" target="_blank" class="{{ $ghostBtn }}">PDF</a>
                        <button type="button" wire:click="closePayslip" class="{{ $ghostBtn }}">{{ __('personnel::my_hr.payslips.close') }}</button>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-zinc-100 bg-zinc-50/70 px-4 py-3">
                        <span class="{{ $lbl }}">{{ __('personnel::my_hr.payslips.gross') }}</span>
                        <p class="mt-1 text-lg font-semibold tracking-tight text-zinc-950">{{ number_format((float) $ps->gross, 2) }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-zinc-50/70 px-4 py-3">
                        <span class="{{ $lbl }}">{{ __('personnel::my_hr.payslips.net') }}</span>
                        <p class="mt-1 text-lg font-semibold tracking-tight text-emerald-700">{{ number_format((float) $ps->net, 2) }}</p>
                    </div>
                </div>

                <div class="mt-4 divide-y divide-zinc-100 rounded-2xl border border-zinc-100">
                    @foreach ($ps->lines->where('kind', '!=', 'employer') as $line)
                        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $line->kind === 'deduction' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ __('payroll::dashboard.kinds.'.$line->kind) }}</span>
                                <span class="text-sm font-medium text-zinc-800">{{ $line->name }}</span>
                            </div>
                            <span class="text-sm font-semibold tracking-tight {{ $line->kind === 'deduction' ? 'text-rose-600' : 'text-zinc-900' }}">{{ $line->kind === 'deduction' ? '−' : '' }}{{ number_format((float) $line->amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
