<div>
  <x-modal-confirm-lg
    livewire-event-to-open-modal="openFilterWasSet"
    event-to-close-modal="filterSelected"
    :modal-title="__('All filters')"
    :modal-confirm-button-text="__('Search')"
    wire-click="search"
>
    <div wire:key="filter-detail-open-{{ $openSequence }}">
    <div class="grid grid-cols-3 gap-2 lg:grid-cols-5">
        <div class="flex flex-col">
            <x-label for="filter.surname">{{ __('Surname') }}</x-label>
            <x-livewire-input mode="gray" name="filter.surname" wire:model.defer="filter.surname"></x-livewire-input>
        </div>
        <div class="flex flex-col">
            <x-label for="filter.name">{{ __('Name') }}</x-label>
            <x-livewire-input mode="gray" name="filter.name" wire:model.defer="filter.name"></x-livewire-input>
        </div>
        <div class="flex flex-col">
            <x-label for="filter.patronymic">{{ __('Patronymic') }}</x-label>
            <x-livewire-input mode="gray" name="filter.patronymic" wire:model.defer="filter.patronymic"></x-livewire-input>
        </div>
        <x-ui.filter-select
            :label="__('Structure')"
            :options="$this->structureOptions"
            searchModel="searchStructure"
            load-on-open="structure"
            load-on-focus="structure" 
            wire:model.live="filter.structure_id"
        />
        <x-ui.filter-select
            :label="__('Position')"
            :options="$this->positions"
            searchModel="searchPosition"
            load-on-open="position"
            load-on-focus="position"
            wire:model.defer="filter.position_id"
        />
        <div class="flex flex-col w-full space-y-1">
            <x-label for="filter.gender">{{ __('Gender') }}</x-label>
            <div class="flex flex-col">
                @foreach(\App\Enums\GenderEnum::genderOptions() as $value => $label)
                    <x-ui.radio
                        model="filter.gender"
                        :$value
                        :$label
                    />
                @endforeach
            </div>
          </div>
          <div class="flex flex-col lg:col-span-2">
            <x-label for="filter.birthdate">{{ __('Birthdate') }}</x-label>
            <div class="flex items-center space-x-1">
                <x-pikaday-input mode="gray" name="filter.birthdate.min" format="Y-MM-DD" wire:model.live="filter.birthdate.min">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('filter.birthdate.min', $el.value);
                      }
                    </x-slot>
                </x-pikaday-input>
                <span>-</span>
                <x-pikaday-input mode="gray" name="filter.birthdate.max" format="Y-MM-DD" wire:model.live="filter.birthdate.max">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('filter.birthdate.max', $el.value);
                      }
                    </x-slot>
                </x-pikaday-input>
            </div>
        </div>
        <div class="flex flex-col lg:col-span-2">
            <x-label for="filter.birthdate">{{ __('Age') }}</x-label>
            <div class="flex items-center space-x-1">
                <x-livewire-input mode="gray" name="filter.age.min" wire:model.defer="filter.age.min"></x-livewire-input>
                <span>-</span>
                <x-livewire-input mode="gray" name="filter.age.max" wire:model.defer="filter.age.max"></x-livewire-input>
            </div>
        </div>
        <div class="flex flex-col">
            <x-label for="filter.tabel_no">{{ __('Tabel #') }}</x-label>
            <x-livewire-input mode="gray" name="filter.tabel_no" wire:model.defer="filter.tabel_no"></x-livewire-input>
        </div>
        <div class="flex flex-col gap-1">
            <x-label for="filter.is_married">{{ __('Family status') }}</x-label>
            <div class="flex flex-row">
                 <x-ui.radio
                    model="filter.is_married"
                    value="0"
                    label="Single"
                />
                <x-ui.radio
                    model="filter.is_married"
                    value="1"
                    label="Married"
                />
            </div>
        </div>
        <x-ui.filter-select
            :label="__('Nationality')"
            :options="$this->nationalityOptions"
            searchModel="searchNationality"
            load-on-open="nationality"
            load-on-focus="nationality"
            wire:model.defer="filter.nationality_id"
        />
        <x-ui.filter-select
            :label="__('Born country')"
            :options="$this->bornCountryOptions"
            searchModel="searchPreviousNationality"
            load-on-open="bornCountry"
            load-on-focus="bornCountry"
            wire:model.defer="filter.born_country_id"
        />
        <x-ui.filter-select
            :label="__('City')"
            :options="$this->cities"
            searchModel="searchCity"
            load-on-open="city"
            load-on-focus="city"
            wire:model.defer="filter.born_city_id"
        />
        <div class="flex flex-col">
            <x-label for="filter.pin">{{ __('PIN') }}</x-label>
            <x-livewire-input mode="gray" name="filter.pin" wire:model.defer="filter.pin"></x-livewire-input>
        </div>
        <div class="flex flex-col lg:col-span-2">
            <x-label for="filter.rank">{{ __('Rank date') }}</x-label>
            <div class="flex items-center space-x-1">
                <x-pikaday-input mode="gray" name="filter.rank.min" format="Y-MM-DD" wire:model.live="filter.rank.min">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('filter.rank.min', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                <span>-</span>
                <x-pikaday-input mode="gray" name="filter.rank.max" format="Y-MM-DD" wire:model.live="filter.rank.max">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('filter.rank.max', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
            </div>
        </div>
        <x-ui.filter-select
            :label="__('Ranks')"
            :options="$this->rankOptions"
            searchModel="searchRank"
            load-on-open="rank"
            load-on-focus="rank"
            wire:model.defer="filter.rank_id"
        />
        <div class="flex flex-col">
            <x-label for="filter.rank_name">{{ __('Rank name') }}</x-label>
            <x-livewire-input mode="gray" name="filter.rank_name" wire:model.defer="filter.rank_name"></x-livewire-input>
        </div>
        <x-ui.filter-select
            :label="__('Education degree')"
            :options="$this->educationDegreeOptions"
            searchModel="searchEducationDegree"
            load-on-open="educationDegree"
            load-on-focus="educationDegree"
            wire:model.defer="filter.education_degree_id"
        />
        <div class="flex flex-col">
            <x-label for="filter.specialty">{{ __('Specialty') }}</x-label>
            <x-livewire-input mode="gray" name="filter.specialty" wire:model.defer="filter.specialty"></x-livewire-input>
        </div>
        <x-ui.filter-select
            :label="__('Education place')"
            :options="$this->institutionOptions"
            searchModel="searchInstitution"
            load-on-open="institution"
            load-on-focus="institution"
            wire:model.defer="filter.educational_institution_id"
        />
        <x-ui.filter-select
            :label="__('Awards')"
            :options="$this->awardOptions"
            searchModel="searchAward"
            load-on-open="award"
            load-on-focus="award"
            wire:model.defer="filter.award_id"
        />
        <x-ui.filter-select
            :label="__('Punishments')"
            :options="$this->punishmentOptions"
            searchModel="searchPunishment"
            load-on-open="punishment"
            load-on-focus="punishment"
            wire:model.defer="filter.punishment_id"
        />
    </div>
    <div class="grid grid-cols-1 gap-2 lg:grid-cols-5">
        <div class="flex flex-col">
            <x-label for="filter.punishment_reason">{{ __('Punishment reason') }}</x-label>
            <x-livewire-input mode="gray" name="filter.punishment_reason" wire:model.defer="filter.punishment_reason"></x-livewire-input>
        </div>
        <div class="flex flex-col lg:col-span-2">
            <x-label for="filter.join_work_date">{{ __('Join work date') }}</x-label>
            <div class="flex items-center space-x-1">
                <x-pikaday-input mode="gray" name="filter.join_work_date.min" format="Y-MM-DD" wire:model.live="filter.join_work_date.min">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('filter.join_work_date.min', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                <span>-</span>
                <x-pikaday-input mode="gray" name="filter.join_work_date.max" format="Y-MM-DD" wire:model.live="filter.join_work_date.max">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('filter.join_work_date.max', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
            </div>
        </div>
        <div class="flex flex-col lg:col-span-2">
            <x-label for="filter.rank">{{ __('Leave work date') }}</x-label>
            <div class="flex items-center space-x-1">
                <x-pikaday-input mode="gray" name="filter.leave_work_date.min" format="Y-MM-DD" wire:model.live="filter.leave_work_date.min">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('filter.leave_work_date.min', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                <span>-</span>
                <x-pikaday-input mode="gray" name="filter.leave_work_date.max" format="Y-MM-DD" wire:model.live="filter.leave_work_date.max">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('filter.leave_work_date.max', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
            </div>
        </div>
    </div>
    </div>
  </x-modal-confirm-lg>
</div>
