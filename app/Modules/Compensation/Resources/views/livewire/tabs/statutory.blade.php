<div class="contents">
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
                        <p class="mt-0.5 truncate text-[11px] text-ink-faint">{{ __('compensation::dashboard.statutory.effective', ['date' => $rate->effective_from?->format('d.m.Y')]) }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ===================== gəlir vergisi azadolmaları (VM m.102) ===================== --}}
    <section class="mt-4 overflow-hidden rounded-xl border border-hairline bg-white">
        <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
            <div class="min-w-0">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('compensation::dashboard.exemptions.title') }}</h2>
                <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __('compensation::dashboard.exemptions.hint') }}</p>
            </div>
            @if ($canManage)
                <x-pill-button variant="primary" wire:click="openPanel('exemption')">{{ __('compensation::dashboard.exemptions.add') }}</x-pill-button>
            @endif
        </div>

        @if ($this->statutoryExemptions->isEmpty())
            <div class="px-4 py-8">
                <x-ui.empty-state icon="icons.document-icon" :title="__('compensation::dashboard.exemptions.empty')" />
            </div>
        @else
            <div class="grid gap-3 px-4 py-3.5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($this->statutoryExemptions as $exemption)
                    <div wire:key="compensation-exemption-{{ $exemption->id }}" class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3.5">
                        <div class="flex items-start justify-between gap-2">
                            <p class="min-w-0 truncate text-[13px] font-medium text-ink">{{ $exemption->legal_basis }}</p>
                            @if ($canManage)
                                <button type="button" x-on:click="{{ $confirmDelete('deleteStatutoryExemption('.$exemption->id.')') }}" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }} -mt-1 -mr-1.5">{!! $delIcon !!}</button>
                            @endif
                        </div>

                        @if ($exemption->kind === \App\Models\StatutoryExemption::KIND_TAXABLE_COMPONENTS)
                            <p class="mt-2 text-[12.5px] text-ink-soft">{{ __('compensation::dashboard.exemptions.taxable_only', ['codes' => implode(', ', (array) $exemption->component_codes)]) }}</p>
                        @else
                            <p class="mt-2 flex items-baseline gap-1.5">
                                <span class="hrm-num text-[22px] font-semibold leading-none tracking-[-0.035em] text-ink">{{ number_format((float) $exemption->amount, 2, ',', ' ') }}</span>
                                <span class="text-[11.5px] text-ink-faint">AZN</span>
                            </p>
                            <p class="mt-1.5 text-[11px] text-ink-faint">
                                {{ $exemption->income_up_to !== null ? __('compensation::dashboard.exemptions.income_limit', ['amount' => number_format((float) $exemption->income_up_to, 2, ',', ' ')]) : __('compensation::dashboard.exemptions.no_income_limit') }}
                                @if ($exemption->main_workplace_only)
                                    <span class="px-0.5">·</span>{{ __('compensation::dashboard.exemptions.main_workplace_only') }}
                                @endif
                            </p>
                        @endif

                        <p class="mt-1 truncate text-[11px] text-ink-faint">{{ $exemption->regime?->name ?? __('compensation::dashboard.statutory.default_regime') }}</p>
                        <p class="mt-0.5 truncate text-[11px] text-ink-faint">
                            {{ __('compensation::dashboard.statutory.effective', ['date' => $exemption->effective_from?->format('d.m.Y')]) }}
                            @if ($exemption->effective_to)
                                — {{ $exemption->effective_to->format('d.m.Y') }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ===================== editor side panel ===================== --}}
    @if ($canManage && $panel === 'exemption')
        <x-ui.side-panel
            title-id="compensation-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('compensation::dashboard.actions.close')"
            width="3xl"
        >
            @include('compensation::livewire.tabs.partials.panel-header', ['panelTitle' => __('compensation::dashboard.exemptions.add')])

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                <p class="text-[12px] leading-relaxed text-ink-faint">{{ __('compensation::dashboard.exemptions.panel_hint') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.input-shell :label="__('compensation::dashboard.fields.regime')">
                        <x-ui.select wire:model.live="exemptionForm.regime_id">
                            <option value="">{{ __('compensation::dashboard.statutory.default_regime') }}</option>
                            @foreach ($this->regimeOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.exemptions.legal_basis')" :error="$errors->first('exemptionForm.legal_basis')">
                        <x-ui.input wire:model="exemptionForm.legal_basis" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.exemptions.code')" :error="$errors->first('exemptionForm.code')">
                        <x-ui.input wire:model="exemptionForm.code" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.exemptions.amount')" :error="$errors->first('exemptionForm.amount')">
                        <x-ui.input type="number" step="0.01" wire:model="exemptionForm.amount" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.exemptions.income_up_to')" :error="$errors->first('exemptionForm.income_up_to')">
                        <x-ui.input type="number" step="0.01" wire:model="exemptionForm.income_up_to" />
                    </x-ui.input-shell>
                    <x-ui.input-shell :label="__('compensation::dashboard.fields.effective_from')" :error="$errors->first('exemptionForm.effective_from')">
                        <x-ui.input type="date" wire:model="exemptionForm.effective_from" />
                    </x-ui.input-shell>
                    <div class="flex flex-wrap gap-4 pt-1 sm:col-span-2">
                        <label class="inline-flex items-center gap-2 text-[12.5px] font-medium text-ink-muted">
                            <input type="checkbox" wire:model="exemptionForm.main_workplace_only" class="rounded border-hairline text-ink focus:ring-[#e4e4e7]" />
                            {{ __('compensation::dashboard.exemptions.main_workplace_only') }}
                        </label>
                    </div>
                </div>
            </div>

            @include('compensation::livewire.tabs.partials.panel-footer', ['save' => 'saveStatutoryExemption'])
        </x-ui.side-panel>
    @elseif ($canManage && $panel !== '')
        <x-ui.side-panel
            title-id="compensation-panel-title"
            close-action="$wire.closePanel()"
            :close-label="__('compensation::dashboard.actions.close')"
            width="3xl"
        >
            @include('compensation::livewire.tabs.partials.panel-header', ['panelTitle' => __('compensation::dashboard.statutory.title')])

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
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
                        <x-ui.input type="date" wire:model="statutoryForm.effective_from" />
                    </x-ui.input-shell>
                </div>

                <div class="rounded-xl border border-hairline bg-[#fafafa] px-4 py-3.5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="hrm-eyebrow">{{ __('compensation::dashboard.statutory.brackets') }}</p>
                        <x-pill-button variant="primary" wire:click="addStatutoryBracket">{{ __('compensation::dashboard.statutory.add_bracket') }}</x-pill-button>
                    </div>
                    @error('statutoryBrackets') <x-validation>{{ $message }}</x-validation> @enderror

                    <div class="mt-3 space-y-2">
                        @foreach ($statutoryBrackets as $i => $bracket)
                            <div wire:key="compensation-bracket-{{ $i }}" class="grid items-end gap-2 rounded-xl border border-hairline bg-white p-3 sm:grid-cols-12">
                                <x-ui.input-shell class="min-w-0 sm:col-span-5" :label="__('compensation::dashboard.statutory.up_to')">
                                    <x-ui.input type="number" step="0.01" wire:model="statutoryBrackets.{{ $i }}.up_to" />
                                </x-ui.input-shell>
                                <x-ui.input-shell class="min-w-0 sm:col-span-5" :label="__('compensation::dashboard.statutory.rate')" :error="$errors->first('statutoryBrackets.'.$i.'.rate')">
                                    <x-ui.input type="number" step="0.01" wire:model="statutoryBrackets.{{ $i }}.rate" />
                                </x-ui.input-shell>
                                <div class="flex justify-end sm:col-span-2">
                                    <button type="button" wire:click="removeStatutoryBracket({{ $i }})" title="{{ __('compensation::dashboard.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @include('compensation::livewire.tabs.partials.panel-footer', ['save' => 'saveStatutoryRate'])
        </x-ui.side-panel>
    @endif
</div>
