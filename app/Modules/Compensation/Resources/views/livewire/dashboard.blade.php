@php
    $num = fn ($value): string => number_format((int) $value, 0, ',', ' ');
    $canManage = $this->canManage();
    $stats = collect($this->summaryStats)->keyBy('key');
    $stat = fn (string $key): int => (int) ($stats->get($key)['value'] ?? 0);

    // Bank and history are per-employee screens, so a global count on them would be a lie.
    $tabCounts = ['scales' => $stat('scales'), 'components' => $stat('components'), 'assignments' => $stat('assignments')];

    $addAction = match ($activeTab) {
        'scales' => ['scale', 'add_scale'],
        'components' => ['component', 'add_component'],
        'bank' => $selectedTabelNo ? ['bank', 'add_bank'] : null,
        'statutory' => ['statutory', 'add_rate'],
        default => null,
    };

    $panelTitle = match ($panel) {
        'scale' => __('compensation::dashboard.scales.title'),
        'grade' => __('compensation::dashboard.grades.title'),
        'component' => __('compensation::dashboard.components.title'),
        'bank' => __('compensation::dashboard.bank.title'),
        'statutory' => __('compensation::dashboard.statutory.title'),
        default => '',
    };

    $rowButton = 'flex h-8 w-8 items-center justify-center rounded-lg text-ink-faint transition';
    $editBtn = $rowButton.' hover:bg-[#f4f4f5] hover:text-ink';
    $delBtn = $rowButton.' hover:bg-rose-50 hover:text-rose-600';
    $editIcon = '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>';
    $delIcon = '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>';
    $confirmDelete = fn (string $call): string => "\$dispatch('confirm-action', { tone: 'rose', message: ".\Illuminate\Support\Js::from(__('compensation::dashboard.confirm.delete')).", confirmText: ".\Illuminate\Support\Js::from(__('compensation::dashboard.actions.delete')).", run: () => \$wire.{$call} })";
@endphp

