<x-modal-confirm-lg
    livewire-event-to-open-modal="openFilterWasSet"
    event-to-close-modal="filterSelected"
    :modal-title="__('All filters')"
    :modal-confirm-button-text="__('Search')"
    wire-click="search"
>
    <div class="grid grid-cols-3 lg:grid-cols-5 gap-2">
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
        <div class="flex flex-col">
            <x-ui.select-dropdown
                label="{{ __('Structure') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="filter.structure_id"
                :model="$this->structureOptions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchStructure"
                    wire:model.live.debounce.300ms="searchStructure"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
          </div>
          <div class="flex flex-col">
            <x-ui.select-dropdown
                label="{{ __('Position') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.position_id"
                :model="$this->positions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchPosition"
                    wire:model.live="searchPosition"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
          </div>
        <div class="flex flex-col space-y-1 w-full">
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
            <div class="flex space-x-1 items-center">
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
            <div class="flex space-x-1 items-center">
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
        <div class="flex flex-col">
            <x-ui.select-dropdown
                label="{{ __('Nationality') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.nationality_id"
                :model="$this->nationalityOptions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchNationality"
                    wire:model.live="searchNationality"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
        </div>
        <div class="flex flex-col">
             <x-ui.select-dropdown
                label="{{ __('Born country') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.born_country_id"
                :model="$this->bornCountryOptions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchPreviousNationality"
                    wire:model.live="searchPreviousNationality"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
        </div>
        <div class="flex flex-col">
            <x-ui.select-dropdown
                label="{{ __('City') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.born_city_id"
                :model="$this->cities"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchCity"
                    wire:model.live="searchCity"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
        </div>
        <div class="flex flex-col">
            <x-label for="filter.pin">{{ __('PIN') }}</x-label>
            <x-livewire-input mode="gray" name="filter.pin" wire:model.defer="filter.pin"></x-livewire-input>
        </div>
        <div class="flex flex-col lg:col-span-2">
            <x-label for="filter.rank">{{ __('Rank date') }}</x-label>
            <div class="flex space-x-1 items-center">
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
        <div class="flex flex-col">
             <x-ui.select-dropdown
                label="{{ __('Ranks') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.rank_id"
                :model="$this->rankOptions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchRank"
                    wire:model.live="searchRank"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
        </div>
        <div class="flex flex-col">
            <x-label for="filter.rank_name">{{ __('Rank name') }}</x-label>
            <x-livewire-input mode="gray" name="filter.rank_name" wire:model.defer="filter.rank_name"></x-livewire-input>
        </div>
        <div class="flex flex-col">
             <x-ui.select-dropdown
                label="{{ __('Education degree') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.education_degree_id"
                :model="$this->educationDegreeOptions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchEducationDegree"
                    wire:model.live="searchEducationDegree"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
        </div>
        <div class="flex flex-col">
            <x-label for="filter.specialty">{{ __('Specialty') }}</x-label>
            <x-livewire-input mode="gray" name="filter.specialty" wire:model.defer="filter.specialty"></x-livewire-input>
        </div>
        <div class="flex flex-col">
             <x-ui.select-dropdown
                label="{{ __('Education place') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.educational_institution_id"
                :model="$this->institutionOptions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchInstitution"
                    wire:model.live="searchInstitution"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
        </div>
        <div class="flex flex-col">
             <x-ui.select-dropdown
                label="{{ __('Awards') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.award_id"
                :model="$this->awardOptions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchAward"
                    wire:model.live="searchAward"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
        </div>
        <div class="flex flex-col">
             <x-ui.select-dropdown
                label="{{ __('Punishments') }}"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.defer="filter.punishment_id"
                :model="$this->punishmentOptions"
            >
                <x-livewire-input
                    mode="gray"
                    name="searchPunishment"
                    wire:model.live="searchPunishment"
                    @click.stop="isOpen = true"
                    x-on:input.stop="null"
                    x-on:keyup.stop="null"
                    x-on:keydown.stop="null"
                    x-on:change.stop="null"
                />
            </x-ui.select-dropdown>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-2">
        <div class="flex flex-col">
            <x-label for="filter.punishment_reason">{{ __('Punishment reason') }}</x-label>
            <x-livewire-input mode="gray" name="filter.punishment_reason" wire:model.defer="filter.punishment_reason"></x-livewire-input>
        </div>
        <div class="flex flex-col lg:col-span-2">
            <x-label for="filter.join_work_date">{{ __('Join work date') }}</x-label>
            <div class="flex space-x-1 items-center">
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
            <div class="flex space-x-1 items-center">
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
</x-modal-confirm-lg>
