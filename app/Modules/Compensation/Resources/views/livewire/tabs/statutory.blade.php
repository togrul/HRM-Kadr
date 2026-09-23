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
                    </div>
                @endforeach
            </div>
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
