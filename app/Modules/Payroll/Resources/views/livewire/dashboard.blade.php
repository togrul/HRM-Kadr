@php
    $num = fn ($value): string => number_format((float) $value, 0, ',', ' ');

    $canManage = $this->canManage();
    $period = $this->activePeriod;
    $periodLabel = $this->periodLabel($period);
    $counts = $this->tabCounts;
    $exportRunId = $this->exportRunId;

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

        @if ($activeTab === 'runs')
            @livewire('payroll.tabs.runs', [
                'periodFilter' => $periodFilter,
                'regimeFilter' => $regimeFilter,
                'selectedRunId' => $selectedRunId,
                'periodId' => $period?->id,
                'activePeriodLabel' => $periodLabel,
                'periodCurrency' => $period?->currency,
                'activeLoanCount' => $counts['loans'],
            ], key('payroll-tab-runs'))
        @elseif ($activeTab === 'payslips')
            @livewire('payroll.tabs.payslips', ['runId' => $selectedRunId], key('payroll-tab-payslips-'.$selectedRunId))
        @else
            @livewire('payroll.tabs.loans', ['tabelNo' => $loanTabelNo, 'label' => $loanPersonnelLabel], key('payroll-tab-loans-'.$loanTabelNo))
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
                            <x-ui.input type="number" wire:model="periodForm.year" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('payroll::dashboard.fields.month')" :error="$errors->first('periodForm.month')">
                            <x-ui.input type="number" min="1" max="12" wire:model="periodForm.month" />
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
