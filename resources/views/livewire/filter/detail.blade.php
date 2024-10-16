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
            <x-select-list class="w-full" :title="__('Structure')" mode="gray" :selected="$structureName" name="structureId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchStructure" wire:model.live="searchStructure"></x-livewire-input>

                <x-select-list-item wire:click="setData('filter','structure_id','structure','---',null)" :selected="'---' == $structureName"
                  wire:model='filter.structure_id'>
                  ---
                </x-select-list-item>
                @foreach($structures as $structure)
                <x-select-list-item wire:click="setData('filter','structure_id','structure','{{ $structure->name }}',{{ $structure->id }})"
                  :selected="$structure->id === $structureId" wire:model='filter.structure_id'>
                  {{ $structure->name }}
                </x-select-list-item>
                @endforeach
            </x-select-list>
          </div>
          <div class="flex flex-col">
            <x-select-list class="w-full" :title="__('Position')" mode="gray" :selected="$positionName" name="positionId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchPosition" wire:model.live="searchPosition"></x-livewire-input>

                <x-select-list-item wire:click="setData('filter','position_id','position','---',null)" :selected="'---' == $positionName"
                  wire:model='filter.position_id'>
                  ---
                </x-select-list-item>
                @foreach($positions as $position)
                <x-select-list-item wire:click="setData('filter','position_id','position','{{ $position->name }}',{{ $position->id }})"
                  :selected="$position->id === $positionId" wire:model='filter.position_id'>
                  {{ $position->name }}
                </x-select-list-item>
                @endforeach
            </x-select-list>
          </div>
        <div class="flex flex-col space-y-1 w-full">
            <x-label for="filter.gender">{{ __('Gender') }}</x-label>
            <div class="flex flex-col">
              <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                  <input type="radio"
                         class="form-radio"
                         name="filter.gender"
                         wire:model="filter.gender"
                         value="2">
                <span class="ml-2 text-sm font-normal">{{__('Woman')}}</span>
              </label>
              <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                <input type="radio" class="form-radio" name="filter.gender" wire:model="filter.gender" value="1">
                <span class="ml-2 text-sm font-normal">{{__('Man')}}</span>
              </label>
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
        <div class="flex flex-col">
            <x-label for="filter.is_married">{{ __('Family status') }}</x-label>
            <div class="flex flex-row">
              <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                <input type="radio" class="form-radio" name="filter.is_married" wire:model="filter.is_married" value="0">
                <span class="ml-2 text-sm font-normal">{{__('Single')}}</span>
              </label>
              <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                <input type="radio" class="form-radio" name="filter.is_married" wire:model="filter.is_married" value="1">
                <span class="ml-2 text-sm font-normal">{{__('Married')}}</span>
              </label>
            </div>
        </div>
        <div class="flex flex-col">
            <x-select-list  class="w-full" :title="__('Nationality')" mode="gray" :selected="$nationalityName" name="nationalityId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchNationality" wire:model.live="searchNationality"></x-livewire-input>

                <x-select-list-item wire:click="setData('filter','nationality_id','nationality','---',null)" :selected="'---' == $nationalityName"
                  wire:model='nationalityId'>
                  ---
                </x-select-list-item>
                @foreach($nationalities as $nationality)
                    <x-select-list-item
                        wire:click="setData('filter','nationality_id','nationality','{{ $nationality->currentCountryTranslations->title }}',{{ $nationality->currentCountryTranslations->id }})"
                        :selected="$nationality->currentCountryTranslations->id === $nationalityId" wire:model='nationalityId'>
                      {{ $nationality->currentCountryTranslations->title }}
                    </x-select-list-item>
                @endforeach
              </x-select-list>
        </div>
        <div class="flex flex-col">
            <x-select-list  class="w-full" :title="__('Born country')" mode="gray" :selected="$bornCountryName" name="bornCountryId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchPreviousNationality" wire:model.live="searchPreviousNationality"></x-livewire-input>

                <x-select-list-item wire:click="setData('filter','born_country_id','bornCountry','---',null)" :selected="'---' == $bornCountryName"
                  wire:model='bornCountryId'>
                  ---
                </x-select-list-item>
                @foreach($nationalities as $nationality)
                    <x-select-list-item wire:click="setData('filter','born_country_id','bornCountry','{{ $nationality->currentCountryTranslations->title }}',{{ $nationality->currentCountryTranslations->id }})"
                      :selected="$nationality->currentCountryTranslations->id === $bornCountryId" wire:model='bornCountryId'>
                      {{ $nationality->currentCountryTranslations->title }}
                    </x-select-list-item>
                @endforeach
              </x-select-list>
        </div>
        <div class="flex flex-col">
            <x-select-list  class="w-full" :title="__('City')" mode="gray" :selected="$bornCityName" name="bornCityId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchCity" wire:model.live="searchCity"></x-livewire-input>

                <x-select-list-item wire:click="setData('filter','born_city_id','bornCity','---',null)" :selected="'---' == $bornCityName"
                  wire:model='bornCityId'>
                  ---
                </x-select-list-item>
                @foreach($cities as $city)
                <x-select-list-item wire:click="setData('filter','born_city_id','bornCity','{{ $city->name }}',{{ $city->id }})"
                  :selected="$city->id === $bornCityId" wire:model='bornCityId'>
                  {{ $city->name }}
                </x-select-list-item>
                @endforeach
              </x-select-list>
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
            <x-select-list class="w-full" :title="__('Ranks')" mode="gray" :selected="$rankName" name="rankId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchRank" wire:model.live="searchRank"></x-livewire-input>

                <x-select-list-item wire:click="setData('filter','rank_id','rank','---',null)" :selected="'---' == $rankName"
                  wire:model='filter.rank_id'>
                  ---
                </x-select-list-item>
                @foreach($rankModel as $rnk)
                    <x-select-list-item wire:click="setData('filter','rank_id','rank','{{ $rnk->name }}',{{ $rnk->id }})"
                    :selected="$rnk->id === $rankId" wire:model='filter.rank_id'>
                        {{ $rnk->name }}
                    </x-select-list-item>
                @endforeach
            </x-select-list>
        </div>
        <div class="flex flex-col">
            <x-label for="filter.rank_name">{{ __('Rank name') }}</x-label>
            <x-livewire-input mode="gray" name="filter.rank_name" wire:model.defer="filter.rank_name"></x-livewire-input>
        </div>
        <div class="flex flex-col">
            <x-select-list class="w-full" :title="__('Education degree')" mode="gray" :selected="$educationDegreeName" name="educationDegreeId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchEducationDegree" wire:model.live="searchEducationDegree"></x-livewire-input>

                <x-select-list-item wire:click="setData('filter','education_degree_id','educationDegree','---',null)" :selected="'---' == $educationDegreeName"
                  wire:model='filter.education_degree_id.id'>
                  ---
                </x-select-list-item>
                @foreach($education_degrees as $eduDegree)
                <x-select-list-item wire:click="setData('filter','education_degree_id','educationDegree','{{ $eduDegree->title }}',{{ $eduDegree->id }})"
                  :selected="$eduDegree->id === $educationDegreeId" wire:model='filter.education_degree_id.id'>
                  {{ $eduDegree->title }}
                </x-select-list-item>
                @endforeach
            </x-select-list>
        </div>
        <div class="flex flex-col">
            <x-label for="filter.specialty">{{ __('Specialty') }}</x-label>
            <x-livewire-input mode="gray" name="filter.specialty" wire:model.defer="filter.specialty"></x-livewire-input>
        </div>
        <div class="flex flex-col">
            <x-select-list class="w-full" :title="__('Education place')" mode="gray" :selected="$institutionName" name="educational_institution_id">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchInstitution" wire:model.live="searchInstitution"></x-livewire-input>

                <x-select-list-item wire:click="setData('filter','educational_institution_id','institution','---',null)" :selected="'---' == $institutionName"
                  wire:model='filter.educational_institution_id.id'>
                  ---
                </x-select-list-item>
                @foreach($institutions as $institution)
                <x-select-list-item wire:click="setData('filter','educational_institution_id','institution','{{ $institution->name }}',{{ $institution->id }})"
                  :selected="$institution->id === $institutionId" wire:model='filter.educational_institution_id.id'>
                    {{ $institution->name }}
                </x-select-list-item>
                @endforeach
            </x-select-list>
        </div>
        <div class="flex flex-col">
            <x-select-list class="w-full" :title="__('Awards')" mode="gray" :selected="$awardName" name="awardId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchAward" wire:model.live="searchAward"></x-livewire-input>

                <x-select-list-item wire:click="setData('filter','award_id','award','---',null)" :selected="'---' == $awardName"
                  wire:model='filter.award_id'>
                  ---
                </x-select-list-item>
                    @foreach($awardModel as $awd)
                    <x-select-list-item wire:click="setData('filter','award_id','award','{{ $awd->name }}',{{ $awd->id }})"
                    :selected="$awd->id === $awardId" wire:model='filter.award_id'>
                        {{ $awd->name }}
                    </x-select-list-item>
                    @endforeach
            </x-select-list>
        </div>
        <div class="flex flex-col">
            <x-select-list class="w-full" :title="__('Punishments')" mode="gray" :selected="$punishmentName" name="punishmentId">
                <x-livewire-input  @click.stop="open = true" mode="gray" name="searchPunishment" wire:model.live="searchPunishment"></x-livewire-input>

                <x-select-list-item wire:click="setData('filter','punishment_id','punishment','---',null)" :selected="'---' == $awardName"
                  wire:model='filter.punishment_id'>
                  ---
                </x-select-list-item>
                    @foreach($punishmentModel as $pnsh)
                    <x-select-list-item wire:click="setData('filter','punishment_id','punishment','{{ $pnsh->name }}',{{ $pnsh->id }})"
                    :selected="$pnsh->id === $punishmentId" wire:model='filter.punishment_id'>
                        {{ $pnsh->name }}
                    </x-select-list-item>
                    @endforeach
            </x-select-list>
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
