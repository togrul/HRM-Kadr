@php
    $document = $documentForm->document ?? [];
@endphp

<div class="flex flex-col space-y-4">

    <x-form-card title="ID document">
        <div class="grid grid-cols-2 gap-2 items-end">
            <div class="flex flex-col">
                <div class="flex space-x-2">
                    <x-label for="documentForm.document.pin">{{ __('PIN') }}</x-label>
                    @error('documentForm.document.pin')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
                <x-livewire-input mode="gray" name="documentForm.document.pin" wire:model="documentForm.document.pin"></x-livewire-input>
            </div>
            <div class="flex">
                <x-button mode="black" wire:click="getDataByPin">{{ __('Get data by PIN') }}</x-button>
            </div>
        </div>

        <hr>

        <div wire:loading wire:target="getDataByPin" class='text-input__loading'>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
            <div class='text-input__loading--line'></div>
        </div>

        <div
            wire:loading.remove
            wire:target="getDataByPin"
            class="grid grid-cols-5 gap-2"
        >
            <div class="flex flex-col">
                <x-label for="documentForm.document.nationality_id">{{ __('Nationality') }}</x-label>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="documentForm.document.nationality_id"
                    :model="$this->documentNationalityOptions"
                    :search-model="data_get($stepSearchModels, 'searchDocumentNationality', 'searchDocumentNationality')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchDocumentNationality', __('Search...'))"
                >
                </x-ui.select-dropdown>
                @error('documentForm.document.nationality_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.series">{{ __('Series') }}</x-label>
                <x-livewire-input mode="gray" name="documentForm.document.series" wire:model="documentForm.document.series"></x-livewire-input>
                @error('documentForm.document.series')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.number">{{ __('Number') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="documentForm.document.number" wire:model="documentForm.document.number"></x-livewire-input>
                @error('documentForm.document.number')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.born_country_id">{{ __('Born country') }}</x-label>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="documentForm.document.born_country_id"
                    :model="$this->documentBornCountryOptions"
                    :search-model="data_get($stepSearchModels, 'searchDocumentBornCountry', 'searchDocumentBornCountry')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchDocumentBornCountry', __('Search...'))"
                >
                </x-ui.select-dropdown>
                @error('documentForm.document.born_country_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.born_city_id">{{ __('City') }}</x-label>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="documentForm.document.born_city_id"
                    :model="$this->documentCityOptions"
                    :search-model="data_get($stepSearchModels, 'searchDocumentCity', 'searchDocumentCity')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchDocumentCity', __('Search...'))"
                >
                </x-ui.select-dropdown>
                @error('documentForm.document.born_city_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-4 gap-2 mt-2">
            <div class="flex flex-col">
                <x-label for="documentForm.document.birthplace">{{ __('Birthplace') }}</x-label>
                <x-livewire-input mode="gray" name="documentForm.document.birthplace" wire:model="documentForm.document.birthplace"></x-livewire-input>
            </div>
            <div class="flex flex-col col-span-2">
                <x-label for="documentForm.document.registered_address">{{ __('Registered address') }}</x-label>
                <x-livewire-input mode="gray" name="documentForm.document.registered_address" wire:model="documentForm.document.registered_address"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.is_married">{{ __('Family status') }}</x-label>
                <div class="flex items-center">
                    <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                        <input type="radio" class="form-radio" name="documentForm.document.is_married" wire:model="documentForm.document.is_married" value="0">
                        <span class="ml-2 text-sm font-normal">{{__('Single')}}</span>
                    </label>
                    <label class="inline-flex items-center ml-4 bg-gray-100 rounded shadow-sm py-2 px-2">
                        <input type="radio" class="form-radio" name="documentForm.document.is_married" wire:model="documentForm.document.is_married" value="1">
                        <span class="ml-2 text-sm font-normal">{{__('Married')}}</span>
                    </label>
                </div>
                @error('documentForm.document.is_married')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.military_duty">{{ __('Military duty') }}</x-label>
                <x-livewire-input mode="gray" name="documentForm.document.military_duty" wire:model="documentForm.document.military_duty"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.blood_group">{{ __('Blood group') }}</x-label>
                <x-livewire-input mode="gray" name="documentForm.document.blood_group" wire:model="documentForm.document.blood_group"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.eye_color">{{ __('Eye color') }}</x-label>
                <x-livewire-input mode="gray" name="documentForm.document.eye_color" wire:model="documentForm.document.eye_color"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.height">{{ __('Height') }}</x-label>
                <x-livewire-input mode="gray" name="documentForm.document.height" wire:model="documentForm.document.height"></x-livewire-input>
                @error('documentForm.document.height')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.document_issued_authority">{{ __('Document issued by') }}</x-label>
                <x-livewire-input mode="gray" name="documentForm.document.document_issued_authority" wire:model="documentForm.document.document_issued_authority"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.document.document_issued_date">{{ __('Document issue date') }}</x-label>
                <x-pikaday-input mode="gray" name="documentForm.document.document_issued_date" format="Y-MM-DD" wire:model.live="documentForm.document.document_issued_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('documentForm.document.document_issued_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
            </div>
        </div>
    </x-form-card>

    <x-form-card title="Service cards">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="documentForm.serviceCards.card_number">{{ __('Card number') }}</x-label>
                <x-livewire-input mode="gray" name="documentForm.serviceCards.card_number" wire:model="documentForm.serviceCards.card_number"></x-livewire-input>
                @error('documentForm.serviceCards.card_number')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.serviceCards.given_date">{{ __('Given date') }}</x-label>
                <x-pikaday-input mode="gray" name="documentForm.serviceCards.given_date" format="Y-MM-DD" wire:model.live="documentForm.serviceCards.given_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('documentForm.serviceCards.given_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('documentForm.serviceCards.given_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.serviceCards.valid_date">{{ __('Valid date') }}</x-label>
                <x-pikaday-input mode="gray" name="documentForm.serviceCards.valid_date" format="Y-MM-DD" wire:model.live="documentForm.serviceCards.valid_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('documentForm.serviceCards.valid_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('documentForm.serviceCards.valid_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex justify-start items-end">
                <x-button  mode="black" wire:click="addServiceCard">{{ __('Add') }}</x-button>
            </div>
        </div>
        <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('Card number'),__('Given date'),__('Valid date'),'action','action']">
                        @forelse ($documentForm->serviceCardsList as $keyServiceCard => $valueServiceCard)
                            @php
                                $valid = \Carbon\Carbon::parse($valueServiceCard['valid_date']) >= \Carbon\Carbon::now();
                            @endphp
                            <tr wire:key="service-card-{{ $keyServiceCard }}">
                                <x-table.td>
                                <span class="text-sm font-medium text-zinc-900">
                                    {{ $valueServiceCard['card_number'] }}
                               </span>
                                </x-table.td>
                                <x-table.td>
                                <span class="text-sm font-medium">
                                    {{ $valueServiceCard['given_date'] }}
                               </span>
                                </x-table.td>
                                <x-table.td>
                                <span @class([
                                    'text-sm font-medium',
                                    'text-emerald-500' => $valid,
                                    'text-rose-500' => ! $valid
                                ])>
                                    {{ $valueServiceCard['valid_date'] }}
                               </span>
                                </x-table.td>
                                <x-table.td>
                                    <x-status-badge :$valid></x-status-badge>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    <button
                                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                        wire:click="removeServiceCard({{ $keyServiceCard }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                    >
                                        <x-icons.force-delete></x-icons.force-delete>
                                    </button>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="flex justify-center items-center py-4">
                                        <span class="font-medium">{{ __('No information added') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>
    </x-form-card>

    <x-form-card title="Foreign passports">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="documentForm.passports.serial_number">{{ __('Serial number') }}</x-label>
                <x-livewire-input mode="gray" name="documentForm.passports.serial_number" wire:model="documentForm.passports.serial_number"></x-livewire-input>
                @error('documentForm.passports.serial_number')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.passports.given_date">{{ __('Given date') }}</x-label>
                <x-pikaday-input mode="gray" name="documentForm.passports.given_date" format="Y-MM-DD" wire:model.live="documentForm.passports.given_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('documentForm.passports.given_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('documentForm.passports.given_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="documentForm.passports.valid_date">{{ __('Valid date') }}</x-label>
                <x-pikaday-input mode="gray" name="documentForm.passports.valid_date" format="Y-MM-DD" wire:model.live="documentForm.passports.valid_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('documentForm.passports.valid_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('documentForm.passports.valid_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex justify-start items-end">
                <x-button  mode="black" wire:click="addPassport">{{ __('Add') }}</x-button>
            </div>
        </div>
        <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('Serial number'),__('Given date'),__('Valid date'),'action','action']">
                        @forelse ($documentForm->passportsList as $keyPassport => $valuePassport)
                            @php
                                $validPassport = \Carbon\Carbon::parse($valuePassport['valid_date']) >= \Carbon\Carbon::now();
                            @endphp
                            <tr wire:key="passport-{{ $keyPassport }}">
                                <x-table.td>
                                <span class="text-sm font-medium text-zinc-900">
                                    {{ $valuePassport['serial_number'] }}
                               </span>
                                </x-table.td>
                                <x-table.td>
                                <span class="text-sm font-medium">
                                    {{ $valuePassport['given_date'] }}
                                </span>
                                </x-table.td>
                                <x-table.td>
                                <span @class([
                                    'text-sm font-medium',
                                    'text-emerald-500' => $validPassport,
                                    'text-rose-500' => ! $validPassport
                                ])>
                                    {{ $valuePassport['valid_date'] }}
                               </span>
                                </x-table.td>
                                <x-table.td>
                                    <x-status-badge :valid="$validPassport"></x-status-badge>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    <button
                                        onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                        wire:click="removePassport({{ $keyPassport }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                    >
                                        <x-icons.force-delete></x-icons.force-delete>
                                    </button>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="flex justify-center items-center py-4">
                                        <span class="font-medium">{{ __('No information added') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>
    </x-form-card>
</div>
