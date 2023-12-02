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

<div wire:loading wire:target="getDataByPin" class='text-input__loading'>
    <div class='text-input__loading--line'></div>
    <div class='text-input__loading--line'></div>
    <div class='text-input__loading--line'></div>
    <div class='text-input__loading--line'></div>
    <div class='text-input__loading--line'></div>
    <div class='text-input__loading--line'></div>
    <div class='text-input__loading--line'></div>
</div>

<div wire:loading.remove wire:target="getDataByPin" class="grid grid-cols-4 gap-2">
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
    <div class="flex flex-col col-span-2">
        <x-label for="document.document_issued_authority">{{ __('Document issued by') }}</x-label>
        <x-livewire-input mode="gray" name="document.document_issued_authority" wire:model="document.document_issued_authority"></x-livewire-input>
    </div>
    <div class="flex flex-col col-span-2">
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
