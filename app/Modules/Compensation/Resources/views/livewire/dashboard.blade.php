@php
    $inp = 'w-full rounded-xl border-0 bg-[#f5f5f7] px-4 py-2.5 text-sm font-medium text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)] outline-none ring-0 transition focus:bg-white focus:ring-2 focus:ring-zinc-200';
    $lbl = 'text-[11px] font-semibold uppercase tracking-wider text-zinc-500';
    $primaryBtn = 'inline-flex items-center justify-center rounded-xl bg-zinc-950 px-5 py-2.5 text-sm font-semibold tracking-tight text-white shadow-sm transition hover:bg-zinc-800';
    $ghostBtn = 'inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#f5f5f7] px-4 py-2.5 text-sm font-semibold tracking-tight text-zinc-700 shadow-[inset_0_1px_0_rgba(255,255,255,0.85)] transition hover:bg-zinc-200/70 hover:text-zinc-900 active:scale-[0.98]';
    $editBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-[11px] text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-900';
    $delBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-[11px] text-zinc-400 transition hover:bg-rose-50 hover:text-rose-600';
    $editIcon = '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>';
    $delIcon = '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>';
    $canManage = $this->canManage();
@endphp

<div class="space-y-6 px-4 py-6 sm:px-6">
    <div class="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-zinc-900 to-zinc-700 text-white shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 7h18a1 1 0 011 1v9a1 1 0 01-1 1H3a1 1 0 01-1-1V8a1 1 0 011-1zm0 0V6a2 2 0 012-2h11M16 13h2" />
                </svg>
            </span>
            <div class="space-y-1">
                <span class="{{ $lbl }}">{{ __('compensation::dashboard.kicker') }}</span>
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-950">{{ __('compensation::dashboard.title') }}</h2>
                <p class="max-w-3xl text-sm leading-6 text-zinc-500">{{ __('compensation::dashboard.description') }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->summaryStats as $stat)
                <div class="group rounded-2xl border border-zinc-200 bg-zinc-50/60 px-5 py-4 transition hover:border-zinc-300 hover:bg-white hover:shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="{{ $lbl }}">{{ __('compensation::dashboard.summary.'.$stat['key']) }}</span>
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
                        {{ __('compensation::dashboard.tabs.'.$tab) }}
                    </x-filter.item>
                @endforeach
            </x-filter.nav>
        </div>
    </div>

    {{-- ================= SCALES ================= --}}
    @if ($activeTab === 'scales')
        <div class="grid gap-4 xl:grid-cols-2">
            {{-- Scale form + list --}}
            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('compensation::dashboard.scales.title') }}</h3>

                @if ($canManage)
                    <div class="mt-4 grid gap-3">
                        <div>
                            <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.name') }}</label>
                            <input type="text" wire:model.defer="scaleForm.name" class="{{ $inp }}" />
                            @error('scaleForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <x-ui.select-dropdown :label="__('compensation::dashboard.fields.regime')" mode="gray" direction="auto" wire:model.live="scaleForm.regime_id" :model="$this->regimeOptions" />
                                @error('scaleForm.regime_id') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.currency') }}</label>
                                <input type="text" maxlength="3" wire:model.defer="scaleForm.currency" class="{{ $inp }} uppercase" />
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.effective_from') }}</label>
                                <input type="date" wire:model.defer="scaleForm.effective_from" class="{{ $inp }}" />
                                @error('scaleForm.effective_from') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.effective_to') }}</label>
                                <input type="date" wire:model.defer="scaleForm.effective_to" class="{{ $inp }}" />
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="saveScale" class="{{ $primaryBtn }}">{{ __('compensation::dashboard.actions.save') }}</button>
                            @if ($editingScaleId)
                                <button type="button" wire:click="cancelScale" class="{{ $ghostBtn }}">{{ __('compensation::dashboard.actions.cancel') }}</button>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="mt-5 border-t border-zinc-100 pt-4">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <span class="{{ $lbl }}">{{ __('compensation::dashboard.scales.list') }} ({{ $this->scales->total() }})</span>
                        <input type="search" wire:model.live.debounce.300ms="scaleSearch" placeholder="{{ __('compensation::dashboard.actions.search') }}" class="h-9 w-40 rounded-xl border border-zinc-200 bg-white px-3 text-xs outline-none focus:ring-2 focus:ring-zinc-200" />
                    </div>
                    <div class="space-y-2">
                        @forelse ($this->scales as $scale)
                            <div @class(['flex items-center justify-between gap-2 rounded-2xl border px-4 py-3', 'border-zinc-200 bg-white' => $selectedScaleId !== $scale->id, 'border-sky-200 bg-sky-50/60' => $selectedScaleId === $scale->id])>
                                <button type="button" wire:click="selectScale({{ $scale->id }})" class="min-w-0 flex-1 text-left">
                                    <p class="truncate text-sm font-semibold text-zinc-900">{{ $scale->name }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500">{{ $scale->regime?->name }} · {{ $scale->currency }} · {{ $scale->grades_count }} {{ __('compensation::dashboard.grades.label') }}</p>
                                </button>
                                @if ($canManage)
                                    <div class="flex shrink-0 items-center gap-1">
                                        <button type="button" wire:click="editScale({{ $scale->id }})" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                        <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('compensation::dashboard.confirm.delete')), confirmText: @js(__('compensation::dashboard.actions.delete')), run: () => $wire.deleteScale({{ $scale->id }}) })" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.scales.empty')" />
                        @endforelse
                    </div>
                    <div class="mt-3">{{ $this->scales->links() }}</div>
                </div>
            </div>

            {{-- Grades of selected scale --}}
            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('compensation::dashboard.grades.title') }}</h3>

                @if (! $selectedScaleId)
                    <p class="mt-4 text-sm text-zinc-500">{{ __('compensation::dashboard.grades.select_scale') }}</p>
                @else
                    @if ($canManage)
                        <div class="mt-4 grid gap-3">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.code') }}</label>
                                    <input type="text" wire:model.defer="gradeForm.code" class="{{ $inp }}" />
                                    @error('gradeForm.code') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                                <div>
                                    <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.base_amount') }}</label>
                                    <input type="number" step="0.01" wire:model.defer="gradeForm.base_amount" class="{{ $inp }}" />
                                    @error('gradeForm.base_amount') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.name') }}</label>
                                <input type="text" wire:model.defer="gradeForm.name" class="{{ $inp }}" />
                                @error('gradeForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-ui.select-dropdown :label="__('compensation::dashboard.fields.rank_category')" mode="gray" direction="auto" wire:model.live="gradeForm.rank_category_id" :model="$this->rankCategoryOptions" search-model="searchRankCategory" />
                                <x-ui.select-dropdown :label="__('compensation::dashboard.fields.position')" mode="gray" direction="auto" wire:model.live="gradeForm.position_id" :model="$this->positionOptions" search-model="searchPosition" />
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="saveGrade" class="{{ $primaryBtn }}">{{ __('compensation::dashboard.actions.save') }}</button>
                                @if ($editingGradeId)
                                    <button type="button" wire:click="cancelGrade" class="{{ $ghostBtn }}">{{ __('compensation::dashboard.actions.cancel') }}</button>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="mt-5 space-y-2 border-t border-zinc-100 pt-4">
                        @forelse ($this->grades as $grade)
                            <div class="flex items-center justify-between gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-zinc-900">{{ $grade->code }} — {{ $grade->name }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500">{{ $this->canViewAmounts() ? number_format((float) $grade->base_amount, 2) : '•••' }}{{ $grade->position ? ' · '.$grade->position->name : '' }}</p>
                                </div>
                                @if ($canManage)
                                    <div class="flex shrink-0 items-center gap-1">
                                        <button type="button" wire:click="editGrade({{ $grade->id }})" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                        <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('compensation::dashboard.confirm.delete')), confirmText: @js(__('compensation::dashboard.actions.delete')), run: () => $wire.deleteGrade({{ $grade->id }}) })" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.grades.empty')" />
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ================= COMPONENTS ================= --}}
    @if ($activeTab === 'components')
        <div class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('compensation::dashboard.components.title') }}</h3>
                @if ($canManage)
                    <div class="mt-4 grid gap-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.code') }}</label>
                                <input type="text" wire:model.defer="componentForm.code" class="{{ $inp }}" />
                                @error('componentForm.code') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.name') }}</label>
                                <input type="text" wire:model.defer="componentForm.name" class="{{ $inp }}" />
                                @error('componentForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.type') }}</label>
                                <select wire:model.live="componentForm.type" class="{{ $inp }}">
                                    <option value="earning">{{ __('compensation::dashboard.types.earning') }}</option>
                                    <option value="deduction">{{ __('compensation::dashboard.types.deduction') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.calc_type') }}</label>
                                <select wire:model.live="componentForm.calc_type" class="{{ $inp }}">
                                    @foreach (['fixed', 'percent', 'formula', 'per_diem', 'rate'] as $ct)
                                        <option value="{{ $ct }}">{{ __('compensation::dashboard.calc_types.'.$ct) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4 pt-1">
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" wire:model.defer="componentForm.taxable" class="rounded border-zinc-300" /> {{ __('compensation::dashboard.fields.taxable') }}</label>
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" wire:model.defer="componentForm.affects_social" class="rounded border-zinc-300" /> {{ __('compensation::dashboard.fields.affects_social') }}</label>
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" wire:model.defer="componentForm.is_statutory" class="rounded border-zinc-300" /> {{ __('compensation::dashboard.fields.is_statutory') }}</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="saveComponent" class="{{ $primaryBtn }}">{{ __('compensation::dashboard.actions.save') }}</button>
                            @if ($editingComponentId)
                                <button type="button" wire:click="cancelComponent" class="{{ $ghostBtn }}">{{ __('compensation::dashboard.actions.cancel') }}</button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <span class="{{ $lbl }}">{{ __('compensation::dashboard.components.list') }} ({{ $this->components->total() }})</span>
                    <input type="search" wire:model.live.debounce.300ms="componentSearch" placeholder="{{ __('compensation::dashboard.actions.search') }}" class="h-9 w-40 rounded-xl border border-zinc-200 bg-white px-3 text-xs outline-none focus:ring-2 focus:ring-zinc-200" />
                </div>
                <div class="space-y-2">
                    @forelse ($this->components as $component)
                        <div class="flex items-center justify-between gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-zinc-900">{{ $component->name }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[11px] font-medium">
                                    <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 uppercase tracking-wide text-zinc-600">{{ $component->code }}</span>
                                    <span class="rounded-full px-2 py-0.5 {{ $component->type === 'earning' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ __('compensation::dashboard.types.'.$component->type) }}</span>
                                    @if ($component->is_statutory)<span class="rounded-full bg-sky-50 px-2 py-0.5 text-sky-700">{{ __('compensation::dashboard.fields.is_statutory') }}</span>@endif
                                </div>
                            </div>
                            @if ($canManage)
                                <div class="flex shrink-0 items-center gap-1">
                                    <button type="button" wire:click="editComponent({{ $component->id }})" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                    <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('compensation::dashboard.confirm.delete')), confirmText: @js(__('compensation::dashboard.actions.delete')), run: () => $wire.deleteComponent({{ $component->id }}) })" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.components.empty')" />
                    @endforelse
                </div>
                <div class="mt-3">{{ $this->components->links() }}</div>
            </div>
        </div>
    @endif

    {{-- ================= ASSIGNMENTS / BANK / HISTORY share personnel picker ================= --}}
    @if (in_array($activeTab, ['assignments', 'bank', 'history'], true))
        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.personnel') }}</label>
            <div class="relative mt-1" x-data="{ open: false }" x-on:click.outside="open = false">
                @if ($selectedTabelNo)
                    <div class="flex items-center justify-between rounded-xl bg-[#f5f5f7] px-4 py-2.5">
                        <span class="text-sm font-semibold text-zinc-900">{{ $selectedPersonnelLabel }}</span>
                        <button type="button" wire:click="clearPersonnel" class="text-xs font-medium text-zinc-500 hover:text-rose-600">{{ __('compensation::dashboard.actions.clear') }}</button>
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
    @endif

    {{-- ================= ASSIGNMENTS ================= --}}
    @if ($activeTab === 'assignments' && $selectedTabelNo)
        @php $current = $this->currentAssignment; @endphp
        @if ($current)
            <div class="rounded-[24px] border border-emerald-200 bg-emerald-50/50 p-5">
                <span class="{{ $lbl }}">{{ __('compensation::dashboard.assignments.current') }}</span>
                <p class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950">{{ $current->maskedBaseAmount() }} <span class="text-base font-medium text-zinc-500">{{ $current->currency }}</span></p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('compensation::dashboard.fields.effective_from') }}: {{ optional($current->effective_from)->format('d.m.Y') }}</p>
            </div>
        @endif

        @if ($canManage)
            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('compensation::dashboard.assignments.title') }}</h3>
                <div class="mt-4 grid gap-3 lg:grid-cols-3">
                    <div>
                        <x-ui.select-dropdown :label="__('compensation::dashboard.fields.regime')" mode="gray" direction="auto" wire:model.live="assignmentForm.regime_id" :model="$this->regimeOptions" />
                        @error('assignmentForm.regime_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.base_amount') }}</label>
                        <input type="number" step="0.01" wire:model.defer="assignmentForm.base_amount" class="{{ $inp }}" />
                        @error('assignmentForm.base_amount') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.effective_from') }}</label>
                        <input type="date" wire:model.defer="assignmentForm.effective_from" class="{{ $inp }}" />
                        @error('assignmentForm.effective_from') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.order_no') }}</label>
                        <input type="text" wire:model.defer="assignmentForm.order_no" class="{{ $inp }}" />
                    </div>
                    <div class="lg:col-span-2">
                        <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.note') }}</label>
                        <input type="text" wire:model.defer="assignmentForm.note" class="{{ $inp }}" />
                    </div>
                </div>

                <div class="mt-5 border-t border-zinc-100 pt-4">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="{{ $lbl }}">{{ __('compensation::dashboard.assignments.lines') }}</span>
                        <button type="button" wire:click="addAssignmentLine" class="{{ $ghostBtn }}">{{ __('compensation::dashboard.actions.add_line') }}</button>
                    </div>
                    <div class="space-y-2">
                        @foreach ($assignmentLines as $i => $line)
                            <div class="grid items-end gap-2 rounded-2xl border border-zinc-100 bg-zinc-50/70 p-3 sm:grid-cols-12">
                                <div class="sm:col-span-5">
                                    <x-ui.select-dropdown :label="__('compensation::dashboard.fields.component')" mode="gray" direction="auto" wire:model.live="assignmentLines.{{ $i }}.component_id" :model="$this->componentOptions" :instance="'line-'.$i" />
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.amount') }}</label>
                                    <input type="number" step="0.01" wire:model.defer="assignmentLines.{{ $i }}.amount" class="{{ $inp }}" />
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.percent') }}</label>
                                    <input type="number" step="0.01" wire:model.defer="assignmentLines.{{ $i }}.percent" class="{{ $inp }}" />
                                </div>
                                <div class="sm:col-span-1 flex justify-end">
                                    <button type="button" wire:click="removeAssignmentLine({{ $i }})" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="button" wire:click="saveAssignment" class="{{ $primaryBtn }}">{{ __('compensation::dashboard.actions.assign') }}</button>
                </div>
            </div>
        @endif
    @endif

    {{-- ================= BANK ================= --}}
    @if ($activeTab === 'bank' && $selectedTabelNo)
        <div class="grid gap-4 xl:grid-cols-2">
            @if ($canManage)
                <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('compensation::dashboard.bank.title') }}</h3>
                    <div class="mt-4 grid gap-3">
                        <div>
                            <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.iban') }}</label>
                            <input type="text" wire:model.defer="bankForm.iban" class="{{ $inp }} uppercase" />
                            @error('bankForm.iban') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.bank_name') }}</label>
                                <input type="text" wire:model.defer="bankForm.bank_name" class="{{ $inp }}" />
                            </div>
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.account_no') }}</label>
                                <input type="text" wire:model.defer="bankForm.account_no" class="{{ $inp }}" />
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4 pt-1">
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" wire:model.defer="bankForm.is_primary" class="rounded border-zinc-300" /> {{ __('compensation::dashboard.fields.is_primary') }}</label>
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" wire:model.defer="bankForm.is_active" class="rounded border-zinc-300" /> {{ __('compensation::dashboard.fields.is_active') }}</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="saveBank" class="{{ $primaryBtn }}">{{ __('compensation::dashboard.actions.save') }}</button>
                            @if ($editingBankId)
                                <button type="button" wire:click="cancelBank" class="{{ $ghostBtn }}">{{ __('compensation::dashboard.actions.cancel') }}</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <span class="{{ $lbl }}">{{ __('compensation::dashboard.bank.list') }}</span>
                <div class="mt-3 space-y-2">
                    @forelse ($this->bankAccounts as $account)
                        <div class="flex items-center justify-between gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold tracking-tight text-zinc-900">{{ $account->iban }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $account->bank_name }}@if ($account->is_primary) · <span class="font-semibold text-emerald-600">{{ __('compensation::dashboard.fields.is_primary') }}</span>@endif</p>
                            </div>
                            @if ($canManage)
                                <div class="flex shrink-0 items-center gap-1">
                                    <button type="button" wire:click="editBank({{ $account->id }})" class="{{ $editBtn }}">{!! $editIcon !!}</button>
                                    <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('compensation::dashboard.confirm.delete')), confirmText: @js(__('compensation::dashboard.actions.delete')), run: () => $wire.deleteBank({{ $account->id }}) })" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.bank.empty')" />
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- ================= HISTORY ================= --}}
    @if ($activeTab === 'history' && $selectedTabelNo)
        <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('compensation::dashboard.history.title') }}</h3>
            <div class="mt-4 space-y-2">
                @forelse ($this->history as $row)
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-zinc-100 bg-zinc-50/70 px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold tracking-tight text-zinc-900">{{ $row->maskedBaseAmount() }} {{ $row->currency }}</p>
                            <p class="mt-0.5 text-xs text-zinc-500">{{ $row->regime?->name }} · {{ optional($row->effective_from)->format('d.m.Y') }} — {{ $row->effective_to ? optional($row->effective_to)->format('d.m.Y') : __('compensation::dashboard.history.ongoing') }}</p>
                        </div>
                        <span class="shrink-0 rounded-full border px-3 py-1 text-xs font-semibold tracking-tight {{ $row->status === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-zinc-200 bg-white text-zinc-500' }}">{{ __('compensation::dashboard.status.'.$row->status) }}</span>
                    </div>
                @empty
                    <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.history.empty')" />
                @endforelse
            </div>
        </div>
    @endif

    {{-- ================= STATUTORY RATES ================= --}}
    @if ($activeTab === 'statutory')
        <div class="grid gap-4 xl:grid-cols-2">
            @if ($canManage)
                <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-950">{{ __('compensation::dashboard.statutory.title') }}</h3>
                    <div class="mt-4 grid gap-3">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.regime') }}</label>
                                <select wire:model.live="statutoryForm.regime_id" class="{{ $inp }}">
                                    <option value="">{{ __('compensation::dashboard.statutory.default_regime') }}</option>
                                    @foreach ($this->regimeOptions as $opt)
                                        <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.statutory.component') }}</label>
                                <select wire:model.live="statutoryForm.component_code" class="{{ $inp }}">
                                    @foreach (['income_tax', 'dsmf', 'unemployment', 'medical'] as $code)
                                        <option value="{{ $code }}">{{ __('compensation::dashboard.statutory.components.'.$code) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.statutory.payer') }}</label>
                                <select wire:model.live="statutoryForm.payer" class="{{ $inp }}">
                                    <option value="ee">{{ __('compensation::dashboard.statutory.payers.ee') }}</option>
                                    <option value="er">{{ __('compensation::dashboard.statutory.payers.er') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="{{ $lbl }}">{{ __('compensation::dashboard.statutory.base') }}</label>
                                <select wire:model.live="statutoryForm.base" class="{{ $inp }}">
                                    <option value="social">{{ __('compensation::dashboard.statutory.bases.social') }}</option>
                                    <option value="taxable">{{ __('compensation::dashboard.statutory.bases.taxable') }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="{{ $lbl }}">{{ __('compensation::dashboard.fields.effective_from') }}</label>
                            <input type="date" wire:model.defer="statutoryForm.effective_from" class="{{ $inp }}" />
                            @error('statutoryForm.effective_from') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>

                        <div class="border-t border-zinc-100 pt-3">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="{{ $lbl }}">{{ __('compensation::dashboard.statutory.rate') }}</span>
                                <button type="button" wire:click="addStatutoryBracket" class="{{ $ghostBtn }}">{{ __('compensation::dashboard.statutory.add_bracket') }}</button>
                            </div>
                            @error('statutoryBrackets') <x-validation>{{ $message }}</x-validation> @enderror
                            <div class="space-y-2">
                                @foreach ($statutoryBrackets as $i => $bracket)
                                    <div class="grid items-end gap-2 rounded-2xl border border-zinc-100 bg-zinc-50/70 p-3 sm:grid-cols-12">
                                        <div class="sm:col-span-5">
                                            <label class="{{ $lbl }}">{{ __('compensation::dashboard.statutory.up_to') }}</label>
                                            <input type="number" step="0.01" wire:model.defer="statutoryBrackets.{{ $i }}.up_to" class="{{ $inp }}" />
                                        </div>
                                        <div class="sm:col-span-5">
                                            <label class="{{ $lbl }}">{{ __('compensation::dashboard.statutory.rate') }}</label>
                                            <input type="number" step="0.01" wire:model.defer="statutoryBrackets.{{ $i }}.rate" class="{{ $inp }}" />
                                        </div>
                                        <div class="sm:col-span-2 flex justify-end">
                                            <button type="button" wire:click="removeStatutoryBracket({{ $i }})" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <button type="button" wire:click="saveStatutoryRate" class="{{ $primaryBtn }}">{{ __('compensation::dashboard.actions.save') }}</button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-[24px] border border-zinc-200 bg-white p-5 shadow-sm">
                <span class="{{ $lbl }}">{{ __('compensation::dashboard.statutory.list') }} ({{ $this->statutoryRates->count() }})</span>
                <div class="mt-3 space-y-2">
                    @forelse ($this->statutoryRates as $rate)
                        <div class="flex items-center justify-between gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold tracking-tight text-zinc-900">{{ __('compensation::dashboard.statutory.components.'.$rate->component_code) }} · {{ __('compensation::dashboard.statutory.payers.'.$rate->payer) }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500">{{ $rate->regime?->name ?? __('compensation::dashboard.statutory.default_regime') }} · {{ collect($rate->brackets)->map(fn ($b) => ($b['up_to'] ?? '∞').':'.$b['rate'].'%')->implode(', ') }}</p>
                            </div>
                            @if ($canManage)
                                <button type="button" x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('compensation::dashboard.confirm.delete')), confirmText: @js(__('compensation::dashboard.actions.delete')), run: () => $wire.deleteStatutoryRate({{ $rate->id }}) })" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                            @endif
                        </div>
                    @empty
                        <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.statutory.empty')" />
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
