@php
    $inp = 'w-full rounded-xl border-0 bg-[#f5f5f7] px-4 py-2.5 text-sm font-medium text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)] outline-none ring-0 transition focus:bg-white focus:ring-2 focus:ring-zinc-200';
    $lbl = 'text-[11px] font-semibold uppercase tracking-wider text-zinc-500';
    $primaryBtn = 'inline-flex items-center justify-center rounded-xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold tracking-tight text-white shadow-sm transition hover:bg-zinc-800';
    $ghostBtn = 'inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#f5f5f7] px-4 py-2.5 text-sm font-semibold tracking-tight text-zinc-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.85)] transition hover:bg-zinc-200/70 hover:text-zinc-900 active:scale-[0.98]';
    $delBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-[11px] text-zinc-400 transition hover:bg-rose-50 hover:text-rose-600';
    $delIcon = '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>';
    $statusClass = fn (string $s) => match ($s) {
        'locked' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'approved' => 'border-amber-200 bg-amber-50 text-amber-700',
        'calculated' => 'border-sky-200 bg-sky-50 text-sky-700',
        default => 'border-zinc-200 bg-white text-zinc-500',
    };
    $canManage = $this->canManage();
@endphp

<div class="space-y-6 px-4 py-6 sm:px-6">
    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-zinc-900 to-zinc-700 text-white shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            <div class="space-y-1">
                <span class="{{ $lbl }}">{{ __('payroll::dashboard.kicker') }}</span>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('payroll::dashboard.title') }}</h2>
                <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('payroll::dashboard.description') }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->summaryStats as $stat)
                <div class="group rounded-2xl border border-zinc-200 bg-zinc-50/60 px-5 py-4 transition hover:border-zinc-300 hover:bg-white hover:shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="{{ $lbl }}">{{ __('payroll::dashboard.summary.'.$stat['key']) }}</span>
                        <span class="h-2 w-2 rounded-full {{ $stat['accent'] }}"></span>
                    </div>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            <x-filter.nav>
                @foreach ($this->allowedTabsList as $tab)
                    <x-filter.item wire:click.prevent="switchTab('{{ $tab }}')" :active="$activeTab === $tab">
                        {{ __('payroll::dashboard.tabs.'.$tab) }}
                    </x-filter.item>
                @endforeach
            </x-filter.nav>
        </div>
    </div>

    {{-- ================= RUNS ================= --}}
    @if ($activeTab === 'runs')
        @if ($this->canViewAmounts())
            <div class="flex items-center justify-between rounded-[24px] border border-sky-200 bg-sky-50/60 px-5 py-4">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-sky-700">{{ __('payroll::dashboard.runs.forecast') }}</span>
                <span class="text-xl font-semibold tracking-tight text-zinc-950">{{ number_format($this->forecastBaseTotal, 2) }} AZN</span>
            </div>
        @endif

        @if ($canManage)
            <div class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('payroll::dashboard.periods.title') }}</h3>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="{{ $lbl }}">{{ __('payroll::dashboard.fields.year') }}</label>
                            <input type="number" wire:model.defer="periodForm.year" class="{{ $inp }}" />
                            @error('periodForm.year') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <label class="{{ $lbl }}">{{ __('payroll::dashboard.fields.month') }}</label>
                            <input type="number" min="1" max="12" wire:model.defer="periodForm.month" class="{{ $inp }}" />
                            @error('periodForm.month') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="flex items-end">
                            <button type="button" wire:click="createPeriod" class="{{ $primaryBtn }} w-full">{{ __('payroll::dashboard.actions.create_period') }}</button>
                        </div>
                    </div>
                </div>

                <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('payroll::dashboard.runs.new') }}</h3>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="{{ $lbl }}">{{ __('payroll::dashboard.fields.period') }}</label>
                            <select wire:model.live="runForm.payroll_period_id" class="{{ $inp }}">
                                <option value="">---</option>
                                @foreach ($this->periods as $period)
                                    <option value="{{ $period->id }}">{{ $period->code }}</option>
                                @endforeach
                            </select>
                            @error('runForm.payroll_period_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div>
                            <x-ui.select-dropdown :label="__('payroll::dashboard.fields.regime')" mode="gray" direction="auto" wire:model.live="runForm.regime_id" :model="$this->regimeOptions" :placeholder="__('payroll::dashboard.fields.all_regimes')" />
                        </div>
                        <div>
                            <label class="{{ $lbl }}">{{ __('payroll::dashboard.runs.type') }}</label>
                            <select wire:model.live="runForm.run_type" class="{{ $inp }}">
                                <option value="regular">{{ __('payroll::dashboard.run_types.regular') }}</option>
                                <option value="off_cycle">{{ __('payroll::dashboard.run_types.off_cycle') }}</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="button" wire:click="createRun" class="{{ $primaryBtn }} w-full">{{ __('payroll::dashboard.actions.create_run') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <span class="{{ $lbl }}">{{ __('payroll::dashboard.periods.list') }} ({{ $this->periods->count() }})</span>
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse ($this->periods as $period)
                    <div class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 py-1 pl-3.5 pr-1.5 text-sm font-medium text-zinc-700">
                        <span>{{ $period->code }}</span>
                        @if ($canManage)
                            <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('payroll::dashboard.confirm.delete')), confirmText: @js(__('payroll::dashboard.actions.delete')), run: () => $wire.deletePeriod({{ $period->id }}) })" class="inline-flex h-6 w-6 items-center justify-center rounded-full text-zinc-400 transition hover:bg-rose-100 hover:text-rose-600">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        @endif
                    </div>
                @empty
                    <span class="text-sm text-zinc-400">{{ __('payroll::dashboard.periods.empty') }}</span>
                @endforelse
            </div>
        </div>

        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('payroll::dashboard.runs.title') }}</h3>
            <div class="mt-4 space-y-2">
                @forelse ($this->runs as $run)
                    <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold tracking-tight text-zinc-900">{{ $run->period?->code }}</span>
                                    <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold tracking-tight {{ $statusClass($run->status) }}">{{ __('payroll::dashboard.status.'.$run->status) }}</span>
                                    <span class="rounded-full border border-zinc-200 bg-white px-2.5 py-0.5 text-xs font-medium text-zinc-500">{{ $run->regime?->name ?? __('payroll::dashboard.fields.all_regimes') }}</span>
                                    @if ($run->run_type === 'off_cycle')
                                        <span class="rounded-full border border-violet-200 bg-violet-50 px-2.5 py-0.5 text-xs font-semibold text-violet-700">{{ __('payroll::dashboard.run_types.off_cycle') }}</span>
                                    @endif
                                </div>
                                <p class="mt-1.5 text-xs text-zinc-500">
                                    {{ __('payroll::dashboard.runs.employees') }}: {{ $run->employee_count }}
                                    · {{ __('payroll::dashboard.fields.net') }}: {{ $this->canViewAmounts() ? number_format((float) $run->net_total, 2) : '•••' }} {{ $run->period?->currency }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" wire:click="selectRun({{ $run->id }})" class="{{ $ghostBtn }}">{{ __('payroll::dashboard.actions.view_payslips') }}</button>
                                @if ($canManage && $run->isEditable())
                                    <button type="button" wire:click="calculateRun({{ $run->id }})" class="{{ $ghostBtn }}">{{ __('payroll::dashboard.actions.calculate') }}</button>
                                @endif
                                @if ($this->canApprove() && $run->status === 'calculated')
                                    <button type="button" wire:click="approveRun({{ $run->id }})" class="{{ $ghostBtn }}">{{ __('payroll::dashboard.actions.approve') }}</button>
                                @endif
                                @if ($this->canLock() && in_array($run->status, ['calculated', 'approved'], true))
                                    <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'emerald', message: @js(__('payroll::dashboard.confirm.lock')), confirmText: @js(__('payroll::dashboard.actions.lock')), run: () => $wire.lockRun({{ $run->id }}) })" class="{{ $primaryBtn }}">{{ __('payroll::dashboard.actions.lock') }}</button>
                                @endif
                                @if ($this->canLock() && $run->status === 'locked')
                                    <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'amber', message: @js(__('payroll::dashboard.confirm.reopen')), confirmText: @js(__('payroll::dashboard.actions.reopen')), run: () => $wire.reopenRun({{ $run->id }}) })" class="{{ $ghostBtn }}">{{ __('payroll::dashboard.actions.reopen') }}</button>
                                @endif
                                @if ($canManage && $run->isEditable())
                                    <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('payroll::dashboard.confirm.delete')), confirmText: @js(__('payroll::dashboard.actions.delete')), run: () => $wire.deleteRun({{ $run->id }}) })" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.runs.empty')" />
                @endforelse
            </div>
        </div>
    @endif

    {{-- ================= PAYSLIPS ================= --}}
    @if ($activeTab === 'payslips')
        @if (! $this->selectedRun)
            <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.payslips.select_run')" />
        @else
            @php $run = $this->selectedRun; @endphp
            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <span class="{{ $lbl }}">{{ __('payroll::dashboard.payslips.run') }}</span>
                        <p class="mt-1 text-lg font-semibold tracking-tight text-zinc-950">{{ $run->period?->code }} · {{ $run->regime?->name ?? __('payroll::dashboard.fields.all_regimes') }}</p>
                    </div>
                    <span class="rounded-full border px-3 py-1 text-xs font-semibold tracking-tight {{ $statusClass($run->status) }}">{{ __('payroll::dashboard.status.'.$run->status) }}</span>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-zinc-100 bg-zinc-50/70 px-4 py-3">
                        <span class="{{ $lbl }}">{{ __('payroll::dashboard.fields.gross') }}</span>
                        <p class="mt-1 text-xl font-semibold tracking-tight text-zinc-950">{{ $this->canViewAmounts() ? number_format((float) $run->gross_total, 2) : '•••' }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-zinc-50/70 px-4 py-3">
                        <span class="{{ $lbl }}">{{ __('payroll::dashboard.fields.deductions') }}</span>
                        <p class="mt-1 text-xl font-semibold tracking-tight text-zinc-950">{{ $this->canViewAmounts() ? number_format((float) $run->deduction_total, 2) : '•••' }}</p>
                    </div>
                    <div class="rounded-2xl border border-zinc-100 bg-zinc-50/70 px-4 py-3">
                        <span class="{{ $lbl }}">{{ __('payroll::dashboard.fields.net') }}</span>
                        <p class="mt-1 text-xl font-semibold tracking-tight text-zinc-950">{{ $this->canViewAmounts() ? number_format((float) $run->net_total, 2) : '•••' }}</p>
                    </div>
                </div>

                @if ($this->canExport())
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4">
                        <span class="{{ $lbl }} mr-1">{{ __('payroll::dashboard.export.title') }}</span>
                        <button type="button" wire:click="exportBankFile({{ $run->id }})" class="{{ $ghostBtn }}">{{ __('payroll::dashboard.export.actions.bank') }}</button>
                        <button type="button" wire:click="exportBankCsv({{ $run->id }})" class="{{ $ghostBtn }}">{{ __('payroll::dashboard.export.actions.bank_csv') }}</button>
                        <button type="button" wire:click="exportGl({{ $run->id }})" class="{{ $ghostBtn }}">{{ __('payroll::dashboard.export.actions.gl') }}</button>
                        <button type="button" wire:click="exportStateReport({{ $run->id }})" class="{{ $ghostBtn }}">{{ __('payroll::dashboard.export.actions.state') }}</button>
                    </div>
                @endif
            </div>

            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <span class="{{ $lbl }}">{{ __('payroll::dashboard.payslips.title') }} ({{ $this->runPayslips->count() }})</span>
                <div class="mt-3 space-y-2">
                    @forelse ($this->runPayslips as $payslip)
                        <div class="flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 transition hover:border-zinc-300 hover:shadow-sm">
                            <button type="button" wire:click="viewPayslip({{ $payslip->id }})" class="flex min-w-0 flex-1 items-center justify-between gap-3 text-left">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold tracking-tight text-zinc-900">{{ $payslip->personnel?->surname }} {{ $payslip->personnel?->name }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500">{{ $payslip->tabel_no }}</p>
                                </div>
                                <span class="shrink-0 text-sm font-semibold tracking-tight text-zinc-900">{{ $payslip->mask($payslip->net) }} {{ $payslip->currency }}</span>
                            </button>
                            @if ($canManage && ! $run->isLocked())
                                <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('payroll::dashboard.confirm.delete')), confirmText: @js(__('payroll::dashboard.actions.delete')), run: () => $wire.deletePayslip({{ $payslip->id }}) })" class="{{ $delBtn }} shrink-0">{!! $delIcon !!}</button>
                            @endif
                        </div>
                    @empty
                        <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.payslips.empty')" />
                    @endforelse
                </div>
            </div>

            @if ($this->selectedPayslip)
                @php $ps = $this->selectedPayslip; @endphp
                <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="{{ $lbl }}">{{ __('payroll::dashboard.payslips.detail') }}</span>
                            <p class="mt-1 text-lg font-semibold tracking-tight text-zinc-950">{{ $ps->personnel?->surname }} {{ $ps->personnel?->name }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($ps->status === 'locked')
                                <a href="{{ route('payroll.payslip.print', $ps->id) }}" target="_blank" class="{{ $ghostBtn }}">{{ __('payroll::dashboard.export.title') }} (PDF)</a>
                            @endif
                            <button type="button" wire:click="closePayslip" class="{{ $ghostBtn }}">{{ __('payroll::dashboard.actions.close') }}</button>
                        </div>
                    </div>
                    @if ((float) $ps->proration_factor < 1)
                        <div class="mt-3 inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold tracking-tight text-amber-700">
                            {{ __('payroll::dashboard.fields.proration') }}: {{ number_format((float) $ps->proration_factor * 100, 1) }}%
                        </div>
                    @endif
                    <div class="mt-4 divide-y divide-zinc-100 rounded-2xl border border-zinc-100">
                        @foreach ($ps->lines as $line)
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $line->kind === 'deduction' ? 'bg-rose-50 text-rose-700' : ($line->kind === 'employer' ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700') }}">{{ __('payroll::dashboard.kinds.'.$line->kind) }}</span>
                                    <span class="text-sm font-medium text-zinc-800">{{ $line->name }}</span>
                                </div>
                                <span class="text-sm font-semibold tracking-tight {{ $line->kind === 'deduction' ? 'text-rose-600' : 'text-zinc-900' }}">{{ $line->kind === 'deduction' ? '−' : '' }}{{ $this->canViewAmounts() ? number_format((float) $line->amount, 2) : '•••' }}</span>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between gap-3 bg-zinc-50/70 px-4 py-3">
                            <span class="text-sm font-semibold uppercase tracking-wider text-zinc-500">{{ __('payroll::dashboard.fields.net') }}</span>
                            <span class="text-base font-semibold tracking-tight text-zinc-950">{{ $ps->mask($ps->net) }} {{ $ps->currency }}</span>
                        </div>
                    </div>
                    @if (abs((float) $this->retro['total']) >= 0.01)
                        <div class="mt-3 flex items-center justify-between rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2.5">
                            <span class="text-xs font-semibold uppercase tracking-wider text-amber-700">{{ __('payroll::dashboard.fields.retro') }}</span>
                            <span class="text-sm font-semibold tracking-tight text-amber-800">{{ $this->canViewAmounts() ? number_format((float) $this->retro['total'], 2) : '•••' }} {{ $ps->currency }}</span>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    @endif

    {{-- ================= LOANS / ADVANCES ================= --}}
    @if ($activeTab === 'loans')
        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.personnel') }}</label>
            <div class="relative mt-1" x-data="{ open: false }" x-on:click.outside="open = false">
                @if ($selectedTabelNo)
                    <div class="flex items-center justify-between rounded-xl bg-[#f5f5f7] px-4 py-2.5">
                        <span class="text-sm font-semibold text-zinc-900">{{ $selectedPersonnelLabel }}</span>
                        <button type="button" wire:click="clearPersonnel" class="text-xs font-medium text-zinc-500 hover:text-rose-600">{{ __('payroll::dashboard.actions.close') }}</button>
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="personnelSearch" x-on:focus="open = true" placeholder="{{ __('compensation::dashboard.actions.search_personnel') }}" class="{{ $inp }}" />
                    @if (count($this->personnelResults))
                        <div x-show="open" class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-zinc-200 bg-white p-1 shadow-lg">
                            @foreach ($this->personnelResults as $res)
                                <button type="button" wire:click="selectPersonnel(@js($res['tabel_no']), @js($res['label']))" x-on:click="open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-zinc-100">{{ $res['label'] }}</button>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>

        @if ($selectedTabelNo)
            <div class="grid gap-4 xl:grid-cols-2">
                @if ($canManage)
                    <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                        <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('payroll::dashboard.loans.title') }}</h3>
                        <div class="mt-4 grid gap-3">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="{{ $lbl }}">{{ __('payroll::dashboard.fields.loan_type') }}</label>
                                    <select wire:model.live="loanForm.type" class="{{ $inp }}">
                                        <option value="loan">{{ __('payroll::dashboard.loans.types.loan') }}</option>
                                        <option value="advance">{{ __('payroll::dashboard.loans.types.advance') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">{{ __('payroll::dashboard.fields.start_on') }}</label>
                                    <input type="date" wire:model.defer="loanForm.start_on" class="{{ $inp }}" />
                                    @error('loanForm.start_on') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="{{ $lbl }}">{{ __('payroll::dashboard.fields.principal') }}</label>
                                    <input type="number" step="0.01" wire:model.defer="loanForm.principal" class="{{ $inp }}" />
                                    @error('loanForm.principal') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">{{ __('payroll::dashboard.fields.monthly_installment') }}</label>
                                    <input type="number" step="0.01" wire:model.defer="loanForm.monthly_installment" class="{{ $inp }}" />
                                    @error('loanForm.monthly_installment') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="saveLoan" class="{{ $primaryBtn }}">{{ __('compensation::dashboard.actions.save') }}</button>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                    <span class="{{ $lbl }}">{{ __('payroll::dashboard.loans.list') }}</span>
                    <div class="mt-3 space-y-2">
                        @forelse ($this->loans as $loan)
                            <div class="flex items-center justify-between gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold tracking-tight text-zinc-900">{{ __('payroll::dashboard.loans.types.'.$loan->type) }} · {{ $this->canViewAmounts() ? number_format((float) $loan->principal, 2) : '•••' }} {{ $loan->currency }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500">{{ __('payroll::dashboard.fields.remaining') }}: {{ $this->canViewAmounts() ? number_format((float) $loan->remaining, 2) : '•••' }} · {{ __('payroll::dashboard.loans.statuses.'.$loan->status) }}</p>
                                </div>
                                @if ($canManage)
                                    <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('payroll::dashboard.confirm.delete')), confirmText: @js(__('payroll::dashboard.actions.delete')), run: () => $wire.deleteLoan({{ $loan->id }}) })" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                @endif
                            </div>
                        @empty
                            <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.loans.empty')" />
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
