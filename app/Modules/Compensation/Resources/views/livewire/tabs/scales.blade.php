<div class="contents">
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
                    {{ __('compensation::dashboard.scales.meta', ['scales' => $num($scaleCount), 'grades' => $num($gradeCount)]) }}
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
                <x-pill-button variant="primary" wire:click="openPanel('grade')">{{ __('compensation::dashboard.actions.add_grade') }}</x-pill-button>
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

    {{-- ===================== editor side panel ===================== --}}
    @if ($canManage && $panel !== '')
        <x-ui.side-panel
            title-id="compensation-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('compensation::dashboard.actions.close')"
            width="3xl"
        >
            @include('compensation::livewire.tabs.partials.panel-header', ['panelTitle' => $panel === 'scale' ? __('compensation::dashboard.scales.title') : __('compensation::dashboard.grades.title')])

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                @if ($panel === 'scale')
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::dashboard.fields.name')" :error="$errors->first('scaleForm.name')">
                            <x-ui.input wire:model="scaleForm.name" />
                        </x-ui.input-shell>
                        <div class="min-w-0">
                            <x-ui.select-dropdown :label="__('compensation::dashboard.fields.regime')" mode="gray" direction="auto" wire:model.live="scaleForm.regime_id" :model="$this->regimeOptions" />
                            @error('scaleForm.regime_id') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.currency')">
                            <x-ui.input maxlength="3" class="uppercase" wire:model="scaleForm.currency" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.effective_from')" :error="$errors->first('scaleForm.effective_from')">
                            <x-ui.input type="date" wire:model="scaleForm.effective_from" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.effective_to')" :error="$errors->first('scaleForm.effective_to')">
                            <x-ui.input type="date" wire:model="scaleForm.effective_to" />
                        </x-ui.input-shell>
                        <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::dashboard.fields.description')" :error="$errors->first('scaleForm.description')">
                            <x-ui.textarea wire:model="scaleForm.description" rows="2" />
                        </x-ui.input-shell>
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.code')" :error="$errors->first('gradeForm.code')">
                            <x-ui.input wire:model="gradeForm.code" />
                        </x-ui.input-shell>
                        <x-ui.input-shell :label="__('compensation::dashboard.fields.base_amount')" :error="$errors->first('gradeForm.base_amount')">
                            <x-ui.input type="number" step="0.01" wire:model="gradeForm.base_amount" />
                        </x-ui.input-shell>
                        <x-ui.input-shell class="sm:col-span-2" :label="__('compensation::dashboard.fields.name')" :error="$errors->first('gradeForm.name')">
                            <x-ui.input wire:model="gradeForm.name" />
                        </x-ui.input-shell>
                        <div class="min-w-0">
                            <x-ui.select-dropdown :label="__('compensation::dashboard.fields.rank_category')" mode="gray" direction="auto" wire:model.live="gradeForm.rank_category_id" :model="$this->rankCategoryOptions" search-model="searchRankCategory" />
                        </div>
                        <div class="min-w-0">
                            <x-ui.select-dropdown :label="__('compensation::dashboard.fields.position')" mode="gray" direction="auto" wire:model.live="gradeForm.position_id" :model="$this->positionOptions" search-model="searchPosition" />
                        </div>
                    </div>
                @endif
            </div>

            @include('compensation::livewire.tabs.partials.panel-footer', ['save' => $panel === 'scale' ? 'saveScale' : 'saveGrade'])
        </x-ui.side-panel>
    @endif
</div>
