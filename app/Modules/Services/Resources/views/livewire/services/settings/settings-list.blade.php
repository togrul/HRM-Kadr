<div x-data class="flex flex-col">
    @if ($section === 'candidate')
        <section class="space-y-6">
            <div class="overflow-hidden rounded-[28px] border border-emerald-200 bg-gradient-to-br from-emerald-500 via-emerald-500 to-teal-500 text-white shadow-sm">
                <div class="flex flex-col gap-5 px-6 py-7 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <div class="flex items-start gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 text-white backdrop-blur">
                            <x-icons.candidate-icon size="w-8 h-8" color="text-white"></x-icons.candidate-icon>
                        </div>
                        <div class="space-y-2">
                            <div class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-50">
                                {{ __('services::common.labels.candidate_preferences') }}
                            </div>
                            <h2 class="text-2xl font-semibold leading-tight">{{ __('services::settings.labels.candidate_status_whitelist_presets') }}</h2>
                            <p class="max-w-3xl text-sm text-emerald-50/90">
                                {{ __('services::settings.messages.candidate_presets_description') }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-emerald-50/95 backdrop-blur">
                        {{ __('services::settings.messages.pick_statuses_help') }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                @foreach($this->candidateModes() as $mode => $modeMeta)
                    @php
                        $selectedStatuses = array_map('strval', $candidateStatusWhitelist[$mode] ?? []);
                        $enabledFilters = array_map('strval', $candidateEnabledFilters[$mode] ?? []);
                        $accent = $modeMeta['accent'] === 'sky'
                            ? [
                                'soft' => 'from-sky-50 to-cyan-50 border-sky-200',
                                'badge' => 'bg-sky-100 text-sky-700',
                                'selected' => 'border-sky-300 bg-sky-50 text-slate-900 shadow-sm',
                                'checkbox' => 'text-sky-600 focus:ring-sky-500',
                            ]
                            : [
                                'soft' => 'from-emerald-50 to-teal-50 border-emerald-200',
                                'badge' => 'bg-emerald-100 text-emerald-700',
                                'selected' => 'border-emerald-300 bg-emerald-50 text-slate-900 shadow-sm',
                                'checkbox' => 'text-emerald-600 focus:ring-emerald-500',
                            ];
                    @endphp

                    <section class="overflow-hidden rounded-[26px] border border-zinc-200 bg-white shadow-[0_20px_60px_-36px_rgba(15,23,42,0.28)]">
                        <div class="border-b border-zinc-200 bg-gradient-to-br {{ $accent['soft'] }} px-5 py-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="space-y-2">
                                    <div class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $accent['badge'] }}">
                                        {{ $mode === 'military' ? __('services::settings.labels.military_short') : __('services::settings.labels.civilian_short') }}
                                    </div>
                                    <h3 class="text-xl font-semibold text-slate-900">{{ $modeMeta['title'] }}</h3>
                                    <p class="text-sm text-slate-500">{{ __('services::settings.messages.mode_hint') }}</p>
                                </div>

                                <div class="grid w-full gap-3 sm:max-w-sm">
                                    <div class="space-y-1">
                                        <span class="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">{{ __('services::settings.labels.default_status') }}</span>
                                        <select wire:model.live="candidatePresetSettings.{{ $mode }}.default_status"
                                            class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm focus:border-emerald-400 focus:ring-emerald-400">
                                            <option value="all">{{ __('services::common.actions.all') }}</option>
                                            <option value="deleted">{{ __('services::common.labels.deleted') }}</option>
                                            @foreach($candidateStatuses as $statusOption)
                                                <option value="{{ $statusOption['id'] }}">#{{ $statusOption['id'] }} - {{ $statusOption['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                        <input type="checkbox" wire:model.live="candidatePresetSettings.{{ $mode }}.show_deleted_tab"
                                            class="rounded border-zinc-300 {{ $accent['checkbox'] }}">
                                        <span>{{ __('services::settings.labels.show_deleted_tab') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 px-5 py-5">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-button mode="slate" class="!rounded-2xl !border-zinc-200 !bg-zinc-100 !px-4 !py-2 !text-xs !font-semibold !text-slate-700"
                                    wire:click="selectAllCandidateStatuses('{{ $mode }}')">
                                    {{ __('services::common.actions.all') }}
                                </x-button>
                                <x-button mode="slate" class="!rounded-2xl !border-zinc-200 !bg-zinc-100 !px-4 !py-2 !text-xs !font-semibold !text-slate-700"
                                    wire:click="clearAllCandidateStatuses('{{ $mode }}')">
                                    {{ __('services::common.actions.clear') }}
                                </x-button>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">{{ __('services::settings.labels.statuses') }}</h4>
                                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-slate-500">
                                        {{ count($selectedStatuses) }} {{ __('services::settings.labels.selected_count') }}
                                    </span>
                                </div>

                                <div class="grid gap-3">
                                    @forelse($candidateStatuses as $statusOption)
                                        @php $isSelected = in_array((string) $statusOption['id'], $selectedStatuses, true); @endphp
                                        <label @class([
                                            'flex cursor-pointer items-center justify-between rounded-2xl border px-4 py-3 transition-all duration-200',
                                            $isSelected ? $accent['selected'] : 'border-zinc-200 bg-white text-slate-500 hover:border-zinc-300 hover:bg-zinc-50',
                                        ])>
                                            <div class="space-y-1">
                                                <div class="text-base font-medium text-slate-800">{{ $statusOption['name'] }}</div>
                                                <div class="text-xs uppercase tracking-[0.16em] text-slate-400">#{{ $statusOption['id'] }}</div>
                                            </div>

                                            <input type="checkbox" value="{{ $statusOption['id'] }}"
                                                wire:model.live="candidateStatusWhitelist.{{ $mode }}"
                                                class="h-5 w-5 rounded border-zinc-300 {{ $accent['checkbox'] }}">
                                        </label>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-6 text-sm text-zinc-500">
                                            {{ __('services::settings.messages.no_statuses_found') }}
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-400">{{ __('services::settings.labels.enabled_filters') }}</h4>
                                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-slate-500">
                                        {{ count($enabledFilters) }} {{ __('services::settings.labels.active_filters_count') }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @foreach($this->candidateFilterOptionsForMode($mode) as $filterOption)
                                        @php $filterActive = in_array((string) $filterOption['key'], $enabledFilters, true); @endphp
                                        <label @class([
                                            'flex cursor-pointer items-center gap-3 rounded-2xl border px-4 py-3 text-sm transition-all duration-200',
                                            $filterActive ? $accent['selected'] : 'border-zinc-200 bg-white text-slate-700 hover:border-zinc-300 hover:bg-zinc-50',
                                        ])>
                                            <input type="checkbox" value="{{ $filterOption['key'] }}"
                                                wire:model.live="candidateEnabledFilters.{{ $mode }}"
                                                class="h-5 w-5 rounded border-zinc-300 {{ $accent['checkbox'] }}">
                                            <span class="font-medium">{{ $filterOption['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="flex justify-end">
                <x-button mode="primary" class="!rounded-2xl !px-6 !py-3 !text-sm !font-semibold" wire:click="saveCandidateStatusWhitelist">
                    {{ __('services::settings.actions.save_presets') }}
                </x-button>
            </div>
        </section>
    @else
        <div class="flex flex-col justify-between sm:flex-row filter mb-4">
            <div class="flex items-center justify-center space-x-2 action-section">
                <x-button class="space-x-2" mode="primary" x-on:click.prevent="Livewire.dispatch('settingsWasSet')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>{{ __('services::settings.actions.add_settings') }}</span>
                </x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
            @foreach ($settings as $key => $settingValue)
                <div class="flex space-x-2 justify-between items-end w-full">
                    <div class="flex flex-col space-y-2 w-full">
                        <span class="text-sm font-medium text-gray-500">{{ $this->resolveSettingLabel((string) $settingValue->name) }}</span>
                        @if ($settingValue->type == 'string')
                            <x-livewire-input mode="gray" name="setting.{{ $key }}.value"
                                wire:model.live="setting.{{ $key }}.value"></x-livewire-input>
                        @elseif($settingValue->type == 'bool')
                            <x-checkbox name="setting.{{ $key }}.value" model="setting.{{ $key }}.value"
                                checked="{{ $settingValue->value == '1' }}"
                                value="{{ (bool) $settingValue->value }}"></x-checkbox>
                        @else
                            <x-livewire-input mode="gray" type="number" name="setting.{{ $key }}.value"
                                wire:model.live="setting.{{ $key }}.value"></x-livewire-input>
                        @endif
                    </div>
                    <button wire:click.prevent = "setDeleteSettings({{ $settingValue->id }})"
                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-100 hover:text-gray-700">
                        <x-icons.delete-icon></x-icons.delete-icon>
                    </button>
                </div>
            @endforeach
        </div>

        <div>
            <livewire:services.settings.add-settings wire:key="services-settings-add-modal" />
        </div>

        <div class="">
            @auth
                <livewire:services.settings.delete-settings wire:key="services-settings-delete-modal" />
            @endauth
        </div>
    @endif
</div>
