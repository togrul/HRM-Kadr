<div class="flex justify-between items-start space-x-4 w-full">
    <div class="flex flex-col space-y-4 flex-1">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.name">{{ __('Name') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.name" wire:model="personnel.name"></x-livewire-input>
                @error('personnel.name')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <div class="flex items-center space-x-2 justify-between">
                    <x-label for="personnel.surname">{{ __('Surname') }}</x-label>
                    <x-checkbox name="addManual" model="personnel.has_changed_initials">{{ __('changed?') }}</x-checkbox>
                </div>

                <x-livewire-input mode="gray" name="personnel.surname" wire:model="personnel.surname"></x-livewire-input>
                @error('personnel.surname')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.patronymic">{{ __('Patronymic') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.patronymic" wire:model="personnel.patronymic"></x-livewire-input>
                @error('personnel.patronymic')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @if($personnel['has_changed_initials'])
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.previous_name">{{ __('Previous name') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.previous_name" wire:model="personnel.previous_name"></x-livewire-input>
                @error('personnel.previous_name')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.previous_surname">{{ __('Previous surname') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.previous_surname" wire:model="personnel.previous_surname"></x-livewire-input>
                @error('personnel.previous_surname')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.previous_patronymic">{{ __('Previous patronymic') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.previous_patronymic" wire:model="personnel.previous_patronymic"></x-livewire-input>
                @error('personnel.previous_patronymic')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.name">{{ __('Change date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.initials_changed_date" format="Y-MM-DD" wire:model.live="personnel.initials_changed_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personnel.initials_changed_date', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                @error('personnel.initials_changed_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col col-span-2">
                <x-label for="personnel.name">{{ __('Change reason') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.initials_change_reason" wire:model="personnel.initials_change_reason"></x-livewire-input>
                @error('personnel.initials_change_reason')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @endif
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.birthdate">{{ __('Birthdate') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.birthdate" format="Y-MM-DD" wire:model.live="personnel.birthdate">
                  <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('personnel.birthdate', $el.value);
                    }
                  </x-slot>
                </x-pikaday-input>
                @error('personnel.birthdate')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col space-y-1">
                <x-label for="personnel.gender">{{ __('Gender') }}</x-label>
                <div class="flex flex-row">
                    @foreach(\App\Enums\GenderEnum::genderOptions() as $value => $label)
                        <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                            <input type="radio" class="form-radio" name="personnel.gender" wire:model="personnel.gender" value="{{ $value }}">
                            <span class="ml-2 text-sm font-normal">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('personnel.gender')
                <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
              <div class="flex flex-col">
                <x-select-list :hasCheckbox="true" class="w-full" :title="__('Nationality')" mode="gray" :selected="$nationalityName" name="nationalityId">
                    <x-slot name="checkbox">
                        <x-checkbox name="hasChangedNationality" model="personnel.has_changed_nationality">{{ __('changed?') }}</x-checkbox>
                    </x-slot>
                    <x-livewire-input  @click.stop="open = true" mode="gray" name="searchNationality" wire:model.live="searchNationality"></x-livewire-input>

                    <x-select-list-item wire:click="setData('personnel','nationality_id','nationality','---',null)" :selected="'---' == $nationalityName"
                      wire:model='personnel.nationality_id.id'>
                      ---
                    </x-select-list-item>
                    @foreach($nationalities as $nationality)
                    <x-select-list-item wire:click="setData('personnel','nationality_id','nationality','{{ $nationality->currentCountryTranslations->title }}',{{ $nationality->currentCountryTranslations->id }})"
                      :selected="$nationality->currentCountryTranslations->id === $nationalityId" wire:model='personnel.nationality_id.id'>
                      {{ $nationality->currentCountryTranslations->title }}
                    </x-select-list-item>
                    @endforeach
                  </x-select-list>
                  @error('personnel.nationality_id.id')
                  <x-validation> {{ $message }} </x-validation>
                  @enderror
              </div>
        </div>
        @if($personnel['has_changed_nationality'])
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-select-list  class="w-full" :title="__('Previous nationality')" mode="gray" :selected="$previousNationalityName" name="previousNationalityId">
                    <x-livewire-input  @click.stop="open = true" mode="gray" name="searchPreviousNationality" wire:model.live="searchPreviousNationality"></x-livewire-input>

                    <x-select-list-item wire:click="setData('personnel','previous_nationality_id','previousNationality','---',null)" :selected="'---' == $previousNationalityName"
                      wire:model='previousNationalityId'>
                      ---
                    </x-select-list-item>
                    @foreach($nationalities as $nationality)
                    <x-select-list-item wire:click="setData('personnel','previous_nationality_id','previousNationality','{{ $nationality->currentCountryTranslations->title }}',{{ $nationality->currentCountryTranslations->id }})"
                      :selected="$nationality->currentCountryTranslations->id === $previousNationalityId" wire:model='previousNationalityId'>
                      {{ $nationality->currentCountryTranslations->title }}
                    </x-select-list-item>
                    @endforeach
                  </x-select-list>
                  @error('personnel.previous_nationality_id.id')
                  <x-validation> {{ $message }} </x-validation>
                  @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.nationality_changed_date">{{ __('Nationality change date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.nationality_changed_date" format="Y-MM-DD" wire:model.live="personnel.nationality_changed_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personnel.nationality_changed_date', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                @error('personnel.nationality_changed_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.nationality_change_reason">{{ __('Nationality change reason') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.nationality_change_reason" wire:model="personnel.nationality_change_reason"></x-livewire-input>
                @error('personnel.nationality_change_reason')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @endif
        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.phone">{{ __('Phone') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.phone" wire:model="personnel.phone"></x-livewire-input>
                @error('personnel.phone')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.mobile">{{ __('Mobile') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.mobile" wire:model="personnel.mobile"></x-livewire-input>
                @error('personnel.mobile')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.email">{{ __('Email') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.email" wire:model="personnel.email"></x-livewire-input>
                @error('personnel.email')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.pin">{{ __('PIN') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.pin" wire:model="personnel.pin"></x-livewire-input>
                @error('personnel.pin')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-select-list class="w-full" :title="__('Social origin')" mode="gray" :selected="$socialOriginName" name="socialOriginId">
                    <x-livewire-input  @click.stop="open = true" mode="gray" name="searchSocialOrigin" wire:model.live="searchSocialOrigin"></x-livewire-input>

                    <x-select-list-item wire:click="setData('personnel','social_origin_id','socialOrigin','---',null)" :selected="'---' == $socialOriginName"
                                        wire:model='personnel.social_origin_id.id'>
                        ---
                    </x-select-list-item>
                    @if(!empty($_social_origins))
                        @foreach($_social_origins as $_origin)
                            <x-select-list-item wire:click="setData('personnel','social_origin_id','socialOrigin','{{ $_origin->name }}',{{ $_origin->id }})"
                                                :selected="$_origin->id === $socialOriginId" wire:model='personnel.social_origin_id.id'>
                                {{ $_origin->name }}
                            </x-select-list-item>
                        @endforeach
                    @endif
                </x-select-list>
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.residental_address">{{ __('Residental address') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.residental_address" wire:model="personnel.residental_address"></x-livewire-input>
                @error('personnel.residental_address')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.registered_address">{{ __('Registered address') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.registered_address" wire:model="personnel.registered_address"></x-livewire-input>
                @error('personnel.registered_address')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-select-list class="w-full" :title="__('Education degree')" mode="gray" :selected="$educationDegreeName" name="educationDegreeId">
                    <x-livewire-input  @click.stop="open = true" mode="gray" name="searchEducationDegree" wire:model.live="searchEducationDegree"></x-livewire-input>

                    <x-select-list-item wire:click="setData('personnel','education_degree_id','educationDegree','---',null)" :selected="'---' == $educationDegreeName"
                      wire:model='personnel.education_degree_id.id'>
                      ---
                    </x-select-list-item>
                    @foreach($education_degrees as $eduDegree)
                    <x-select-list-item wire:click="setData('personnel','education_degree_id','educationDegree','{{ $eduDegree->title }}',{{ $eduDegree->id }})"
                      :selected="$eduDegree->id === $educationDegreeId" wire:model='personnel.education_degree_id.id'>
                      {{ $eduDegree->title }}
                    </x-select-list-item>
                    @endforeach
                </x-select-list>
                @error('personnel.education_degree_id.id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
              <div class="flex flex-col">
                <x-select-list class="w-full" :title="__('Structure')" mode="gray" :selected="$structureName" name="structureId">
                    <x-livewire-input  @click.stop="open = true" mode="gray" name="searchStructure" wire:model.live="searchStructure"></x-livewire-input>

                    <x-select-list-item wire:click="setData('personnel','structure_id','structure','---',null)" :selected="'---' == $structureName"
                      wire:model='personnel.structure_id.id'>
                      ---
                    </x-select-list-item>
                    @foreach($structures as $structure)
                    <x-select-list-item wire:click="setData('personnel','structure_id','structure','{{ $structure->name }}',{{ $structure->id }})"
                      :selected="$structure->id === $structureId" wire:model='personnel.structure_id.id'>
                      {{ $structure->name }}
                    </x-select-list-item>
                    @endforeach
                </x-select-list>
                @error('personnel.structure_id.id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
              <div class="flex flex-col">
                <x-select-list class="w-full" :title="__('Position')" mode="gray" :selected="$positionName" name="positionId">
                    <x-livewire-input  @click.stop="open = true" mode="gray" name="searchPosition" wire:model.live="searchPosition"></x-livewire-input>

                    <x-select-list-item wire:click="setData('personnel','position_id','position','---',null)" :selected="'---' == $positionName"
                      wire:model='personnel.position_id.id'>
                      ---
                    </x-select-list-item>
                    @foreach($positions as $position)
                    <x-select-list-item wire:click="setData('personnel','position_id','position','{{ $position->name }}',{{ $position->id }})"
                      :selected="$position->id === $positionId" wire:model='personnel.position_id.id'>
                      {{ $position->name }}
                    </x-select-list-item>
                    @endforeach
                </x-select-list>
                @error('personnel.position_id.id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-select-list class="w-full" :title="__('Work norms')" mode="gray" :selected="$workNormName" name="workNormId">
                    <x-livewire-input  @click.stop="open = true" mode="gray" name="searchWorkNorm" wire:model.live="searchWorkNorm"></x-livewire-input>

                    <x-select-list-item wire:click="setData('personnel','work_norm_id','workNorm','---',null)" :selected="'---' == $workNormName"
                      wire:model='personnel.work_norm_id.id'>
                      ---
                    </x-select-list-item>
                    @foreach($work_norms as $norm)
                    <x-select-list-item wire:click="setData('personnel','work_norm_id','workNorm','{{ $norm->name }}',{{ $norm->id }})"
                      :selected="$norm->id === $workNormId" wire:model='personnel.work_norm_id.id'>
                      {{ $norm->name }}
                    </x-select-list-item>
                    @endforeach
                </x-select-list>
                @error('personnel.work_norm_id.id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>

              <div class="flex flex-col">
                <x-label for="personnel.join_work_date">{{ __('Join work date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.join_work_date" format="Y-MM-DD" wire:model.live="personnel.join_work_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personnel.join_work_date', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                @error('personnel.join_work_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.leave_work_date">{{ __('Leave work date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.leave_work_date" format="Y-MM-DD" wire:model.live="personnel.leave_work_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personnel.leave_work_date', $el.value);
                      }
                    </x-slot>
                </x-pikaday-input>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col space-y-2">
                <div class="flex flex-col">
                    <x-label for="personnel.special_inspection_date">{{ __('Special inspection date') }}</x-label>
                    <x-pikaday-input mode="gray" name="personnel.special_inspection_date" format="Y-MM-DD" wire:model.live="personnel.special_inspection_date">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('personnel.special_inspection_date', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                </div>
                @if (!empty($personnel['special_inspection_date']))
                    <div class="flex flex-col">
                        <x-label for="personnel.special_inspection_result">{{ __('Special inspection result') }}</x-label>
                        <x-textarea mode="gray" name="personnel.special_inspection_result" placeholder="{{__('')}}"
                                    wire:model="personnel.special_inspection_result"></x-textarea>
                    </div>
                @endif
            </div>
            <div class="flex flex-col space-y-2">
                <div class="flex flex-col">
                    <x-label for="personnel.medical_inspection_date">{{ __('Medical inspection date') }}</x-label>
                    <x-pikaday-input mode="gray" name="personnel.medical_inspection_date" format="Y-MM-DD" wire:model.live="personnel.medical_inspection_date">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('personnel.medical_inspection_date', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                </div>
                @if (!empty($personnel['medical_inspection_date']))
                    <div class="flex flex-col">
                        <x-label for="personnel.medical_inspection_result">{{ __('Medical inspection result') }}</x-label>
                        <x-textarea mode="gray" name="personnel.medical_inspection_result" placeholder="{{__('')}}"
                                    wire:model="personnel.medical_inspection_result"></x-textarea>
                    </div>
                @endif
            </div>
        </div>

        @if($isDisability)
        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col">
                <x-select-list class="w-full" :title="__('Disability')" mode="gray" :selected="$disabilityName" name="disabilityId">
                    <x-livewire-input  @click.stop="open = true" mode="gray" name="searchDisability" wire:model.live="searchDisability"></x-livewire-input>

                    <x-select-list-item wire:click="setData('personnel','disability_id','disability','---',null)" :selected="'---' == $disabilityName"
                      wire:model='personnel.disability_id.id'>
                      ---
                    </x-select-list-item>
                    @foreach($disabilities as $disability)
                    <x-select-list-item wire:click="setData('personnel','disability_id','disability','{{ $disability->name }}',{{ $disability->id }})"
                      :selected="$disability->id === $disabilityId" wire:model='personnel.disability_id.id'>
                        {{ $disability->name }}
                    </x-select-list-item>
                    @endforeach
                </x-select-list>
                @error('personnel.disability_id.id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
            <div class="flex flex-col">
                <x-label for="personnel.disability_given_date">{{ __('Disability given date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.disability_given_date" format="Y-MM-DD" wire:model.live="personnel.disability_given_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personnel.disability_given_date', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                  @error('personnel.disability_given_date')
                  <x-validation> {{ $message }} </x-validation>
                  @enderror
            </div>
        </div>
        @endif
        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col">
                <div class="flex items-center space-x-2 justify-between">
                    <x-label for="personnel.extra_important_information">{{ __('Extra information') }}</x-label>
                    <x-checkbox name="isDisability" model="isDisability">{{ __('has disability?') }}</x-checkbox>
                </div>
                <x-textarea mode="gray" name="personnel.extra_important_information" placeholder="{{__('')}}"
                  wire:model="personnel.extra_important_information"></x-textarea>
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.computer_knowledge">{{ __('Computer knowledge') }}</x-label>
                <x-textarea mode="gray" name="personnel.computer_knowledge" placeholder="{{__('')}}"
                  wire:model="personnel.computer_knowledge"></x-textarea>
            </div>

            <div class="flex flex-col">
                <x-label for="personnel.referenced_by">{{ __('Referenced by') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.referenced_by" wire:model="personnel.referenced_by"></x-livewire-input>
            </div>
        </div>
    </div>
    <div class="flex-none w-40">
        <div class="flex flex-col space-y-2">
            <div class="flex flex-col">
                <x-label for="personnel.tabel_no">{{ __('Tabel #') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.tabel_no" wire:model.defer="personnel.tabel_no"></x-livewire-input>
                @error('personnel.tabel_no')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="border border-gray-300 rounded-lg shadow-sm p-1">
                <div class="flex flex-col space-y-4">
                    <div class="mt-2 flex flex-col" x-data="{ isUploading: false, progress: 0 }"
                         x-on:livewire-upload-start="isUploading = true"
                         x-on:livewire-upload-finish="isUploading = false"
                         x-on:livewire-upload-error="isUploading = false"
                         x-on:livewire-upload-progress="progress = $event.detail.progress"
                    >
                      <div class="flex flex-col space-y-2 items-center">
                        @if ($avatar)
                        <img alt="avatar" class="w-full h-full object-cover" src="{{ $avatar->temporaryUrl() }}">
                        @elseif(!empty($personnelModel) && $personnelModelData->photo)
{{--                        <img alt="avatar" class="w-full h-full object-cover" src="{{ asset('/storage/'.$personnelModelData->photo) }}">--}}
                           <img alt="avatar" class="w-full h-full object-cover" src="{{ \Illuminate\Support\Facades\Storage::url($personnelModelData->photo) }}">
                        @else
                        <img class="w-full h-full" src="{{ asset('assets/images/id-photo.jpeg') }}" alt="id photo">
                        @endif
                        <label
                          class="flex ml-2 cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-[40px]">
                          <span class="text-sm leading-normal">{{__('Choose photo')}}</span>
                          <input type='file' class="hidden" wire:model="avatar"  />
                        </label>
                      </div>
                      <div x-show="isUploading">
                        <progress max="100" x-bind:value="progress"></progress>
                      </div>
                    </div>

                    @error('avatar') <span class="error">{{ $message }}</span> @enderror
                </div>

            </div>
        </div>

    </div>
</div>