<div class="flex flex-col">
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar"><div id="hrm-context-panel"></div></x-slot>

    @teleport('#hrm-context-panel')
        <x-context-panel
            :title="__('compensation::dashboard.title')"
            :subtitle="__('compensation::dashboard.kicker')"
        >
            <x-context-panel.section>
                @foreach ($this->allowedTabsList as $tab)
                    <x-context-panel.item
                        wire:key="compensation-tab-{{ $tab }}"
                        wire:click.prevent="switchTab('{{ $tab }}')"
                        :active="$activeTab === $tab"
                        :count="isset($tabCounts[$tab]) ? $num($tabCounts[$tab]) : null"
                    >{{ __('compensation::dashboard.tabs.'.$tab) }}</x-context-panel.item>
                @endforeach
            </x-context-panel.section>

            <x-context-panel.section :padded="false">
                <div class="p-2.5">
                    <x-context-panel.meta :items="collect($this->summaryStats)->map(fn ($item) => [
                        'label' => __('compensation::dashboard.summary.'.$item['key']),
                        'value' => $num($item['value']),
                        'dot' => $item['accent'],
                    ])->all()" />
                </div>
            </x-context-panel.section>
        </x-context-panel>
    @endteleport

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('compensation::dashboard.title')"
        :breadcrumb="__('compensation::dashboard.kicker')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
        </x-slot:icon>

        <x-slot:stats>
            <x-page-header.stat :value="$num($stat('scales'))" :label="__('compensation::dashboard.summary.scales')" />
            <x-page-header.stat :value="$num($stat('grades'))" :label="__('compensation::dashboard.summary.grades')" tone="violet" />
            <x-page-header.stat :value="$num($stat('assignments'))" :label="__('compensation::dashboard.summary.assignments')" tone="green" />
        </x-slot:stats>

        <x-slot:actions>
            <x-pill-button wire:click="switchTab('components')">
                {{ __('compensation::dashboard.actions.open_catalog') }}
            </x-pill-button>

            @if ($canManage && $addAction)
                <x-pill-button variant="primary" wire:click="openPanel('{{ $addAction[0] }}')">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('compensation::dashboard.actions.'.$addAction[1]) }}
                </x-pill-button>
            @endif
        </x-slot:actions>

        <div class="lg:hidden">
            <x-filter.nav>
                @foreach ($this->allowedTabsList as $tab)
                    <x-filter.item wire:click.prevent="switchTab('{{ $tab }}')" :active="$activeTab === $tab">
                        {{ __('compensation::dashboard.tabs.'.$tab) }}
                    </x-filter.item>
                @endforeach
            </x-filter.nav>
        </div>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">

        {{-- ================= SCALES ================= --}}
        @if ($activeTab === 'scales')
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.scales.title') }}</h2>
                        <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __('compensation::dashboard.scales.subtitle') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-full sm:w-[220px]">
                            <x-ui.input icon="search" wire:model.live.debounce.300ms="scaleSearch" placeholder="{{ __('compensation::dashboard.actions.search') }}" />
                        </div>
                        <p class="hrm-num hidden shrink-0 text-[11.5px] text-ink-faint sm:block">
                            {{ __('compensation::dashboard.scales.meta', ['scales' => $num($stat('scales')), 'grades' => $num($stat('grades'))]) }}
                        </p>
                    </div>
                </div>

                <x-table.tbl :headers="[
                    __('compensation::dashboard.columns.scale'),
                    __('compensation::dashboard.columns.grade_range'),
                    __('compensation::dashboard.columns.min'),
                    __('compensation::dashboard.columns.midpoint'),
                    __('compensation::dashboard.columns.max'),
                    __('compensation::dashboard.fields.effective_from'),
                    __('compensation::dashboard.columns.actions'),
                ]">
                    @forelse ($this->scales as $scale)
                        @php $band = $this->scaleRange($scale); @endphp
                        <tr wire:key="compensation-scale-{{ $scale->id }}" @class(['bg-[#fafafa]' => $selectedScaleId === $scale->id])>
                            <x-table.td standart-width>
                                <button type="button" wire:click="selectScale({{ $scale->id }})" class="min-w-0 max-w-[260px] text-left">
                                    <p class="truncate text-[13px] font-medium text-ink">{{ $scale->name }}</p>
                                    <p class="truncate text-[11px] text-ink-faint">{{ $scale->regime?->name }} <span class="px-0.5">·</span> {{ $scale->currency }}</p>
                                </button>
                            </x-table.td>

                            <x-table.td>
                                <span class="hrm-num text-[13px] text-ink-soft">{{ $band['range'] }}</span>
                                <p class="hrm-num text-[11px] text-ink-faint">{{ $band['grades'] }} {{ __('compensation::dashboard.grades.label') }}</p>
                            </x-table.td>

                            <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $band['min'] }}</span></x-table.td>
                            <x-table.td><span class="hrm-num text-[13px] font-semibold text-ink">{{ $band['midpoint'] }}</span></x-table.td>
                            <x-table.td><span class="hrm-num text-[13px] text-ink-soft">{{ $band['max'] }}</span></x-table.td>

                            <x-table.td>
                                <span class="hrm-num text-[13px] text-ink-muted">{{ optional($scale->effective_from)->format('d.m.Y') ?? '—' }}</span>
                            </x-table.td>

                            <x-table.td :isButton="true">
                                @if ($canManage)
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" wire:click="editScale({{ $scale->id }})" title="{{ __('compensation::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                        <button type="button" x-on:click="{{ $confirmDelete('deleteScale('.$scale->id.')') }}" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                    </div>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10">
                                <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.scales.empty')" />
                            </td>
                        </tr>
                    @endforelse
                </x-table.tbl>

                <x-pagination :paginator="$this->scales" :unit="__('compensation::dashboard.summary.scales')" />
            </section>

            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.grades.title') }}</h2>
                    @if ($canManage && $selectedScaleId)
                        <x-pill-button wire:click="openPanel('grade')">{{ __('compensation::dashboard.actions.add_grade') }}</x-pill-button>
                    @endif
                </div>

                @if (! $selectedScaleId)
                    <p class="px-4 py-6 text-[12.5px] text-ink-faint">{{ __('compensation::dashboard.grades.select_scale') }}</p>
                @else
                    <x-table.tbl :headers="[
                        __('compensation::dashboard.columns.grade'),
                        __('compensation::dashboard.columns.amount'),
                        __('compensation::dashboard.columns.position'),
                        __('compensation::dashboard.columns.actions'),
                    ]">
                        @forelse ($this->grades as $grade)
                            <tr wire:key="compensation-grade-{{ $grade->id }}">
                                <x-table.td standart-width>
                                    <p class="max-w-[280px] truncate text-[13px] font-medium text-ink">
                                        <span class="hrm-num">{{ $grade->code }}</span> <span class="px-0.5 text-ink-faint">—</span> {{ $grade->name }}
                                    </p>
                                </x-table.td>
                                <x-table.td>
                                    <span class="hrm-num text-[13px] text-ink-soft">{{ $this->canViewAmounts() ? number_format((float) $grade->base_amount, 2) : '•••' }}</span>
                                </x-table.td>
                                <x-table.td>
                                    <span class="text-[13px] text-ink-muted">{{ $grade->position?->name ?? '—' }}</span>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    @if ($canManage)
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" wire:click="editGrade({{ $grade->id }})" title="{{ __('compensation::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                            <button type="button" x-on:click="{{ $confirmDelete('deleteGrade('.$grade->id.')') }}" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                        </div>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10">
                                    <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.grades.empty')" />
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                @endif
            </section>
        @endif

        {{-- ================= COMPONENTS ================= --}}
        @if ($activeTab === 'components')
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.components.title') }}</h2>
                    <div class="w-full sm:max-w-[280px]">
                        <x-ui.input icon="search" wire:model.live.debounce.300ms="componentSearch" placeholder="{{ __('compensation::dashboard.actions.search') }}" />
                    </div>
                </div>

                <x-table.tbl :headers="[
                    __('compensation::dashboard.columns.component'),
                    __('compensation::dashboard.columns.type'),
                    __('compensation::dashboard.columns.calc_type'),
                    __('compensation::dashboard.columns.flags'),
                    __('compensation::dashboard.columns.actions'),
                ]">
                    @forelse ($this->components as $component)
                        <tr wire:key="compensation-component-{{ $component->id }}">
                            <x-table.td standart-width>
                                <p class="max-w-[260px] truncate text-[13px] font-medium text-ink">{{ $component->name }}</p>
                                <p class="hrm-num truncate text-[11px] uppercase tracking-[0.04em] text-ink-faint">{{ $component->code }}</p>
                            </x-table.td>
                            <x-table.td>
                                <x-small-badge :mode="$component->type === 'earning' ? 'green' : 'rose'" dot>
                                    {{ __('compensation::dashboard.types.'.$component->type) }}
                                </x-small-badge>
                            </x-table.td>
                            <x-table.td>
                                <span class="text-[13px] text-ink-soft">{{ __('compensation::dashboard.calc_types.'.$component->calc_type) }}</span>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if ($component->is_statutory)
                                        <x-small-badge mode="sky">{{ __('compensation::dashboard.fields.is_statutory') }}</x-small-badge>
                                    @endif
                                    @if ($component->taxable)
                                        <x-small-badge mode="secondary">{{ __('compensation::dashboard.fields.taxable') }}</x-small-badge>
                                    @endif
                                    @if ($component->affects_social)
                                        <x-small-badge mode="secondary">{{ __('compensation::dashboard.fields.affects_social') }}</x-small-badge>
                                    @endif
                                </div>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                @if ($canManage)
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" wire:click="editComponent({{ $component->id }})" title="{{ __('compensation::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                        <button type="button" x-on:click="{{ $confirmDelete('deleteComponent('.$component->id.')') }}" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                    </div>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10">
                                <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.components.empty')" />
                            </td>
                        </tr>
                    @endforelse
                </x-table.tbl>

                <x-pagination :paginator="$this->components" :unit="__('compensation::dashboard.summary.components')" />
            </section>
        @endif

        {{-- ========= ASSIGNMENTS / BANK / HISTORY share the personnel picker ========= --}}
        @if (in_array($activeTab, ['assignments', 'bank', 'history'], true))
            <section class="rounded-xl border border-hairline bg-white px-4 py-3.5">
                <p class="hrm-eyebrow">{{ __('compensation::dashboard.fields.personnel') }}</p>
                <div class="relative mt-2 max-w-xl" x-data="{ open: false }" x-on:click.outside="open = false">
                    @if ($selectedTabelNo)
                        <div class="flex items-center justify-between gap-3 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 py-2">
                            <span class="min-w-0 truncate text-[13px] font-medium text-ink">{{ $selectedPersonnelLabel }}</span>
                            <button type="button" wire:click="clearPersonnel" class="shrink-0 text-[11.5px] font-medium text-ink-faint transition hover:text-rose-600">
                                {{ __('compensation::dashboard.actions.clear') }}
                            </button>
                        </div>
                    @else
                        <x-ui.input
                            icon="search"
                            wire:model.live.debounce.300ms="personnelSearch"
                            x-on:focus="open = true"
                            placeholder="{{ __('compensation::dashboard.actions.search_personnel') }}"
                        />
                        @if (count($this->personnelResults))
                            <div x-show="open" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-xl border border-hairline bg-white p-1 shadow-card">
                                @foreach ($this->personnelResults as $result)
                                    <button
                                        type="button"
                                        wire:key="compensation-personnel-{{ $result['tabel_no'] }}"
                                        wire:click="selectPersonnel({{ \Illuminate\Support\Js::from($result['tabel_no']) }}, {{ \Illuminate\Support\Js::from($result['label']) }})"
                                        x-on:click="open = false"
                                        class="block w-full rounded-lg px-3 py-2 text-left text-[12.5px] text-ink-soft transition hover:bg-[#fafafa] hover:text-ink"
                                    >{{ $result['label'] }}</button>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </section>
        @endif

        {{-- ================= ASSIGNMENTS ================= --}}
        @if ($activeTab === 'assignments' && $selectedTabelNo)
            @php $current = $this->currentAssignment; @endphp

            @if ($current)
                <section class="rounded-xl border border-hairline bg-white px-4 py-3.5">
                    <p class="hrm-eyebrow">{{ __('compensation::dashboard.assignments.current') }}</p>
                    <p class="hrm-num mt-1.5 text-[26px] font-semibold leading-none tracking-[-0.035em] text-ink">
                        {{ $current->maskedBaseAmount() }}<span class="ml-1.5 text-[13px] font-medium text-ink-faint">{{ $current->currency }}</span>
                    </p>
                    <p class="hrm-num mt-1.5 text-[11.5px] text-ink-faint">
                        {{ __('compensation::dashboard.fields.effective_from') }}: {{ optional($current->effective_from)->format('d.m.Y') }}
                    </p>
                </section>
            @endif

            @if ($canManage)
                <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                    <div class="border-b border-hairline-subtle px-4 py-3">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.assignments.title') }}</h2>
                    </div>

                    <div class="space-y-4 px-4 py-3.5">
                        <div class="grid gap-3 lg:grid-cols-3">
                            <div class="min-w-0">
                                <x-ui.select-dropdown :label="__('compensation::dashboard.fields.regime')" mode="gray" direction="auto" wire:model.live="assignmentForm.regime_id" :model="$this->regimeOptions" />
                                @error('assignmentForm.regime_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <x-ui.input-shell :label="__('compensation::dashboard.fields.base_amount')" :error="$errors->first('assignmentForm.base_amount')">
                                <x-ui.input type="number" step="0.01" wire:model.defer="assignmentForm.base_amount" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('compensation::dashboard.fields.effective_from')" :error="$errors->first('assignmentForm.effective_from')">
                                <x-ui.input type="date" wire:model.defer="assignmentForm.effective_from" />
                            </x-ui.input-shell>
                            <x-ui.input-shell :label="__('compensation::dashboard.fields.order_no')">
                                <x-ui.input wire:model.defer="assignmentForm.order_no" />
                            </x-ui.input-shell>
                            <x-ui.input-shell class="lg:col-span-2" :label="__('compensation::dashboard.fields.note')">
                                <x-ui.input wire:model.defer="assignmentForm.note" />
                            </x-ui.input-shell>
                        </div>

                        <div class="border-t border-hairline-subtle pt-3.5">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <p class="hrm-eyebrow">{{ __('compensation::dashboard.assignments.lines') }}</p>
                                <x-pill-button wire:click="addAssignmentLine">{{ __('compensation::dashboard.actions.add_line') }}</x-pill-button>
                            </div>

                            <div class="space-y-2">
                                @foreach ($assignmentLines as $i => $line)
                                    <div wire:key="compensation-line-{{ $i }}" class="grid items-end gap-2 rounded-xl border border-hairline bg-[#fafafa] p-3 sm:grid-cols-12">
                                        <div class="min-w-0 sm:col-span-5">
                                            <x-ui.select-dropdown :label="__('compensation::dashboard.fields.component')" mode="gray" direction="auto" wire:model.live="assignmentLines.{{ $i }}.component_id" :model="$this->componentOptions" :instance="'line-'.$i" />
                                        </div>
                                        <x-ui.input-shell class="min-w-0 sm:col-span-3" :label="__('compensation::dashboard.fields.amount')">
                                            <x-ui.input type="number" step="0.01" wire:model.defer="assignmentLines.{{ $i }}.amount" />
                                        </x-ui.input-shell>
                                        <x-ui.input-shell class="min-w-0 sm:col-span-3" :label="__('compensation::dashboard.fields.percent')">
                                            <x-ui.input type="number" step="0.01" wire:model.defer="assignmentLines.{{ $i }}.percent" />
                                        </x-ui.input-shell>
                                        <div class="flex justify-end sm:col-span-1">
                                            <button type="button" wire:click="removeAssignmentLine({{ $i }})" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <x-pill-button variant="primary" wire:click="saveAssignment">{{ __('compensation::dashboard.actions.assign') }}</x-pill-button>
                        </div>
                    </div>
                </section>
            @endif
        @endif

        {{-- ================= BANK ================= --}}
        @if ($activeTab === 'bank' && $selectedTabelNo)
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.bank.list') }}</h2>
                </div>
                <div class="divide-y divide-hairline-subtle">
                    @forelse ($this->bankAccounts as $account)
                        <div wire:key="compensation-bank-{{ $account->id }}" class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <p class="hrm-num truncate text-[13px] font-medium text-ink">{{ $account->iban }}</p>
                                <p class="truncate text-[11.5px] text-ink-faint">{{ $account->bank_name }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                @if ($account->is_primary)
                                    <x-small-badge mode="green" dot>{{ __('compensation::dashboard.fields.is_primary') }}</x-small-badge>
                                @endif
                                @if ($canManage)
                                    <button type="button" wire:click="editBank({{ $account->id }})" title="{{ __('compensation::dashboard.actions.edit') }}" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                    <button type="button" x-on:click="{{ $confirmDelete('deleteBank('.$account->id.')') }}" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8">
                            <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.bank.empty')" />
                        </div>
                    @endforelse
                </div>
            </section>
        @endif

        {{-- ================= HISTORY ================= --}}
        @if ($activeTab === 'history' && $selectedTabelNo)
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.history.title') }}</h2>
                </div>
                <div class="divide-y divide-hairline-subtle">
                    @forelse ($this->history as $row)
                        <div wire:key="compensation-history-{{ $row->id }}" class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <p class="hrm-num truncate text-[13px] font-medium text-ink">{{ $row->maskedBaseAmount() }} {{ $row->currency }}</p>
                                <p class="hrm-num truncate text-[11.5px] text-ink-faint">
                                    {{ $row->regime?->name }} <span class="px-0.5">·</span>
                                    {{ optional($row->effective_from)->format('d.m.Y') }} — {{ $row->effective_to ? optional($row->effective_to)->format('d.m.Y') : __('compensation::dashboard.history.ongoing') }}
                                </p>
                            </div>
                            <x-small-badge :mode="$row->status === 'active' ? 'green' : 'secondary'" dot>
                                {{ __('compensation::dashboard.status.'.$row->status) }}
                            </x-small-badge>
                        </div>
                    @empty
                        <div class="px-4 py-8">
                            <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.history.empty')" />
                        </div>
                    @endforelse
                </div>
            </section>
        @endif

        {{-- ================= STATUTORY RATES ================= --}}
        @if ($activeTab === 'statutory')
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.tabs.statutory') }}</h2>
                    <p class="text-[11.5px] text-ink-faint">{{ __('compensation::dashboard.statutory.default_regime') }}</p>
                </div>

                @if ($this->statutoryRates->isEmpty())
                    <div class="px-4 py-8">
                        <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.statutory.empty')" />
                    </div>
                @else
                    <div class="grid gap-3 px-4 py-3.5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($this->statutoryRates as $rate)
                            @php $brackets = collect($rate->brackets); @endphp
                            <div wire:key="compensation-rate-{{ $rate->id }}" class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3.5">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="min-w-0 truncate text-[13px] font-medium text-ink">{{ __('compensation::dashboard.statutory.components.'.$rate->component_code) }}</p>
                                    @if ($canManage)
                                        <button type="button" x-on:click="{{ $confirmDelete('deleteStatutoryRate('.$rate->id.')') }}" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }} -mt-1 -mr-1.5">{!! $delIcon !!}</button>
                                    @endif
                                </div>

                                <p class="mt-2 flex items-baseline gap-1.5">
                                    <span class="hrm-num text-[22px] font-semibold leading-none tracking-[-0.035em] text-ink">{{ rtrim(rtrim(number_format((float) $brackets->max('rate'), 2, ',', ' '), '0'), ',') }}%</span>
                                    <span class="text-[11.5px] text-ink-faint">{{ __('compensation::dashboard.statutory.payers.'.$rate->payer) }}</span>
                                    @if ($brackets->count() > 1)
                                        <span class="text-[11px] text-ink-faint">{{ __('compensation::dashboard.statutory.top_rate') }}</span>
                                    @endif
                                </p>

                                <p class="mt-1.5 text-[11px] text-ink-faint">
                                    {{ __('compensation::dashboard.statutory.bases.'.$rate->base) }}
                                    <span class="px-0.5">·</span>
                                    {{ __('compensation::dashboard.statutory.bracket_count', ['count' => $brackets->count()]) }}
                                </p>

                                <p class="mt-1 truncate text-[11px] text-ink-faint">{{ $rate->regime?->name ?? __('compensation::dashboard.statutory.default_regime') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>

    {{-- ===================== editor side panel ===================== --}}
    @if ($canManage && $panel !== '')
        <x-ui.side-panel
            title-id="compensation-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('compensation::dashboard.actions.close')"
            width="3xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-hairline-subtle px-5 py-4">
                <div class="min-w-0">
                    <p class="hrm-eyebrow">{{ __('compensation::dashboard.kicker') }}</p>
                    <h2 id="compensation-panel-title" class="mt-1.5 text-[17px] font-semibold tracking-[-0.025em] text-ink">{{ $panelTitle }}</h2>
                </div>

                <x-pill-button x-ref="closeButton" :icon="true" x-on:click="close()" title="{{ __('compensation::dashboard.actions.close') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </x-pill-button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                @if ($panel === 'scale')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::dashboard.fields.name')" :error="$errors->first('scaleForm.name')">
                            <x-ui.input wire:model.defer="scaleForm.name" />
                        </x-ui.input-shell>
                        <div class="min-w-0">
                            <x-ui.select-dropdown :label="__('compensation::dashboard.fields.regime')" mode="gray" direction="auto" wire:model.live="scaleForm.regime_id" :model="$this->regimeOptions" />
                            @error('scaleForm.regime_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.currency')">
                            <x-ui.input maxlength="3" class="uppercase" wire:model.defer="scaleForm.currency" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.effective_from')" :error="$errors->first('scaleForm.effective_from')">
                            <x-ui.input type="date" wire:model.defer="scaleForm.effective_from" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.effective_to')" :error="$errors->first('scaleForm.effective_to')">
                            <x-ui.input type="date" wire:model.defer="scaleForm.effective_to" />
                        </x-ui.input-shell>
                        <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::dashboard.fields.description')" :error="$errors->first('scaleForm.description')">
                            <x-ui.textarea wire:model.defer="scaleForm.description" rows="2" />
                        </x-ui.input-shell>
                    </div>
                @elseif ($panel === 'grade')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.code')" :error="$errors->first('gradeForm.code')">
                            <x-ui.input wire:model.defer="gradeForm.code" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.base_amount')" :error="$errors->first('gradeForm.base_amount')">
                            <x-ui.input type="number" step="0.01" wire:model.defer="gradeForm.base_amount" />
                        </x-ui.input-shell>
                        <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::dashboard.fields.name')" :error="$errors->first('gradeForm.name')">
                            <x-ui.input wire:model.defer="gradeForm.name" />
                        </x-ui.input-shell>
                        <div class="min-w-0">
                            <x-ui.select-dropdown :label="__('compensation::dashboard.fields.rank_category')" mode="gray" direction="auto" wire:model.live="gradeForm.rank_category_id" :model="$this->rankCategoryOptions" search-model="searchRankCategory" />
                        </div>
                        <div class="min-w-0">
                            <x-ui.select-dropdown :label="__('compensation::dashboard.fields.position')" mode="gray" direction="auto" wire:model.live="gradeForm.position_id" :model="$this->positionOptions" search-model="searchPosition" />
                        </div>
                    </div>
                @elseif ($panel === 'component')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.code')" :error="$errors->first('componentForm.code')">
                            <x-ui.input wire:model.defer="componentForm.code" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.name')" :error="$errors->first('componentForm.name')">
                            <x-ui.input wire:model.defer="componentForm.name" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.type')">
                            <x-ui.select wire:model.live="componentForm.type">
                                <option value="earning">{{ __('compensation::dashboard.types.earning') }}</option>
                                <option value="deduction">{{ __('compensation::dashboard.types.deduction') }}</option>
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.calc_type')">
                            <x-ui.select wire:model.live="componentForm.calc_type">
                                @foreach (['fixed', 'percent', 'formula', 'per_diem', 'rate'] as $calcType)
                                    <option value="{{ $calcType }}">{{ __('compensation::dashboard.calc_types.'.$calcType) }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.gl_code')">
                            <x-ui.input wire:model.defer="componentForm.gl_code" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.sort')">
                            <x-ui.input type="number" wire:model.defer="componentForm.sort" />
                        </x-ui.input-shell>
                        <div class="flex flex-wrap gap-4 pt-1 sm:col-span-2">
                            @foreach (['taxable', 'affects_social', 'is_statutory', 'is_active'] as $flag)
                                <label class="inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted">
                                    <input type="checkbox" wire:model.defer="componentForm.{{ $flag }}" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" />
                                    {{ __('compensation::dashboard.fields.'.$flag) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @elseif ($panel === 'bank')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::dashboard.fields.iban')" :error="$errors->first('bankForm.iban')">
                            <x-ui.input class="uppercase" wire:model.defer="bankForm.iban" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.bank_name')">
                            <x-ui.input wire:model.defer="bankForm.bank_name" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.account_no')">
                            <x-ui.input wire:model.defer="bankForm.account_no" />
                        </x-ui.input-shell>
                        <div class="flex flex-wrap gap-4 pt-1 sm:col-span-2">
                            @foreach (['is_primary', 'is_active'] as $flag)
                                <label class="inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted">
                                    <input type="checkbox" wire:model.defer="bankForm.{{ $flag }}" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" />
                                    {{ __('compensation::dashboard.fields.'.$flag) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.regime')">
                            <x-ui.select wire:model.live="statutoryForm.regime_id">
                                <option value="">{{ __('compensation::dashboard.statutory.default_regime') }}</option>
                                @foreach ($this->regimeOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.statutory.component')">
                            <x-ui.select wire:model.live="statutoryForm.component_code">
                                @foreach (['income_tax', 'dsmf', 'unemployment', 'medical'] as $code)
                                    <option value="{{ $code }}">{{ __('compensation::dashboard.statutory.components.'.$code) }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.statutory.payer')">
                            <x-ui.select wire:model.live="statutoryForm.payer">
                                <option value="ee">{{ __('compensation::dashboard.statutory.payers.ee') }}</option>
                                <option value="er">{{ __('compensation::dashboard.statutory.payers.er') }}</option>
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.statutory.base')">
                            <x-ui.select wire:model.live="statutoryForm.base">
                                <option value="social">{{ __('compensation::dashboard.statutory.bases.social') }}</option>
                                <option value="taxable">{{ __('compensation::dashboard.statutory.bases.taxable') }}</option>
                            </x-ui.select>
                        </x-ui.input-shell>
                        <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::dashboard.fields.effective_from')" :error="$errors->first('statutoryForm.effective_from')">
                            <x-ui.input type="date" wire:model.defer="statutoryForm.effective_from" />
                        </x-ui.input-shell>
                    </div>

                    <div class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3.5">
                        <div class="flex items-center justify-between gap-3">
                            <p class="hrm-eyebrow">{{ __('compensation::dashboard.statutory.brackets') }}</p>
                            <x-pill-button wire:click="addStatutoryBracket">{{ __('compensation::dashboard.statutory.add_bracket') }}</x-pill-button>
                        </div>
                        @error('statutoryBrackets') <x-validation>{{ $message }}</x-validation> @enderror

                        <div class="mt-3 space-y-2">
                            @foreach ($statutoryBrackets as $i => $bracket)
                                <div wire:key="compensation-bracket-{{ $i }}" class="grid items-end gap-2 rounded-xl border border-hairline bg-white p-3 sm:grid-cols-12">
                                    <x-ui.input-shell class="min-w-0 sm:col-span-5" :label="__('compensation::dashboard.statutory.up_to')">
                                        <x-ui.input type="number" step="0.01" wire:model.defer="statutoryBrackets.{{ $i }}.up_to" />
                                    </x-ui.input-shell>
                                    <x-ui.input-shell class="min-w-0 sm:col-span-5" :label="__('compensation::dashboard.statutory.rate')" :error="$errors->first('statutoryBrackets.'.$i.'.rate')">
                                        <x-ui.input type="number" step="0.01" wire:model.defer="statutoryBrackets.{{ $i }}.rate" />
                                    </x-ui.input-shell>
                                    <div class="flex justify-end sm:col-span-2">
                                        <button type="button" wire:click="removeStatutoryBracket({{ $i }})" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-hairline-subtle bg-white px-5 py-3.5">
                <x-pill-button x-on:click="close()">{{ __('compensation::dashboard.actions.cancel') }}</x-pill-button>
                <x-pill-button variant="primary" wire:click="{{ match ($panel) {
                    'scale' => 'saveScale',
                    'grade' => 'saveGrade',
                    'component' => 'saveComponent',
                    'bank' => 'saveBank',
                    default => 'saveStatutoryRate',
                } }}">{{ __('compensation::dashboard.actions.save') }}</x-pill-button>
            </div>
        </x-ui.side-panel>
    @endif
</div>
