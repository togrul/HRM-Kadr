@php
    $num = fn ($value): string => number_format((float) $value, 0, ',', ' ');
    $money = fn ($value): string => $this->canViewAmounts() ? number_format((float) $value, 2, ',', ' ') : '•••';

    $canManage = $this->canManage();
    $period = $this->activePeriod;
    $periodLabel = $this->periodLabel($period);
    $counts = $this->tabCounts;
    $exportRunId = $this->exportRunId;

    $chip = 'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium';
    $statusChip = fn (string $status): string => $chip.' '.match ($status) {
        'locked' => 'bg-emerald-50 text-emerald-700',
        'approved' => 'bg-amber-50 text-amber-700',
        'calculated' => 'bg-sky-50 text-sky-700',
        default => 'bg-[#f4f4f5] text-ink-muted',
    };

    $rowButton = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition';
    $delBtn = $rowButton.' hover:bg-rose-50 hover:text-rose-600';
    $delIcon = '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>';
    // @js() cannot be used inside an <x-...> attribute — component attributes compile with
    // escaped quotes, which breaks Blade's directive tokenizer — so build the payload here.
    $confirm = fn (string $tone, string $messageKey, string $actionKey, string $call): string => "\$dispatch('confirm-action', { tone: '{$tone}', message: ".\Illuminate\Support\Js::from(__('payroll::dashboard.confirm.'.$messageKey)).", confirmText: ".\Illuminate\Support\Js::from(__('payroll::dashboard.actions.'.$actionKey)).", run: () => \$wire.{$call} })";
    $confirmDelete = fn (string $call): string => $confirm('rose', 'delete', 'delete', $call);

    $panelTitle = match ($panel) {
        'period' => __('payroll::dashboard.periods.title'),
        'run' => __('payroll::dashboard.runs.new'),
        default => '',
    };

    $exports = [
        ['bank', 'exportBankFile'],
        ['bank_csv', 'exportBankCsv'],
        ['gl', 'exportGl'],
        ['state', 'exportStateReport'],
    ];
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel :title="__('payroll::dashboard.kicker')" :subtitle="$periodLabel">
            <x-context-panel.section>
                @foreach ($this->allowedTabsList as $tab)
                    <x-context-panel.item
                        wire:key="payroll-tab-{{ $tab }}"
                        wire:click.prevent="switchTab('{{ $tab }}')"
                        :active="$activeTab === $tab"
                        :count="isset($counts[$tab]) ? $num($counts[$tab]) : null"
                    >{{ __('payroll::dashboard.tabs.'.$tab) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            <x-context-panel.section :title="__('payroll::dashboard.fields.period')">
                <div class="space-y-2 px-1 py-1">
                    <x-ui.select-dropdown
                        mode="gray"
                        direction="auto"
                        wire:model.live="periodFilter"
                        :model="$this->periodOptions"
                        :placeholder="__('payroll::dashboard.periods.empty')"
                    />
                    <x-ui.select-dropdown
                        mode="gray"
                        direction="auto"
                        wire:model.live="regimeFilter"
                        :model="$this->regimeOptions"
                        :placeholder="__('payroll::dashboard.fields.all_regimes')"
                    />
                </div>
            </x-context-panel.section>

            <x-context-panel.section :padded="false">
                <div class="p-2.5">
                    <x-context-panel.meta :items="collect($this->summaryStats)->map(fn ($stat) => [
                        'label' => __('payroll::dashboard.summary.'.$stat['key']),
                        'value' => $num($stat['value']),
                        'dot' => $stat['accent'],
                    ])->all()" />
                </div>
            </x-context-panel.section>

            @if ($this->canExport() && $exportRunId)
                <x-context-panel.section :title="__('payroll::dashboard.export.title')">
                    @foreach ($exports as [$key, $method])
                        <x-context-panel.item wire:key="payroll-export-{{ $key }}" wire:click="{{ $method }}({{ $exportRunId }})">
                            {{ __('payroll::dashboard.export.actions.'.$key) }}
                        </x-context-panel.item>
                    @endforeach
                </x-context-panel.section>
            @endif
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('payroll::dashboard.title')"
        :breadcrumb="__('payroll::dashboard.kicker')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($this->summaryStats[1]['value'])" :label="__('payroll::dashboard.summary.runs')" tone="violet" />
            <x-page-header.stat :value="$num($this->summaryStats[2]['value'])" :label="__('payroll::dashboard.summary.locked')" tone="green" />
            <x-page-header.stat :value="$num($this->summaryStats[3]['value'])" :label="__('payroll::dashboard.summary.payslips')" />
        </x-slot:stats>

        <x-slot:actions>
            @if ($canManage)
                <x-pill-button variant="secondary" wire:click="openPanel('period')">{{ __('payroll::dashboard.actions.create_period') }}</x-pill-button>
            @endif

            @if ($this->canExport() && $exportRunId)
                <x-pill-button variant="secondary" :icon="true" wire:click="exportBankFile({{ $exportRunId }})" title="{{ __('payroll::dashboard.export.actions.bank') }}">
                    <svg class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 13 6 5M15 13l-6 5"/></svg>
                </x-pill-button>
            @endif

            @if ($canManage)
                <x-pill-button variant="primary" wire:click="openPanel('run')">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('payroll::dashboard.runs.new') }}
                </x-pill-button>
            @endif
        </x-slot:actions>

        <div class="lg:hidden">
            <x-filter.nav>
                @foreach ($this->allowedTabsList as $tab)
                    <x-filter.item wire:click.prevent="switchTab('{{ $tab }}')" :active="$activeTab === $tab">
                        {{ __('payroll::dashboard.tabs.'.$tab) }}
                    </x-filter.item>
                @endforeach
            </x-filter.nav>
        </div>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">

        {{-- ================= RUNS ================= --}}
        @if ($activeTab === 'runs')
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="flex flex-col gap-1 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('payroll::dashboard.runs.title') }}</h2>
                        @if ($this->canViewAmounts())
                            <p class="mt-0.5 text-[11.5px] text-ink-faint">
                                {{ __('payroll::dashboard.runs.forecast') }} <span class="px-0.5">—</span>
                                <span class="hrm-num">{{ $money($this->forecastBaseTotal) }}</span> {{ $period?->currency ?? 'AZN' }}
                            </p>
                        @endif
                    </div>
                    <span class="shrink-0 text-[11.5px] text-ink-faint">{{ $periodLabel }}</span>
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
                                <button type="button" wire:click="selectRun({{ $run->id }})" class="min-w-0 max-w-[220px] text-left">
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
                                    <x-pill-button variant="secondary" wire:click="selectRun({{ $run->id }})">{{ __('payroll::dashboard.actions.view_payslips') }}</x-pill-button>

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
                        <span class="shrink-0 text-[11.5px] text-ink-faint">{{ $periodLabel }}</span>
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
                        <span class="shrink-0 text-[11.5px] text-ink-faint">{{ __('payroll::dashboard.loans.active', ['count' => $num($counts['loans'])]) }}</span>
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
                                    <button type="button" wire:click="manageLoans(@js($loan->tabel_no), @js(trim($loan->personnel?->surname.' '.$loan->personnel?->name)))" class="min-w-0 max-w-[220px] text-left">
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
        @endif

        {{-- ================= PAYSLIPS ================= --}}
        @if ($activeTab === 'payslips')
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
                                <x-pill-button variant="secondary" wire:key="payroll-run-export-{{ $key }}" wire:click="{{ $method }}({{ $run->id }})">{{ __('payroll::dashboard.export.actions.'.$key) }}</x-pill-button>
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
        @endif

        {{-- ================= LOANS / ADVANCES ================= --}}
        @if ($activeTab === 'loans')
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('payroll::dashboard.loans.title') }}</h2>

                    <div class="w-full sm:w-[320px]">
                        @if ($selectedTabelNo)
                            <div class="flex items-center justify-between gap-3 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2">
                                <span class="truncate text-[12.5px] font-semibold text-ink">{{ $selectedPersonnelLabel }}</span>
                                <button type="button" wire:click="clearPersonnel" class="shrink-0 text-[11.5px] font-medium text-ink-faint transition hover:text-rose-600">{{ __('payroll::dashboard.actions.close') }}</button>
                            </div>
                        @else
                            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                                <x-ui.input icon="search" wire:model.live.debounce.300ms="personnelSearch" x-on:focus="open = true" placeholder="{{ __('compensation::dashboard.actions.search_personnel') }}" />
                                @if (count($this->personnelResults))
                                    <div x-show="open" class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-hairline bg-white p-1 shadow-lg">
                                        @foreach ($this->personnelResults as $res)
                                            <button type="button" wire:key="payroll-personnel-{{ $res['tabel_no'] }}" wire:click="selectPersonnel(@js($res['tabel_no']), @js($res['label']))" x-on:click="open = false" class="block w-full rounded-lg px-3 py-2 text-left text-[12.5px] text-ink-soft transition hover:bg-[#f4f4f5] hover:text-ink">{{ $res['label'] }}</button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                @if (! $selectedTabelNo)
                    <p class="px-4 py-6 text-[12.5px] text-ink-faint">{{ __('payroll::dashboard.loans.select_personnel') }}</p>
                @else
                    @if ($canManage)
                        <div class="grid gap-3 border-b border-hairline-subtle px-4 py-4 sm:grid-cols-2 xl:grid-cols-5">
                            <div class="min-w-0">
                                <x-ui.select-dropdown
                                    :label="__('payroll::dashboard.fields.loan_type')"
                                    mode="gray"
                                    direction="auto"
                                    wire:model.live="loanForm.type"
                                    :model="[
                                        ['id' => 'loan', 'label' => __('payroll::dashboard.loans.types.loan')],
                                        ['id' => 'advance', 'label' => __('payroll::dashboard.loans.types.advance')],
                                    ]"
                                />
                            </div>
                            <x-ui.input-shell :label="__('payroll::dashboard.fields.principal')" :error="$errors->first('loanForm.principal')">
                                <x-ui.input type="number" step="0.01" wire:model.defer="loanForm.principal" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('payroll::dashboard.fields.monthly_installment')" :error="$errors->first('loanForm.monthly_installment')">
                                <x-ui.input type="number" step="0.01" wire:model.defer="loanForm.monthly_installment" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('payroll::dashboard.fields.start_on')" :error="$errors->first('loanForm.start_on')">
                                <x-ui.input type="date" wire:model.defer="loanForm.start_on" />
                            </x-ui.input-shell>
                            <div class="flex items-end">
                                <x-pill-button variant="primary" class="w-full justify-center" wire:click="saveLoan">{{ __('payroll::dashboard.actions.save') }}</x-pill-button>
                            </div>
                        </div>
                    @endif

                    <x-table.tbl :headers="[
                        __('payroll::dashboard.fields.loan_type'),
                        __('payroll::dashboard.fields.principal'),
                        __('payroll::dashboard.fields.monthly_installment'),
                        __('payroll::dashboard.fields.remaining'),
                        __('payroll::dashboard.fields.status'),
                        __('payroll::dashboard.columns.actions'),
                    ]">
                        @forelse ($this->loans as $loan)
                            <tr wire:key="payroll-loan-{{ $loan->id }}">
                                <x-table.td standart-width>
                                    <span class="{{ $chip }} {{ $loan->type === 'loan' ? 'bg-sky-50 text-sky-700' : 'bg-[#f4f4f5] text-ink-muted' }}">{{ __('payroll::dashboard.loans.types.'.$loan->type) }}</span>
                                </x-table.td>
                                <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $money($loan->principal) }} {{ $loan->currency }}</span></x-table.td>
                                <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $money($loan->monthly_installment) }}</span></x-table.td>
                                <x-table.td><span class="hrm-num text-[13px] font-semibold text-ink">{{ $money($loan->remaining) }}</span></x-table.td>
                                <x-table.td><span class="text-[13px] text-ink-muted">{{ __('payroll::dashboard.loans.statuses.'.$loan->status) }}</span></x-table.td>
                                <x-table.td :isButton="true">
                                    @if ($canManage)
                                        <button type="button" x-on:click="{{ $confirmDelete('deleteLoan('.$loan->id.')') }}" title="{{ __('payroll::dashboard.actions.delete') }}" class="{{ $delBtn }} ml-auto">{!! $delIcon !!}</button>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4">
                                    <x-ui.empty-state icon="icons.document-icon" :title="__('payroll::dashboard.loans.empty')" />
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                @endif
            </section>
        @endif
    </div>

    {{-- ===================== side panel: period / run ===================== --}}
    @if ($panel)
        <x-ui.side-panel
            title-id="payroll-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('payroll::dashboard.actions.close')"
            width="2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-hairline-subtle px-5 py-4">
                <div class="min-w-0">
                    <p class="hrm-eyebrow">{{ __('payroll::dashboard.kicker') }}</p>
                    <h2 id="payroll-panel-title" class="mt-1.5 text-[17px] font-semibold tracking-[-0.025em] text-ink">{{ $panelTitle }}</h2>
                </div>

                <x-pill-button variant="secondary" x-ref="closeButton" :icon="true" x-on:click="close()" title="{{ __('payroll::dashboard.actions.close') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </x-pill-button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                @if ($panel === 'period')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('payroll::dashboard.fields.year')" :error="$errors->first('periodForm.year')">
                            <x-ui.input type="number" wire:model.defer="periodForm.year" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('payroll::dashboard.fields.month')" :error="$errors->first('periodForm.month')">
                            <x-ui.input type="number" min="1" max="12" wire:model.defer="periodForm.month" />
                        </x-ui.input-shell>
                    </div>

                    <div>
                        <p class="hrm-eyebrow">{{ __('payroll::dashboard.periods.list') }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @forelse ($this->periods as $item)
                                <span wire:key="payroll-period-chip-{{ $item->id }}" class="inline-flex items-center gap-1.5 rounded-full border border-hairline bg-[#fafafa] py-1 pl-3 pr-1.5 text-[12px] font-medium text-ink-soft">
                                    <span class="hrm-num">{{ $item->code }}</span>
                                    @if ($canManage)
                                        <button type="button" x-on:click="{{ $confirmDelete('deletePeriod('.$item->id.')') }}" title="{{ __('payroll::dashboard.actions.delete') }}" class="inline-flex h-5 w-5 items-center justify-center rounded-full text-ink-faint transition hover:bg-rose-100 hover:text-rose-600">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                        </button>
                                    @endif
                                </span>
                            @empty
                                <span class="text-[12.5px] text-ink-faint">{{ __('payroll::dashboard.periods.empty') }}</span>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="min-w-0">
                            <x-ui.select-dropdown
                                :label="__('payroll::dashboard.fields.period')"
                                mode="gray"
                                direction="auto"
                                wire:model.live="runForm.payroll_period_id"
                                :model="$this->periodOptions"
                            />
                            @error('runForm.payroll_period_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="min-w-0">
                            <x-ui.select-dropdown
                                :label="__('payroll::dashboard.fields.regime')"
                                mode="gray"
                                direction="auto"
                                wire:model.live="runForm.regime_id"
                                :model="$this->regimeOptions"
                                :placeholder="__('payroll::dashboard.fields.all_regimes')"
                            />
                            @error('runForm.regime_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="min-w-0 sm:col-span-2">
                            <x-ui.select-dropdown
                                :label="__('payroll::dashboard.runs.type')"
                                mode="gray"
                                direction="auto"
                                wire:model.live="runForm.run_type"
                                :model="[
                                    ['id' => 'regular', 'label' => __('payroll::dashboard.run_types.regular')],
                                    ['id' => 'off_cycle', 'label' => __('payroll::dashboard.run_types.off_cycle')],
                                ]"
                            />
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-hairline-subtle bg-[#fafafa] px-5 py-3">
                <x-pill-button variant="secondary" x-on:click="close()">{{ __('payroll::dashboard.actions.close') }}</x-pill-button>
                <x-pill-button variant="primary" wire:click="{{ $panel === 'period' ? 'createPeriod' : 'createRun' }}">
                    {{ $panel === 'period' ? __('payroll::dashboard.actions.create_period') : __('payroll::dashboard.actions.create_run') }}
                </x-pill-button>
            </div>
        </x-ui.side-panel>
    @endif
</div>
