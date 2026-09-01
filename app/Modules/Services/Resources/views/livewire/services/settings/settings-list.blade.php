<div x-data class="flex flex-col">
    @if ($section === 'candidate')
        <div class="flex flex-col gap-4">
            <section class="rounded-xl border border-hairline bg-white px-4 py-3.5">
                <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('services::settings.labels.candidate_status_whitelist_presets') }}</h2>
                <p class="mt-0.5 max-w-3xl text-[11.5px] leading-5 text-ink-faint">{{ __('services::settings.messages.candidate_presets_description') }}</p>
            </section>

            <div class="grid items-start gap-4 xl:grid-cols-2">
                @foreach($this->candidateModes() as $mode => $modeMeta)
                    @php
                        $selectedStatuses = array_map('strval', $candidateStatusWhitelist[$mode] ?? []);
                        $enabledFilters = array_map('strval', $candidateEnabledFilters[$mode] ?? []);
                        $optionRow = 'flex cursor-pointer items-center justify-between gap-3 rounded-[10px] border px-3 py-2 transition';
                    @endphp

                    <section class="overflow-hidden rounded-xl border border-hairline bg-white" wire:key="candidate-mode-{{ $mode }}">
                        <div class="flex items-start justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                            <div class="min-w-0">
                                <h3 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ $modeMeta['title'] }}</h3>
                                <p class="mt-0.5 text-[11.5px] leading-5 text-ink-faint">{{ __('services::settings.messages.mode_hint') }}</p>
                            </div>

                            <span class="inline-flex shrink-0 items-center rounded-md bg-[#f4f4f5] px-2 py-0.5 text-[11px] font-medium text-ink-muted">
                                {{ $mode === 'military' ? __('services::settings.labels.military_short') : __('services::settings.labels.civilian_short') }}
                            </span>
                        </div>

                        <div class="space-y-3 border-b border-hairline-subtle px-4 py-3.5">
                            <x-ui.input-shell :label="__('services::settings.labels.default_status')">
                                <x-ui.select wire:model.live="candidatePresetSettings.{{ $mode }}.default_status">
                                    <option value="all">{{ __('services::common.actions.all') }}</option>
                                    <option value="deleted">{{ __('services::common.labels.deleted') }}</option>
                                    @foreach($candidateStatuses as $statusOption)
                                        <option value="{{ $statusOption['id'] }}">#{{ $statusOption['id'] }} — {{ $statusOption['name'] }}</option>
                                    @endforeach
                                </x-ui.select>
                            </x-ui.input-shell>

                            <div class="flex items-center justify-between gap-3">
                                <p class="text-[13px] font-medium text-ink">{{ __('services::settings.labels.show_deleted_tab') }}</p>
                                <x-ui.toggle wire:model.live="candidatePresetSettings.{{ $mode }}.show_deleted_tab" />
                            </div>
                        </div>

                        <div class="space-y-3 border-b border-hairline-subtle px-4 py-3.5">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="hrm-eyebrow">{{ __('services::settings.labels.statuses') }}</h4>
                                <div class="flex items-center gap-1.5">
                                    <span class="hrm-num rounded-full bg-[#f4f4f5] px-2 py-0.5 text-[10.5px] text-ink-muted">{{ count($selectedStatuses) }} {{ __('services::settings.labels.selected_count') }}</span>
                                    <x-pill-button variant="secondary" wire:click="selectAllCandidateStatuses('{{ $mode }}')">{{ __('services::common.actions.all') }}</x-pill-button>
                                    <x-pill-button variant="secondary" wire:click="clearAllCandidateStatuses('{{ $mode }}')">{{ __('services::common.actions.clear') }}</x-pill-button>
                                </div>
                            </div>

                            <div class="grid gap-2">
                                @forelse($candidateStatuses as $statusOption)
                                    @php $isSelected = in_array((string) $statusOption['id'], $selectedStatuses, true); @endphp
                                    <label @class([$optionRow, 'border-ink bg-[#fafafa]' => $isSelected, 'border-hairline hover:bg-[#fafafa]' => ! $isSelected])>
                                        <span class="min-w-0">
                                            <span class="block truncate text-[13px] font-medium text-ink">{{ $statusOption['name'] }}</span>
                                            <span class="hrm-num block text-[11px] text-ink-faint">#{{ $statusOption['id'] }}</span>
                                        </span>

                                        <input type="checkbox" value="{{ $statusOption['id'] }}" wire:model.live="candidateStatusWhitelist.{{ $mode }}"
                                            class="h-4 w-4 shrink-0 rounded border-hairline text-ink focus:ring-ink/20" />
                                    </label>
                                @empty
                                    <p class="text-[12.5px] text-ink-faint">{{ __('services::settings.messages.no_statuses_found') }}</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="space-y-3 px-4 py-3.5">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="hrm-eyebrow">{{ __('services::settings.labels.enabled_filters') }}</h4>
                                <span class="hrm-num rounded-full bg-[#f4f4f5] px-2 py-0.5 text-[10.5px] text-ink-muted">{{ count($enabledFilters) }} {{ __('services::settings.labels.active_filters_count') }}</span>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($this->candidateFilterOptionsForMode($mode) as $filterOption)
                                    @php $filterActive = in_array((string) $filterOption['key'], $enabledFilters, true); @endphp
                                    <label @class([$optionRow, 'border-ink bg-[#fafafa]' => $filterActive, 'border-hairline hover:bg-[#fafafa]' => ! $filterActive])>
                                        <span class="truncate text-[13px] font-medium text-ink">{{ $filterOption['label'] }}</span>
                                        <input type="checkbox" value="{{ $filterOption['key'] }}" wire:model.live="candidateEnabledFilters.{{ $mode }}"
                                            class="h-4 w-4 shrink-0 rounded border-hairline text-ink focus:ring-ink/20" />
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="flex justify-end">
                <x-pill-button variant="primary" wire:click="saveCandidateStatusWhitelist">{{ __('services::settings.actions.save_presets') }}</x-pill-button>
            </div>
        </div>
    @else
        @php
            $chiefOptions = $this->chiefPersonnelOptions();
            $chiefMode = data_get($chiefSnapshot, 'mode');
            $chiefChip = match ($chiefMode) {
                'delegated' => 'bg-amber-50 text-amber-700',
                'legacy' => 'bg-[#f4f4f5] text-ink-muted',
                default => 'bg-emerald-50 text-emerald-700',
            };
            $chiefChipLabel = __('services::settings.labels.mode_'.($chiefMode === 'delegated' ? 'delegated' : ($chiefMode === 'legacy' ? 'legacy' : 'permanent')));
            $chip = 'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium';
            $rowLabel = 'text-[13px] font-medium text-ink';
            $rowHint = 'mt-0.5 text-[11.5px] text-ink-faint';
            $delBtn = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-ink-faint transition hover:bg-rose-50 hover:text-rose-600';
            $delIcon = '<svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>';
        @endphp

        <div class="flex flex-col gap-4">
            {{-- ================= ÜMUMİ ================= --}}
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="flex flex-col gap-3 border-b border-hairline-subtle px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('services::common.labels.general') }}</h2>
                        <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __('services::settings.messages.general_description') }}</p>
                    </div>

                    <x-pill-button variant="primary" x-on:click.prevent="Livewire.dispatch('settingsWasSet')">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __('services::settings.actions.add_settings') }}
                    </x-pill-button>
                </div>

                <div class="divide-y divide-hairline-subtle">
                    @forelse ($settings as $key => $settingValue)
                        <div class="flex items-center justify-between gap-4 px-4 py-3" wire:key="setting-row-{{ $settingValue->id }}">
                            <div class="min-w-0">
                                <p class="{{ $rowLabel }}">{{ $this->resolveSettingLabel((string) $settingValue->name) }}</p>
                                <p class="{{ $rowHint }}">{{ $settingValue->name }}</p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                @if ($settingValue->type === 'bool')
                                    <x-ui.toggle wire:model.live="setting.{{ $key }}.value" />
                                @elseif ($settingValue->type === 'string')
                                    <div class="w-[220px]">
                                        <x-ui.input wire:model.live="setting.{{ $key }}.value" />
                                    </div>
                                @else
                                    <div class="w-[110px]">
                                        <x-ui.input type="number" step="0.01" class="text-right" wire:model.live="setting.{{ $key }}.value" />
                                    </div>
                                @endif

                                <button type="button" wire:click.prevent="setDeleteSettings({{ $settingValue->id }})" title="{{ __('services::common.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-4">
                            <x-ui.empty-state icon="icons.settings2-icon" :title="__('services::settings.messages.no_settings')" />
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- ================= ƏMSALLAR ================= --}}
            <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('services::settings.labels.coefficients') }}</h2>
                    <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __('services::settings.messages.coefficients_description') }}</p>
                </div>

                <div class="divide-y divide-hairline-subtle">
                    @forelse ($coefficientSettings as $coefficientSetting)
                        @php $coefficientIndex = $coefficientSettingIndexes[$coefficientSetting->name] ?? null; @endphp

                        <div class="flex items-center justify-between gap-4 px-4 py-3" wire:key="coefficient-row-{{ $coefficientSetting->id }}">
                            <p class="{{ $rowLabel }}">{{ $this->resolveSettingLabel((string) $coefficientSetting->name) }}</p>

                            <div class="flex shrink-0 items-center gap-2">
                                @if ($coefficientIndex !== null)
                                    <div class="w-[110px]">
                                        <x-ui.input type="number" step="0.01" class="text-right" wire:model.live="setting.{{ $coefficientIndex }}.value" />
                                    </div>
                                @endif

                                <button type="button" wire:click.prevent="setDeleteSettings({{ $coefficientSetting->id }})" title="{{ __('services::common.actions.delete') }}" class="{{ $delBtn }}">{!! $delIcon !!}</button>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-4">
                            <x-ui.empty-state icon="icons.settings2-icon" :title="__('services::settings.messages.no_coefficients')" />
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- ================= RƏHBƏR VƏ HƏVALƏ ================= --}}
            <div class="grid items-start gap-4 xl:grid-cols-2">
                <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                    <div class="border-b border-hairline-subtle px-4 py-3">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('services::settings.labels.permanent_chief') }}</h2>
                        <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __('services::settings.messages.permanent_chief_hint') }}</p>
                    </div>

                    <div class="flex items-center gap-3 border-b border-hairline-subtle px-4 py-3.5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-hairline bg-[#fafafa] text-ink-faint">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20a7.5 7.5 0 0 1 15 0"/></svg>
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-semibold text-ink">{{ data_get($chiefSnapshot, 'fullname') ?: __('services::settings.labels.chief_not_set') }}</p>
                            <p class="truncate text-[11.5px] text-ink-faint">{{ data_get($chiefSnapshot, 'title') ?: __('services::settings.labels.chief_no_title') }}</p>
                        </div>

                        <span class="{{ $chip }} {{ $chiefChip }}">{{ $chiefChipLabel }}</span>
                    </div>

                    <div class="space-y-3 px-4 py-4">
                        <x-ui.input-shell :label="__('services::settings.labels.chief_select')">
                            <x-ui.select wire:model.live="chiefPersonnelId">
                                <option value="">{{ __('services::settings.labels.chief_auto') }}</option>
                                @foreach ($chiefOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}@if ($option['position']) — {{ $option['position'] }}@endif</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>

                        <div class="flex justify-end">
                            <x-pill-button variant="primary" wire:click="saveChiefPersonnel">{{ __('services::common.actions.save') }}</x-pill-button>
                        </div>
                    </div>
                </section>

                <section class="flex flex-col overflow-hidden rounded-xl border border-hairline bg-white">
                    <div class="flex items-center justify-between gap-3 border-b border-hairline-subtle px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('services::settings.labels.delegation') }}</h2>
                            <p class="mt-0.5 text-[11.5px] text-ink-faint">{{ __('services::settings.messages.delegation_hint') }}</p>
                        </div>

                        @if ($chiefDelegations->isEmpty())
                            <span class="shrink-0 text-[11.5px] text-ink-faint">{{ __('services::settings.messages.no_active_delegation') }}</span>
                        @endif
                    </div>

                    <div class="grid flex-1 content-start gap-3 px-4 py-4 sm:grid-cols-2">
                        <x-ui.input-shell class="sm:col-span-2" :label="__('services::settings.labels.delegate')" :error="$errors->first('chiefDelegationForm.delegate_personnel_id')">
                            <x-ui.select wire:model.defer="chiefDelegationForm.delegate_personnel_id">
                                <option value="">{{ __('services::settings.labels.delegate_placeholder') }}</option>
                                @foreach ($chiefOptions as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['label'] }}@if ($option['position']) — {{ $option['position'] }}@endif</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('services::settings.labels.starts_at')" :error="$errors->first('chiefDelegationForm.starts_at')">
                            <x-ui.input type="date" wire:model.live="chiefDelegationForm.starts_at" />
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('services::settings.labels.ends_at')" :error="$errors->first('chiefDelegationForm.ends_at')">
                            <x-ui.input type="date" wire:model.live="chiefDelegationForm.ends_at" />
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('services::settings.labels.reason')" :error="$errors->first('chiefDelegationForm.reason')">
                            <x-ui.input wire:model.defer="chiefDelegationForm.reason" placeholder="{{ __('services::settings.labels.reason_placeholder') }}" />
                        </x-ui.input-shell>

                        <x-ui.input-shell :label="__('services::settings.labels.basis_document')" :error="$errors->first('chiefDelegationForm.basis_document')">
                            <x-ui.input wire:model.defer="chiefDelegationForm.basis_document" placeholder="{{ __('services::settings.labels.basis_placeholder') }}" />
                        </x-ui.input-shell>
                    </div>

                    <div class="mt-auto flex items-center justify-end gap-2 border-t border-hairline-subtle bg-[#fafafa] px-4 py-3">
                        <x-pill-button variant="secondary" wire:click="resetChiefDelegationForm">{{ __('services::common.actions.cancel') }}</x-pill-button>
                        <x-pill-button variant="primary" wire:click="createChiefDelegation">{{ __('services::settings.actions.create_delegation') }}</x-pill-button>
                    </div>
                </section>
            </div>

            @if ($chiefDelegations->isNotEmpty())
                <section class="overflow-hidden rounded-xl border border-hairline bg-white">
                    <div class="border-b border-hairline-subtle px-4 py-3">
                        <h2 class="text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ __('services::settings.labels.active_delegations') }}</h2>
                    </div>

                    <div class="divide-y divide-hairline-subtle">
                        @foreach ($chiefDelegations as $delegation)
                            <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" wire:key="delegation-{{ $delegation->id }}">
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] font-medium text-ink">
                                        {{ $delegation->delegate?->fullname }}
                                        <span class="text-ink-faint">— {{ __('services::settings.labels.instead_of', ['chief' => $delegation->chief?->fullname]) }}</span>
                                    </p>
                                    <p class="hrm-num mt-0.5 truncate text-[11.5px] text-ink-faint">
                                        {{ optional($delegation->starts_at)->format('d.m.Y') }}@if ($delegation->ends_at) <span class="px-0.5">—</span> {{ optional($delegation->ends_at)->format('d.m.Y') }}@endif
                                        @if ($delegation->basis_document) <span class="px-0.5">·</span> {{ $delegation->basis_document }} @endif
                                    </p>
                                </div>

                                <x-pill-button variant="secondary" wire:click="revokeChiefDelegation({{ $delegation->id }})">{{ __('services::settings.actions.stop_delegation') }}</x-pill-button>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <div>
                <livewire:services.settings.add-settings wire:key="services-settings-add-modal" />
            </div>

            @auth
                <livewire:services.settings.delete-settings wire:key="services-settings-delete-modal" />
            @endauth
        </div>
    @endif
</div>
