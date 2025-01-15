<div class="flex flex-col space-y-4">
    <div class="step-section__title">
        <h1>{{ __('ID document') }}</h1>
    </div>
    <div class="grid grid-cols-2 gap-2 items-end">
        <div class="flex flex-col">
            <div class="flex space-x-2">
                <x-label for="document.pin">{{ __('PIN') }}</x-label>
                @error('document.pin')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>

            <x-livewire-input mode="gray" name="document.pin" wire:model="document.pin"></x-livewire-input>

        </div>
        <div class="flex">
            <x-button  mode="black" wire:click="getDataByPin">{{ __('Get data by PIN') }}</x-button>
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
            <x-select-list  class="w-full" :title="__('Nationality')" mode="gray" :selected="$documentNationalityName" name="documentNationalityId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchPreviousNationality" wire:model.live="searchPreviousNationality"></x-livewire-input>

                <x-select-list-item wire:click="setData('document','nationality_id','documentNationality','---',null)" :selected="'---' == $documentNationalityName"
                                    wire:model='documentNationalityId'>
                    ---
                </x-select-list-item>
                @foreach($nationalities as $nationality)
                    <x-select-list-item wire:click="setData('document','nationality_id','documentNationality','{{ $nationality->currentCountryTranslations->title }}',{{ $nationality->currentCountryTranslations->id }})"
                                        :selected="$nationality->currentCountryTranslations->id === $documentNationalityId" wire:model='documentNationalityId'>
                        {{ $nationality->currentCountryTranslations->title }}
                    </x-select-list-item>
                @endforeach
            </x-select-list>
            @error('document.nationality_id.id')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="document.series">{{ __('Series') }}</x-label>
            <x-livewire-input mode="gray" name="document.series" wire:model="document.series"></x-livewire-input>
            @error('document.series')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="document.number">{{ __('Number') }}</x-label>
            <x-livewire-input mode="gray" type="number" name="document.number" wire:model="document.number"></x-livewire-input>
            @error('document.number')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-select-list  class="w-full" :title="__('Born country')" mode="gray" :selected="$documentBornCountryName" name="documentBornCountryId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchPreviousNationality" wire:model.live="searchPreviousNationality"></x-livewire-input>

                <x-select-list-item wire:click="setData('document','born_country_id','documentBornCountry','---',null)" :selected="'---' == $documentBornCountryName"
                                    wire:model='documentBornCountryId'>
                    ---
                </x-select-list-item>
                @foreach($nationalities as $nationality)
                    <x-select-list-item wire:click="setData('document','born_country_id','documentBornCountry','{{ $nationality->currentCountryTranslations->title }}',{{ $nationality->currentCountryTranslations->id }})"
                                        :selected="$nationality->currentCountryTranslations->id === $documentBornCountryId" wire:model='documentBornCountryId'>
                        {{ $nationality->currentCountryTranslations->title }}
                    </x-select-list-item>
                @endforeach
            </x-select-list>
            @error('document.born_country_id.id')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-select-list  class="w-full" :title="__('City')" mode="gray" :selected="$documentBornCityName" name="documentBornCityId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchCity" wire:model.live="searchCity"></x-livewire-input>

                <x-select-list-item wire:click="setData('document','born_city_id','documentBornCity','---',null)" :selected="'---' == $documentBornCityName"
                                    wire:model='documentBornCityId'>
                    ---
                </x-select-list-item>
                @foreach($cities as $city)
                    <x-select-list-item wire:click="setData('document','born_city_id','documentBornCity','{{ $city->name }}',{{ $city->id }})"
                                        :selected="$city->id === $documentBornCityId" wire:model='documentBornCityId'>
                        {{ $city->name }}
                    </x-select-list-item>
                @endforeach
            </x-select-list>
            @error('document.born_city_id.id')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col col-span-2">
            <x-label for="document.birthplace">{{ __('Birth place') }} ({{ __('Extra') }})</x-label>
            <x-livewire-input mode="gray" name="document.birthplace" wire:model="document.birthplace"></x-livewire-input>
        </div>
        <div class="flex flex-col col-span-2">
            <x-label for="document.registered_address">{{ __('Registered address') }}</x-label>
            <x-livewire-input mode="gray" name="document.registered_address" wire:model="document.registered_address"></x-livewire-input>
        </div>
        <div class="flex flex-col">
            <x-label for="document.is_married">{{ __('Family status') }}</x-label>
            <div class="flex flex-row">
                <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                    <input type="radio" class="form-radio" name="document.is_married" wire:model="document.is_married" value="0">
                    <span class="ml-2 text-sm font-normal">{{__('Single')}}</span>
                </label>
                <label class="inline-flex items-center ml-4 bg-gray-100 rounded shadow-sm py-2 px-2">
                    <input type="radio" class="form-radio" name="document.is_married" wire:model="document.is_married" value="1">
                    <span class="ml-2 text-sm font-normal">{{__('Married')}}</span>
                </label>
            </div>
            @error('document.is_married')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="document.military_duty">{{ __('Military duty') }}</x-label>
            <x-livewire-input mode="gray" name="document.military_duty" wire:model="document.military_duty"></x-livewire-input>
        </div>
        <div class="flex flex-col">
            <x-label for="document.blood_group">{{ __('Blood group') }}</x-label>
            <x-livewire-input mode="gray" name="document.blood_group" wire:model="document.blood_group"></x-livewire-input>
        </div>
        <div class="flex flex-col">
            <x-label for="document.eye_color">{{ __('Eye color') }}</x-label>
            <x-livewire-input mode="gray" name="document.eye_color" wire:model="document.eye_color"></x-livewire-input>
        </div>
        <div class="flex flex-col">
            <x-label for="document.height">{{ __('Height') }}</x-label>
            <x-livewire-input mode="gray" name="document.height" wire:model="document.height"></x-livewire-input>
            @error('document.height')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="document.document_issued_authority">{{ __('Document issued by') }}</x-label>
            <x-livewire-input mode="gray" name="document.document_issued_authority" wire:model="document.document_issued_authority"></x-livewire-input>
        </div>
        <div class="flex flex-col">
            <x-label for="document.document_issued_date">{{ __('Document issue date') }}</x-label>
            <x-pikaday-input mode="gray" name="document.document_issued_date" format="Y-MM-DD" wire:model.live="document.document_issued_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('document.document_issued_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
        </div>
    </div>
    <hr>
    <div class="step-section__title">
        <h1>{{ __('Service cards') }}</h1>
    </div>

    <div class="grid grid-cols-3 gap-2">
        <div class="flex flex-col">
            <x-label for="service_cards.card_number">{{ __('Card number') }}</x-label>
            <x-livewire-input mode="gray" name="service_cards.card_number" wire:model="service_cards.card_number"></x-livewire-input>
            @error('service_cards.card_number')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="service_cards.given_date">{{ __('Given date') }}</x-label>
            <x-pikaday-input mode="gray" name="service_cards.given_date" format="Y-MM-DD" wire:model.live="service_cards.given_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('service_cards.given_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('service_cards.given_date')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="service_cards.valid_date">{{ __('Valid date') }}</x-label>
            <x-pikaday-input mode="gray" name="service_cards.valid_date" format="Y-MM-DD" wire:model.live="service_cards.valid_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('service_cards.valid_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('service_cards.valid_date')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex justify-start items-end">
            <x-button  mode="black" wire:click="addServiceCard">{{ __('Add') }}</x-button>
        </div>
    </div>
    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <x-table.tbl :headers="[__('Card number'),__('Given date'),__('Valid date'),'action']">
                    @forelse ($service_cards_list as $keyServiceCard => $valueServiceCard)
                        @php
                            $valid = \Carbon\Carbon::parse($valueServiceCard['valid_date']) >= \Carbon\Carbon::now();
                        @endphp
                        <tr>
                            <x-table.td>
                                <span class="text-sm font-semibold text-gray-700">
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
                                <div @class([
                                    'px-3 py-1 text-xs rounded-lg font-medium max-w-[120px] flex justify-center items-center space-x-2',
                                    'bg-emerald-50 text-emerald-500' => $valid,
                                    'bg-rose-50 text-rose-500' => ! $valid
                                ])>
                                    <span @class([
                                        'w-2 h-2 rounded-full shadow-sm flex',
                                        'bg-emerald-400' => $valid ,
                                        'bg-rose-400' => ! $valid ,
                                    ])>
                                   </span>
                                    <span>{{ $valid ? __('Active') : __('De-active') }}</span>
                                </div>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                <button
                                    onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                    wire:click="forceDeleteServiceCard({{ $keyServiceCard }})"
                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                >
                                    <x-icons.force-delete></x-icons.force-delete>
                                </button>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
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

    <hr>
    <div class="step-section__title">
        <h1>{{ __('Foreign passports') }}</h1>
    </div>
    <div class="grid grid-cols-3 gap-2">
        <div class="flex flex-col">
            <x-label for="passports.serial_number">{{ __('Serial number') }}</x-label>
            <x-livewire-input mode="gray" name="passports.serial_number" wire:model="passports.serial_number"></x-livewire-input>
            @error('passports.serial_number')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="passports.given_date">{{ __('Given date') }}</x-label>
            <x-pikaday-input mode="gray" name="passports.given_date" format="Y-MM-DD" wire:model.live="passports.given_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('passports.given_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('passports.given_date')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="passports.valid_date">{{ __('Valid date') }}</x-label>
            <x-pikaday-input mode="gray" name="passports.valid_date" format="Y-MM-DD" wire:model.live="passports.valid_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('passports.valid_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
            @error('passports.valid_date')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex justify-start items-end">
            <x-button  mode="black" wire:click="addPassport">{{ __('Add') }}</x-button>
        </div>
    </div>
    {{--list passports--}}
    <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                <x-table.tbl :headers="[__('Serial number'),__('Given date'),__('Valid date'),'action','action']">
                    @forelse ($passports_list as $keyPassport => $valuePassport)
                        @php
                            $validPassport = \Carbon\Carbon::parse($valuePassport['valid_date']) >= \Carbon\Carbon::now();
                        @endphp
                        <tr>
                            <x-table.td>
                                <span class="text-sm font-medium text-gray-700">
                                    {{ $valuePassport['serial_number'] }}
                               </span>
                            </x-table.td>
                            <x-table.td>
                                <span @class([
                                    'text-sm font-medium',
                                    'text-gray-500' => $validPassport,
                                    'text-gray-500' => ! $validPassport
                                ])>
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
                                <div @class([
                                    'px-3 py-1 text-xs rounded-lg font-medium max-w-[120px] flex justify-center items-center space-x-2',
                                    'bg-emerald-50 text-emerald-500' => $validPassport,
                                    'bg-rose-50 text-rose-500' => ! $validPassport
                                ])>
                                    <span @class([
                                        'w-2 h-2 rounded-full shadow-sm flex',
                                        'bg-emerald-400' => $validPassport ,
                                        'bg-rose-400' => ! $validPassport ,
                                    ])>
                                   </span>
                                    <span>{{ $validPassport ? __('Active') : __('De-active') }}</span>
                                </div>
                            </x-table.td>
                            <x-table.td :isButton="true">
                                <button
                                    onclick="confirm('Are you sure you want to remove this data?') || event.stopImmediatePropagation()"
                                    wire:click="forceDeletePassport({{ $keyPassport }})"
                                    class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                                >
                                    <x-icons.force-delete></x-icons.force-delete>
                                </button>
                            </x-table.td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
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
</div>
