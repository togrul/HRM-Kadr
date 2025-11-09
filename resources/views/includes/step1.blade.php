@php
    $personal = $personalForm->personnel ?? [];
    $personalExtra = $personalForm->personnelExtra ?? [];
    $hasDisability = $personalForm->hasDisability ?? false;
@endphp

<div class="flex justify-between items-start space-x-4 w-full">
    <div class="flex flex-col space-y-4 flex-1">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.name">{{ __('Name') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.name" wire:model="personalForm.personnel.name"></x-livewire-input>
                @error('personalForm.personnel.name')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <div class="flex items-center space-x-2 justify-between">
                    <x-label for="personnel.surname">{{ __('Surname') }}</x-label>
                    <x-checkbox name="addManual" model="personalForm.personnel.has_changed_initials">{{ __('changed?') }}</x-checkbox>
                </div>

                <x-livewire-input mode="gray" name="personnel.surname" wire:model="personalForm.personnel.surname"></x-livewire-input>
                @error('personalForm.personnel.surname')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.patronymic">{{ __('Patronymic') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.patronymic" wire:model="personalForm.personnel.patronymic"></x-livewire-input>
                @error('personalForm.personnel.patronymic')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @if(data_get($personal, 'has_changed_initials'))
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.previous_name">{{ __('Previous name') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.previous_name" wire:model="personalForm.personnel.previous_name"></x-livewire-input>
                @error('personalForm.personnel.previous_name')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.previous_surname">{{ __('Previous surname') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.previous_surname" wire:model="personalForm.personnel.previous_surname"></x-livewire-input>
                @error('personalForm.personnel.previous_surname')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.previous_patronymic">{{ __('Previous patronymic') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.previous_patronymic" wire:model="personalForm.personnel.previous_patronymic"></x-livewire-input>
                @error('personalForm.personnel.previous_patronymic')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.name">{{ __('Change date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.initials_changed_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.initials_changed_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personalForm.personnel.initials_changed_date', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                @error('personalForm.personnel.initials_changed_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col col-span-2">
                <x-label for="personnel.name">{{ __('Change reason') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.initials_change_reason" wire:model="personalForm.personnel.initials_change_reason"></x-livewire-input>
                @error('personalForm.personnel.initials_change_reason')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @endif
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.birthdate">{{ __('Birthdate') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.birthdate" format="Y-MM-DD" wire:model.live="personalForm.personnel.birthdate">
                  <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('personalForm.personnel.birthdate', $el.value);
                    }
                  </x-slot>
                </x-pikaday-input>
                @error('personalForm.personnel.birthdate')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col space-y-1">
                <x-label for="personnel.gender">{{ __('Gender') }}</x-label>
                <div class="flex flex-row">
                    @foreach(\App\Enums\GenderEnum::genderOptions() as $value => $label)
                        <label class="inline-flex items-center bg-gray-100 rounded shadow-sm py-2 px-2">
                            <input type="radio" class="form-radio" name="personnel.gender" wire:model="personalForm.personnel.gender" value="{{ $value }}">
                            <span class="ml-2 text-sm font-normal">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('personalForm.personnel.gender')
                <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
            <div class="flex flex-col">
                <div class="flex items-center space-x-2 justify-between">
                    <x-label for="personnel.name">{{ __('Nationality') }}</x-label>
                    <x-checkbox name="hasChangedNationality" model="personalForm.personnel.has_changed_nationality">{{ __('changed?') }}</x-checkbox>
                </div>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.nationality_id"
                    :model="$this->nationalityOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchNationality"
                        wire:model.live.debounce.300ms="searchNationality"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('personalForm.personnel.nationality_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @if(data_get($personal, 'has_changed_nationality'))
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.previous_nationality">{{ __('Previous nationality') }}</x-label>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.previous_nationality_id"
                    :model="$this->previousNationalityOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchPreviousNationality"
                        wire:model.live.debounce.300ms="searchPreviousNationality"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('personalForm.personnel.previous_nationality_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.nationality_changed_date">{{ __('Nationality change date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.nationality_changed_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.nationality_changed_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personalForm.personnel.nationality_changed_date', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                @error('personalForm.personnel.nationality_changed_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.nationality_change_reason">{{ __('Nationality change reason') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.nationality_change_reason" wire:model="personalForm.personnel.nationality_change_reason"></x-livewire-input>
                @error('personalForm.personnel.nationality_change_reason')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @endif
        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.phone">{{ __('Phone') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.phone" wire:model="personalForm.personnel.phone"></x-livewire-input>
                @error('personalForm.personnel.phone')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.mobile">{{ __('Mobile') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.mobile" wire:model="personalForm.personnel.mobile"></x-livewire-input>
                @error('personalForm.personnel.mobile')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.email">{{ __('Email') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.email" wire:model="personalForm.personnel.email"></x-livewire-input>
                @error('personalForm.personnel.email')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.pin">{{ __('PIN') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.pin" wire:model="personalForm.personnel.pin"></x-livewire-input>
                @error('personalForm.personnel.pin')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.social_origin_id">{{ __('Social origin') }}</x-label>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.social_origin_id"
                    :model="$this->socialOriginOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchSocialOrigin"
                        wire:model.live.debounce.300ms="searchSocialOrigin"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('personalForm.personnel.social_origin_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.residental_address">{{ __('Residental address') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.residental_address" wire:model="personalForm.personnel.residental_address"></x-livewire-input>
                @error('personalForm.personnel.residental_address')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.registered_address">{{ __('Registered address') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.registered_address" wire:model="personalForm.personnel.registered_address"></x-livewire-input>
                @error('personalForm.personnel.registered_address')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.education_degree_id">{{ __('Education degree') }}</x-label>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.education_degree_id"
                    :model="$this->educationDegreeOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchEducationDegree"
                        wire:model.live.debounce.300ms="searchEducationDegree"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('personalForm.personnel.education_degree_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.structure_id">{{ __('Structure') }}</x-label>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.structure_id"
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
                @error('personalForm.personnel.structure_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.position_id">{{ __('Position') }}</x-label>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.position_id"
                    :model="$this->positionOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchPosition"
                        wire:model.live.debounce.300ms="searchPosition"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('personalForm.personnel.position_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.work_norm_id">{{ __('Work norms') }}</x-label>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.work_norm_id"
                    :model="$this->workNormOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchWorkNorm"
                        wire:model.live.debounce.300ms="searchWorkNorm"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('personalForm.personnel.work_norm_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>

              <div class="flex flex-col">
                <x-label for="personnel.join_work_date">{{ __('Join work date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.join_work_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.join_work_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personalForm.personnel.join_work_date', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                @error('personalForm.personnel.join_work_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.leave_work_date">{{ __('Leave work date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.leave_work_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.leave_work_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personalForm.personnel.leave_work_date', $el.value);
                      }
                    </x-slot>
                </x-pikaday-input>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col space-y-2">
                <div class="flex flex-col">
                    <x-label for="personnel.special_inspection_date">{{ __('Special inspection date') }}</x-label>
                    <x-pikaday-input mode="gray" name="personnel.special_inspection_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.special_inspection_date">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('personalForm.personnel.special_inspection_date', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                </div>
                @if (!empty(data_get($personal, 'special_inspection_date')))
                    <div class="flex flex-col">
                        <x-label for="personnel.special_inspection_result">{{ __('Special inspection result') }}</x-label>
                        <x-textarea mode="gray" name="personnel.special_inspection_result" placeholder="{{__('')}}"
                                    wire:model="personalForm.personnel.special_inspection_result"></x-textarea>
                    </div>
                @endif
            </div>
            <div class="flex flex-col space-y-2">
                <div class="flex flex-col">
                    <x-label for="personnel.medical_inspection_date">{{ __('Medical inspection date') }}</x-label>
                    <x-pikaday-input mode="gray" name="personnel.medical_inspection_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.medical_inspection_date">
                        <x-slot name="script">
                            $el.onchange = function () {
                            @this.set('personalForm.personnel.medical_inspection_date', $el.value);
                            }
                        </x-slot>
                    </x-pikaday-input>
                </div>
                @if (!empty(data_get($personal, 'medical_inspection_date')))
                    <div class="flex flex-col">
                        <x-label for="personnel.medical_inspection_result">{{ __('Medical inspection result') }}</x-label>
                        <x-textarea mode="gray" name="personnel.medical_inspection_result" placeholder="{{__('')}}"
                                    wire:model="personalForm.personnel.medical_inspection_result"></x-textarea>
                    </div>
                @endif
            </div>
        </div>

        @if($hasDisability)
        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col">
                <x-label for="personnel.disability_id">{{ __('Disability') }}</x-label>
                <x-ui.select-dropdown
                    label=""
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.disability_id"
                    :model="$this->disabilityOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchDisability"
                        wire:model.live.debounce.300ms="searchDisability"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('personalForm.personnel.disability_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
            <div class="flex flex-col">
                <x-label for="personnel.disability_given_date">{{ __('Disability given date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.disability_given_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.disability_given_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personalForm.personnel.disability_given_date', $el.value);
                      }
                    </x-slot>
                  </x-pikaday-input>
                  @error('personalForm.personnel.disability_given_date')
                  <x-validation> {{ $message }} </x-validation>
                  @enderror
            </div>
        </div>
        @endif
        <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col">
                <div class="flex items-center space-x-2 justify-between">
                    <x-label for="personnel.extra_important_information">{{ __('Extra information') }}</x-label>
                    <x-checkbox name="isDisability" model="personalForm.hasDisability">{{ __('has disability?') }}</x-checkbox>
                </div>
                <x-textarea mode="gray" name="personnel.extra_important_information" placeholder="{{__('')}}"
                  wire:model="personalForm.personnel.extra_important_information"></x-textarea>
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.computer_knowledge">{{ __('Computer knowledge') }}</x-label>
                <x-textarea mode="gray" name="personnel.computer_knowledge" placeholder="{{__('')}}"
                  wire:model="personalForm.personnel.computer_knowledge"></x-textarea>
            </div>

            <div class="flex flex-col">
                <x-label for="personnel.referenced_by">{{ __('Referenced by') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.referenced_by" wire:model="personalForm.personnel.referenced_by"></x-livewire-input>
            </div>
        </div>
    </div>
    <div class="flex-none w-40">
        <div class="flex flex-col space-y-2">
            <div class="flex flex-col">
                <x-label for="personnel.tabel_no">{{ __('Tabel #') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.tabel_no" wire:model.defer="personalForm.personnel.tabel_no"></x-livewire-input>
                @error('personalForm.personnel.tabel_no')
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
