@php
    $injuryTypeLabels = [
        'other' => __('personnel::wizard.options.injury_types.other'),
        'contusion' => __('personnel::wizard.options.injury_types.contusion'),
    ];
    $features = app(\App\Services\Features\FeatureState::class);
    $hasMilitaryDomains = $features->enabled('military_service') || $features->enabled('captivity');
@endphp

<div class="flex flex-col space-y-4">
    @feature('military_service')
    <x-form-card title="{{ __('personnel::wizard.sections.military') }}">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('personnel::common.labels.ranks') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="historyForm.military.rank_id"
                    :model="$this->militaryRankOptions"
                    :search-model="data_get($stepSearchModels, 'searchMilitaryRank', 'searchMilitaryRank')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchMilitaryRank', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('historyForm.military.rank_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="historyForm.military.attitude_to_military_service">{{ __('personnel::common.labels.attitude_to_military_service') }}</x-label>
                <x-livewire-input mode="gray" name="historyForm.military.attitude_to_military_service" wire:model="historyForm.military.attitude_to_military_service"></x-livewire-input>
                @error('historyForm.military.attitude_to_military_service')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
             <div class="flex flex-col">
                <x-label for="historyForm.military.location">{{ __('personnel::common.labels.service_place') }}</x-label>
                <x-livewire-input mode="gray" name="historyForm.military.location" wire:model="historyForm.military.location"></x-livewire-input>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="historyForm.military.given_date">{{ __('personnel::common.labels.given_date') }}</x-label>
                <x-pikaday-input mode="gray" name="historyForm.military.given_date" format="Y-MM-DD" wire:model.live="historyForm.military.given_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('historyForm.military.given_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('historyForm.military.given_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="historyForm.military.start_date">{{ __('personnel::common.labels.start_date') }}</x-label>
                <x-pikaday-input mode="gray" name="historyForm.military.start_date" format="Y-MM-DD" wire:model.live="historyForm.military.start_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('historyForm.military.start_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
            </div>
            <div class="flex flex-col">
                <x-label for="historyForm.military.end_date">{{ __('personnel::common.labels.end_date') }}</x-label>
                <x-pikaday-input mode="gray" name="historyForm.military.end_date" format="Y-MM-DD" wire:model.live="historyForm.military.end_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('historyForm.military.end_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
            </div>
        </div>

        <div class="flex justify-end">
            <x-button  mode="black" wire:click="addMilitary">{{ __('personnel::common.actions.add') }}</x-button>
        </div>

        <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('personnel::common.labels.attitude'), __('personnel::common.labels.service_place'), __('personnel::common.labels.rank'), __('personnel::common.labels.date'), __('personnel::common.labels.action')]">
                        @forelse ($historyForm->militaryList as $key => $msModel)
                            <tr>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ $msModel['attitude_to_military_service'] }}
                                   </span>
                                </x-table.td>
                                 <x-table.td>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $msModel['location'] }}
                                    </span>
                                 </x-table.td>
                                <x-table.td>
                                   <span class="text-sm font-medium text-teal-600">
                                        {{ $this->rankLabel(data_get($msModel, 'rank_id')) ?? '---' }}
                                    </span>
                                </x-table.td>
                                <x-table.td>
                                    <div class="flex items-center space-x-6">
                                        <div class="flex flex-col items-start space-y-1">
                                            <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">{{ __('personnel::common.labels.given_date') }}:</span>
                                            <span class="text-sm font-medium text-teal-600">
                                                {{ $msModel['given_date'] }}
                                            </span>
                                        </div>
                                        @if(array_key_exists('start_date',$msModel))
                                            <div class="flex flex-col items-start space-y-1">
                                                <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">{{ __('personnel::common.labels.start_date') }}:</span>
                                                <span class="text-sm font-medium text-gray-600">
                                                    {{ $msModel['start_date'] }}
                                                </span>
                                            </div>
                                        @endif
                                        @if(array_key_exists('end_date',$msModel))
                                            <div class="flex flex-col items-start space-y-1">
                                                <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">{{ __('personnel::common.labels.end_date') }}:</span>
                                                <span class="text-sm font-medium text-rose-500">
                                                    {{ $msModel['end_date'] }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    <button
                                        x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('personnel::common.messages.remove_data_confirm')), confirmText: @js(__('ui::common.actions.delete')), run: () => $wire.forceDeleteMilitary({{ $key }}) })"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                                    >
                                        <x-icons.force-delete></x-icons.force-delete>
                                    </button>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="flex items-center justify-center py-4">
                                        <span class="font-medium">{{ __('personnel::common.labels.no_information_added') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>
    </x-form-card>

    <x-form-card title="{{ __('personnel::wizard.sections.injuries') }}">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="historyForm.injury.injury_type">{{ __('personnel::common.labels.injury_type') }}</x-label>
                <div class="flex flex-row">
                    <label class="inline-flex items-center w-full px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="historyForm.injury.injury_type" wire:model="historyForm.injury.injury_type" value="other">
                        <span class="ml-2 text-sm font-normal">{{ __('personnel::wizard.options.injury_types.other') }}</span>
                    </label>
                    <label class="inline-flex items-center w-full px-2 py-2 ml-4 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="historyForm.injury.injury_type" wire:model="historyForm.injury.injury_type" value="contusion">
                        <span class="ml-2 text-sm font-normal">{{ __('personnel::wizard.options.injury_types.contusion') }}</span>
                    </label>
                </div>
                @error('historyForm.injury.injury_type')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="historyForm.injury.location">{{ __('personnel::common.labels.location') }}</x-label>
                <x-livewire-input mode="gray" name="historyForm.injury.location" wire:model="historyForm.injury.location"></x-livewire-input>
                @error('historyForm.injury.location')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="historyForm.injury.date_time">{{ __('personnel::common.labels.date') }}</x-label>
                <x-pikaday-input mode="gray" name="historyForm.injury.date_time" format="Y-MM-DD" wire:model.live="historyForm.injury.date_time">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('historyForm.injury.date_time', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('historyForm.injury.date_time')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1">
            <div class="flex flex-col">
                <x-label for="historyForm.injury.description">{{ __('personnel::common.labels.description') }}</x-label>
                <x-textarea mode="gray" name="historyForm.injury.description" placeholder=""
                            wire:model="historyForm.injury.description"></x-textarea>
            </div>
        </div>

        <div class="flex justify-end">
            <x-button  mode="black" wire:click="addInjury">{{ __('personnel::common.actions.add') }}</x-button>
        </div>

        <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('personnel::common.labels.type'), __('personnel::common.labels.location_and_date'), __('personnel::common.labels.description'), __('personnel::common.labels.action')]">
                        @forelse ($historyForm->injuryList as $key => $injuryModel)
                            <tr>
                                <x-table.td>
                                    @if(!empty($injuryModel['injury_type']))
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ $injuryTypeLabels[$injuryModel['injury_type']] ?? ($injuryModel['injury_type'] ?? '---') }}
                                       </span>
                                    @endif
                                </x-table.td>
                                <x-table.td>
                                    <div class="flex flex-col space-y-1 w-max">
                                         <span class="text-sm font-medium text-gray-600 border-b border-dashed border-slate-400">
                                            {{ $injuryModel['location'] }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-900">
                                            @if(! empty($injuryModel['date_time']))
                                                {{ \Carbon\Carbon::parse($injuryModel['date_time'])->format('d.m.Y') }}
                                            @endif
                                        </span>
                                    </div>
                                </x-table.td>
                                <x-table.td>
                                    <div
                                        class="flex items-center space-x-2"
                                        x-data="{showFull: ''}"
                                        @click="showFull = (showFull === '' ? '{{ $key }}' : '')"
                                    >
                                        <span
                                            class="text-sm font-medium text-gray-700 truncate whitespace-normal"
                                            :class="{ 'line-clamp-2': showFull === '' }"
                                        >
                                            {{ $injuryModel['description']}}
                                       </span>
                                    </div>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    <button
                                        x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('personnel::common.messages.remove_data_confirm')), confirmText: @js(__('ui::common.actions.delete')), run: () => $wire.forceDeleteInjury({{ $key }}) })"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                                    >
                                        <x-icons.force-delete></x-icons.force-delete>
                                    </button>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="flex items-center justify-center py-4">
                                        <span class="font-medium">{{ __('personnel::common.labels.no_information_added') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>
    </x-form-card>

    <x-form-card title="{{ __('personnel::wizard.sections.participation_in_war') }}">
        <div class="flex flex-col">
            <x-label for="personnelExtra.participation_in_war">{{ __('personnel::common.labels.description') }}</x-label>
            <x-textarea
                mode="gray"
                name="personnelExtra.participation_in_war"
                placeholder=""
                wire:model.live.debounce.250ms="personnelExtra.participation_in_war"
            ></x-textarea>
            @error('personnelExtra.participation_in_war')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </x-form-card>
    @endfeature

    @feature('captivity')
    <x-form-card title="{{ __('personnel::wizard.sections.captivity') }}">
        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col">
                <x-label for="historyForm.captivity.location">{{ __('personnel::common.labels.location') }}</x-label>
                <x-livewire-input mode="gray" name="historyForm.captivity.location" wire:model="historyForm.captivity.location"></x-livewire-input>
                @error('historyForm.captivity.location')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="historyForm.captivity.condition">{{ __('personnel::common.labels.condition') }}</x-label>
                <x-livewire-input mode="gray" name="historyForm.captivity.condition" wire:model="historyForm.captivity.condition"></x-livewire-input>
                @error('historyForm.captivity.condition')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="historyForm.captivity.taken_captive_date">{{ __('personnel::common.labels.taken_date') }}</x-label>
                <x-pikaday-input mode="gray" name="historyForm.captivity.taken_captive_date" format="Y-MM-DD" wire:model.live="historyForm.captivity.taken_captive_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('historyForm.captivity.taken_captive_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('historyForm.captivity.taken_captive_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="historyForm.captivity.release_date">{{ __('personnel::common.labels.release_date') }}</x-label>
                <x-pikaday-input mode="gray" name="historyForm.captivity.release_date" format="Y-MM-DD" wire:model.live="historyForm.captivity.release_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('historyForm.captivity.release_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
            </div>
        </div>

        <div class="flex justify-end">
            <x-button  mode="black" wire:click="addCaptivity">{{ __('personnel::common.actions.add') }}</x-button>
        </div>

        <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('personnel::common.labels.location'), __('personnel::common.labels.condition'), __('personnel::common.labels.date'), __('personnel::common.labels.action')]">
                        @forelse ($historyForm->captivityList as $key => $captivityModel)
                            <tr>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-600">
                                        {{ $captivityModel['location'] }}
                                    </span>
                                </x-table.td>
                                <x-table.td>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $captivityModel['condition'] }}
                                    </span>
                                </x-table.td>
                                <x-table.td>
                                    <div class="flex items-center space-x-6">
                                        <div class="flex flex-col items-start space-y-1">
                                            <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">{{ __('personnel::common.labels.taken_date')  }}:</span>
                                            <span class="text-sm font-medium text-gray-900">
                                                @if(! empty($captivityModel['taken_captive_date']))
                                                    {{ \Carbon\Carbon::parse($captivityModel['taken_captive_date'])->format('d.m.Y') }}
                                                @endif
                                            </span>
                                        </div>
                                        @if(! empty($captivityModel['release_date']))
                                            <div class="flex flex-col items-start space-y-1">
                                                <span class="text-sm font-medium text-gray-500 border-b border-dashed border-slate-400">{{ __('personnel::common.labels.release_date')  }}:</span>
                                                <span class="text-sm font-medium text-emerald-500">
                                                    {{ \Carbon\Carbon::parse($captivityModel['release_date'])->format('d.m.Y') }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    <button
                                        x-on:click="$dispatch('confirm-action', { tone: 'rose', message: @js(__('personnel::common.messages.remove_data_confirm')), confirmText: @js(__('ui::common.actions.delete')), run: () => $wire.forceDeleteCaptivity({{ $key }}) })"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                                    >
                                        <x-icons.force-delete></x-icons.force-delete>
                                    </button>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="flex items-center justify-center py-4">
                                        <span class="font-medium">{{ __('personnel::common.labels.no_information_added') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>
    </x-form-card>
    @endfeature

    @unless($hasMilitaryDomains)
        <x-form-card title="{{ __('personnel::wizard.sections.military') }}">
            <div class="flex items-center justify-center py-6">
                <span class="font-medium text-gray-500">{{ __('personnel::common.labels.no_information_added') }}</span>
            </div>
        </x-form-card>
    @endunless
</div>
