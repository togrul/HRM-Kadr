<div x-data class="flex flex-col">
    <div class="flex flex-col justify-between sm:flex-row filter mb-4">
        <div class="flex items-center justify-center space-x-2 action-section">
            {{-- @can('manage-settings') --}}
            <x-button class="space-x-2" mode="primary" x-on:click.prevent="Livewire.dispatch('settingsWasSet')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>{{ __('Add settings') }}</span>
            </x-button>
            {{-- @endcan --}}
        </div>
    </div>


    <div class="mb-6 p-4 bg-white rounded-xl border border-gray-200">
        <div class="flex flex-col gap-3">
            <div class="text-sm font-semibold text-gray-700">{{ __('Candidate status whitelist presets') }}</div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="flex flex-col space-y-2">
                    <span class="text-sm font-medium text-gray-500">{{ __('Military mode status ids') }}</span>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                        <div class="flex flex-col space-y-1">
                            <span class="text-xs text-gray-500">{{ __('Default status') }}</span>
                            <select wire:model.live="candidatePresetSettings.military.default_status"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="all">{{ __('All') }}</option>
                                <option value="deleted">{{ __('Deleted') }}</option>
                                @foreach($candidateStatuses as $statusOption)
                                    <option value="{{ $statusOption['id'] }}">#{{ $statusOption['id'] }} - {{ $statusOption['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm border border-gray-200">
                                <input type="checkbox" wire:model.live="candidatePresetSettings.military.show_deleted_tab"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-gray-700">{{ __('Show deleted tab') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-button mode="slate" class="!py-1 !px-3 !text-xs"
                            wire:click="selectAllCandidateStatuses('military')">{{ __('All') }}</x-button>
                        <x-button mode="slate" class="!py-1 !px-3 !text-xs"
                            wire:click="clearAllCandidateStatuses('military')">{{ __('Clear') }}</x-button>
                    </div>
                    <div class="max-h-48 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-2 space-y-2">
                        @forelse($candidateStatuses as $statusOption)
                            <label class="flex items-center justify-between rounded-md bg-white px-3 py-2 text-sm">
                                <span class="text-gray-700">{{ $statusOption['name'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400">#{{ $statusOption['id'] }}</span>
                                    <input type="checkbox" value="{{ $statusOption['id'] }}"
                                        wire:model.live="candidateStatusWhitelist.military"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </div>
                            </label>
                        @empty
                            <div class="text-xs text-gray-500 px-2 py-1">{{ __('No statuses found for current locale.') }}
                            </div>
                        @endforelse
                    </div>
                    <div class="flex flex-col space-y-2">
                        <span class="text-xs text-gray-500">{{ __('Enabled filters') }}</span>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach($this->candidateFilterOptionsForMode('military') as $filterOption)
                                <label class="inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm border border-gray-200">
                                    <input type="checkbox" value="{{ $filterOption['key'] }}"
                                        wire:model.live="candidateEnabledFilters.military"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-gray-700">{{ $filterOption['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex flex-col space-y-2">
                    <span class="text-sm font-medium text-gray-500">{{ __('Civilian mode status ids') }}</span>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                        <div class="flex flex-col space-y-1">
                            <span class="text-xs text-gray-500">{{ __('Default status') }}</span>
                            <select wire:model.live="candidatePresetSettings.civilian.default_status"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="all">{{ __('All') }}</option>
                                <option value="deleted">{{ __('Deleted') }}</option>
                                @foreach($candidateStatuses as $statusOption)
                                    <option value="{{ $statusOption['id'] }}">#{{ $statusOption['id'] }} - {{ $statusOption['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm border border-gray-200">
                                <input type="checkbox" wire:model.live="candidatePresetSettings.civilian.show_deleted_tab"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-gray-700">{{ __('Show deleted tab') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-button mode="slate" class="!py-1 !px-3 !text-xs"
                            wire:click="selectAllCandidateStatuses('civilian')">{{ __('All') }}</x-button>
                        <x-button mode="slate" class="!py-1 !px-3 !text-xs"
                            wire:click="clearAllCandidateStatuses('civilian')">{{ __('Clear') }}</x-button>
                    </div>
                    <div class="max-h-48 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-2 space-y-2">
                        @forelse($candidateStatuses as $statusOption)
                            <label class="flex items-center justify-between rounded-md bg-white px-3 py-2 text-sm">
                                <span class="text-gray-700">{{ $statusOption['name'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400">#{{ $statusOption['id'] }}</span>
                                    <input type="checkbox" value="{{ $statusOption['id'] }}"
                                        wire:model.live="candidateStatusWhitelist.civilian"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </div>
                            </label>
                        @empty
                            <div class="text-xs text-gray-500 px-2 py-1">{{ __('No statuses found for current locale.') }}
                            </div>
                        @endforelse
                    </div>
                    <div class="flex flex-col space-y-2">
                        <span class="text-xs text-gray-500">{{ __('Enabled filters') }}</span>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach($this->candidateFilterOptionsForMode('civilian') as $filterOption)
                                <label class="inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm border border-gray-200">
                                    <input type="checkbox" value="{{ $filterOption['key'] }}"
                                        wire:model.live="candidateEnabledFilters.civilian"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-gray-700">{{ $filterOption['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-xs text-gray-500">
                {{ __('Pick statuses directly by name. Empty selection means all statuses are visible.') }}
            </div>
            <div>
                <x-button mode="primary" wire:click="saveCandidateStatusWhitelist">
                    {{ __('Save presets') }}
                </x-button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
        @foreach ($settings as $key => $settingValue)
            <div class="flex space-x-2 justify-between items-end w-full">
                <div class="flex flex-col space-y-2 w-full">
                    <span class="text-sm font-medium text-gray-500">{{ __($settingValue->name) }}</span>
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

    {{-- @can('manage-settings') --}}
    <div>
        <livewire:services.settings.add-settings wire:key="services-settings-add-modal" />
    </div>
    {{-- @endcan --}}

    <div class="">
        @auth
            <livewire:services.settings.delete-settings wire:key="services-settings-delete-modal" />
        @endauth
    </div>
</div>
