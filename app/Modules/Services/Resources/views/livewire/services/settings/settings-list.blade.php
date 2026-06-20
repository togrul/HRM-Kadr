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
        @php
            $chiefOptions = $this->chiefPersonnelOptions();
        @endphp

        <section class="mb-6 rounded-[28px] bg-[#f4f7fb] px-6 py-7 shadow-[0_24px_70px_-56px_rgba(15,23,42,0.4)]">
            <div class="mb-7 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <h2 class="text-[26px] font-semibold leading-tight tracking-tight text-slate-950">Rəhbər və Həvalə</h2>
                    <p class="mt-1 text-base font-medium text-slate-500">Sistem üzrə imza səlahiyyətləri və müvəqqəti təyinatlar.</p>
                </div>

                <button type="button" x-on:click.prevent="Livewire.dispatch('settingsWasSet')"
                    class="inline-flex h-12 items-center justify-center gap-3 rounded-xl bg-slate-950 px-6 text-base font-semibold text-white shadow-[0_18px_36px_-24px_rgba(15,23,42,0.9)] transition hover:bg-slate-800">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    Tənzimləmə əlavə et
                </button>
            </div>

            <div class="grid gap-7 xl:grid-cols-[360px_minmax(0,1fr)]">
                <div class="space-y-6">
                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-[0_20px_45px_-38px_rgba(15,23,42,0.55)]">
                        <div class="flex items-center gap-5">
                            <div class="flex h-[82px] w-[82px] shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-400">
                                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <h3 class="break-words text-xl font-semibold leading-tight text-slate-950">
                                    {{ data_get($chiefSnapshot, 'fullname') ?: 'Rəhbər təyin edilməyib' }}
                                </h3>
                                <p class="mt-1 text-sm font-medium text-slate-500">
                                    {{ data_get($chiefSnapshot, 'title') ?: 'Vəzifə/rütbə məlumatı yoxdur' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-7 flex items-center justify-between border-t border-slate-100 pt-5">
                            @if(data_get($chiefSnapshot, 'mode') === 'delegated')
                                <span class="rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-tight text-amber-700">
                                    Vəzifəni icra edir
                                </span>
                            @elseif(data_get($chiefSnapshot, 'mode') === 'legacy')
                                <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-tight text-slate-500">
                                    Köhnə ayarlar
                                </span>
                            @else
                                <span class="rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-tight text-emerald-700">
                                    Daimi rəhbər
                                </span>
                            @endif

                            <svg class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3 19 6v5c0 5-3.1 8.2-7 10-3.9-1.8-7-5-7-10V6l7-3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                <path d="m9 12 2 2 4-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-[0_20px_45px_-38px_rgba(15,23,42,0.55)]">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="m12 3 2.2 6.1h6.3l-5.1 3.8 1.9 6.1-5.3-3.7L6.7 19l1.9-6.1-5.1-3.8h6.3L12 3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold uppercase tracking-tight text-slate-950">Daimi rəhbər</h3>
                        </div>

                        <p class="mt-5 text-base leading-7 text-slate-500">Boş saxlanarsa ən yüksək təsdiq sırasına malik aktiv əməkdaş avtomatik seçilir.</p>

                        <label class="mt-8 block">
                            <span class="mb-3 block text-xs font-semibold uppercase tracking-tight text-slate-400">Rəhbər seçimi</span>
                            <x-ui.filter-native-select wire:model.live="chiefPersonnelId" class="!h-12 !rounded-xl !border !border-slate-200 !bg-white !px-4 !text-sm !font-semibold !text-slate-950 !shadow-sm !ring-0 hover:!bg-white focus:!border-slate-300 focus:!ring-2 focus:!ring-slate-200">
                                <option value="">Avtomatik seç</option>
                                @foreach($chiefOptions as $option)
                                    <option value="{{ $option['id'] }}">
                                        {{ $option['label'] }}@if($option['position']) - {{ $option['position'] }}@endif
                                    </option>
                                @endforeach
                            </x-ui.filter-native-select>
                        </label>

                        <button type="button" wire:click="saveChiefPersonnel"
                            class="mt-5 flex h-12 w-full items-center justify-center rounded-xl bg-emerald-700 text-base font-semibold text-white shadow-[0_16px_32px_-22px_rgba(4,120,87,0.9)] transition hover:bg-emerald-800">
                            Yadda saxla
                        </button>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-[0_20px_45px_-38px_rgba(15,23,42,0.55)]">
                        <h3 class="text-base font-semibold uppercase tracking-tight text-slate-950">Əmsallar</h3>

                        <div class="mt-6 space-y-4">
                            @forelse($coefficientSettings as $coefficientSetting)
                                @php
                                    $coefficientIndex = $coefficientSettingIndexes[$coefficientSetting->name] ?? null;
                                    $iconName = $this->settingIconName((string) $coefficientSetting->name);
                                @endphp

                                <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center text-slate-400">
                                        @if($iconName === 'briefcase')
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M9 7V5.8C9 4.8 9.8 4 10.8 4h2.4C14.2 4 15 4.8 15 5.8V7M5 9.5h14M6 7h12a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="m12 5 8 4-8 4-8-4 8-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                                <path d="M7 11.5v4c0 1.4 2.2 2.5 5 2.5s5-1.1 5-2.5v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                            </svg>
                                        @endif
                                    </div>

                                    <span class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-700">{{ $this->resolveSettingLabel((string) $coefficientSetting->name) }}</span>

                                    @if($coefficientIndex !== null)
                                        <x-livewire-input mode="gray" type="number" name="setting.{{ $coefficientIndex }}.value"
                                            wire:model.live="setting.{{ $coefficientIndex }}.value"
                                            class="!mt-0 !h-9 !w-20 !rounded-lg !border !border-transparent !bg-white !px-2 !text-right !text-sm !font-semibold !text-slate-950 !shadow-sm !ring-0 focus:!border-slate-300 focus:!ring-2 focus:!ring-slate-200">
                                        </x-livewire-input>
                                    @endif

                                    <button type="button" wire:click.prevent="setDeleteSettings({{ $coefficientSetting->id }})"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-rose-500"
                                        aria-label="{{ $this->resolveSettingLabel((string) $coefficientSetting->name) }} sil">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m7 7 10 10M17 7 7 17" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" />
                                        </svg>
                                    </button>
                                </div>
                            @empty
                                <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm font-medium text-slate-500">Əmsal ayarı əlavə edilməyib.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="flex min-h-[720px] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-[0_20px_45px_-38px_rgba(15,23,42,0.55)]">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-8 py-7 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 6h10l6 6-6 6H4V6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                                    <path d="m9 10 4 4m0-4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold uppercase tracking-tight text-slate-950">Müvəqqəti həvalə</h3>
                            </div>
                        </div>

                        <span class="text-sm font-semibold text-slate-500">Aktiv sessiya yoxdur</span>
                    </div>

                    @if(data_get($chiefSnapshot, 'mode') === 'delegated')
                        <div class="mx-6 mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800">
                            {{ data_get($chiefSnapshot, 'permanent_chief_fullname') }} rəhbərin səlahiyyəti
                            {{ data_get($chiefSnapshot, 'delegation_starts_at') }}
                            @if(data_get($chiefSnapshot, 'delegation_ends_at'))
                                - {{ data_get($chiefSnapshot, 'delegation_ends_at') }}
                            @endif
                            tarixləri üçün həvalə edilib.
                        </div>
                    @endif

                    <div class="grid flex-1 content-start gap-6 px-8 py-10 lg:grid-cols-2">
                        <p class="text-base font-medium leading-7 text-slate-500 lg:col-span-2">Tarix aralığında sənədlərdə imza sahibi əvəz edən şəxs olacaq.</p>

                        <label class="block lg:col-span-2">
                            <span class="mb-3 block text-xs font-semibold uppercase tracking-tight text-slate-400">Vəzifəni icra edən əməkdaş</span>
                            <x-ui.filter-native-select wire:model.defer="chiefDelegationForm.delegate_personnel_id" class="!h-12 !rounded-xl !border !border-slate-200 !bg-slate-50 !px-4 !text-sm !font-semibold !text-slate-950 !shadow-sm !ring-0 hover:!bg-white focus:!border-slate-300 focus:!bg-white focus:!ring-2 focus:!ring-slate-200">
                                <option value="">Əməkdaş seç</option>
                                @foreach($chiefOptions as $option)
                                    <option value="{{ $option['id'] }}">
                                        {{ $option['label'] }}@if($option['position']) - {{ $option['position'] }}@endif
                                    </option>
                                @endforeach
                            </x-ui.filter-native-select>
                            @error('chiefDelegationForm.delegate_personnel_id')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="mb-3 block text-xs font-semibold uppercase tracking-tight text-slate-400">Başlama tarixi</span>
                            <x-pikaday-input mode="gray" name="chiefDelegationForm.starts_at" format="Y-MM-DD" wire:model.live="chiefDelegationForm.starts_at" placeholder="dd.mm.yyyy"
                                class="!mt-0 !h-12 !rounded-xl !border !border-slate-200 !bg-slate-50 !px-4 !text-sm !font-semibold !text-slate-950 !shadow-sm !ring-0 focus:!border-slate-300 focus:!bg-white focus:!ring-2 focus:!ring-slate-200">
                            </x-pikaday-input>
                            @error('chiefDelegationForm.starts_at')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="mb-3 block text-xs font-semibold uppercase tracking-tight text-slate-400">Bitmə tarixi</span>
                            <x-pikaday-input mode="gray" name="chiefDelegationForm.ends_at" format="Y-MM-DD" wire:model.live="chiefDelegationForm.ends_at" placeholder="dd.mm.yyyy"
                                class="!mt-0 !h-12 !rounded-xl !border !border-slate-200 !bg-slate-50 !px-4 !text-sm !font-semibold !text-slate-950 !shadow-sm !ring-0 focus:!border-slate-300 focus:!bg-white focus:!ring-2 focus:!ring-slate-200">
                            </x-pikaday-input>
                            @error('chiefDelegationForm.ends_at')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="mb-3 block text-xs font-semibold uppercase tracking-tight text-slate-400">Səbəb</span>
                            <x-livewire-input mode="gray" name="chiefDelegationForm.reason" wire:model.defer="chiefDelegationForm.reason" placeholder="Məzuniyyət, ezamiyyət..."
                                class="!mt-0 !h-12 !rounded-xl !border !border-slate-200 !bg-slate-50 !px-4 !text-sm !font-semibold !text-slate-950 !shadow-sm !ring-0 focus:!border-slate-300 focus:!bg-white focus:!ring-2 focus:!ring-slate-200">
                            </x-livewire-input>
                            @error('chiefDelegationForm.reason')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="mb-3 block text-xs font-semibold uppercase tracking-tight text-slate-400">Əsas sənəd</span>
                            <x-livewire-input mode="gray" name="chiefDelegationForm.basis_document" wire:model.defer="chiefDelegationForm.basis_document" placeholder="Əmr nömrəsi və ya əsas"
                                class="!mt-0 !h-12 !rounded-xl !border !border-slate-200 !bg-slate-50 !px-4 !text-sm !font-semibold !text-slate-950 !shadow-sm !ring-0 focus:!border-slate-300 focus:!bg-white focus:!ring-2 focus:!ring-slate-200">
                            </x-livewire-input>
                            @error('chiefDelegationForm.basis_document')
                                <x-validation>{{ $message }}</x-validation>
                            @enderror
                        </label>
                    </div>

                    <div class="mt-auto flex justify-end gap-4 border-t border-slate-100 bg-slate-50/70 px-8 py-7">
                        <button type="button" wire:click="resetChiefDelegationForm" class="h-12 rounded-xl px-8 text-base font-semibold text-slate-600 hover:bg-white">
                            Ləğv et
                        </button>
                        <button type="button" wire:click="createChiefDelegation"
                            class="h-12 rounded-xl bg-slate-950 px-9 text-base font-semibold text-white shadow-[0_18px_34px_-24px_rgba(15,23,42,0.95)] transition hover:bg-slate-800">
                            Həvalə yarat
                        </button>
                    </div>
                </div>
            </div>

            @if($chiefDelegations->isNotEmpty())
                <div class="border-t border-zinc-100 px-5 pb-5">
                    <h3 class="pt-5 text-sm font-semibold uppercase tracking-[0.16em] text-zinc-400">Aktiv həvalələr</h3>
                    <div class="mt-3 grid gap-3">
                        @foreach($chiefDelegations as $delegation)
                            <div class="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-950">
                                        {{ $delegation->delegate?->fullname }} - {{ $delegation->chief?->fullname }} əvəzinə
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-zinc-500">
                                        {{ optional($delegation->starts_at)->format('Y-m-d') }}
                                        @if($delegation->ends_at)
                                            - {{ optional($delegation->ends_at)->format('Y-m-d') }}
                                        @endif
                                        @if($delegation->basis_document)
                                            · {{ $delegation->basis_document }}
                                        @endif
                                    </p>
                                </div>

                                <button type="button" wire:click="revokeChiefDelegation({{ $delegation->id }})" class="rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-tight text-zinc-700 shadow-sm hover:bg-zinc-100">
                                    Dayandır
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

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
