@php
    use App\Modules\Personnel\Support\EmploymentTerms;

    $personal = $personalForm->personnel ?? [];
    $personalExtra = $personalForm->personnelExtra ?? [];
    $hasDisability = $personalForm->hasDisability ?? false;

    $contractTypeOptions = EmploymentTerms::options(EmploymentTerms::CONTRACT_TYPES, 'contract_type');
    $probationUnitOptions = EmploymentTerms::options(EmploymentTerms::PROBATION_UNITS, 'probation_unit');
    $workplaceTypeOptions = EmploymentTerms::options(EmploymentTerms::WORKPLACE_TYPES, 'workplace_type');
    $workingTimeTypeOptions = EmploymentTerms::options(EmploymentTerms::WORKING_TIME_TYPES, 'working_time_type');
    $workScheduleOptions = EmploymentTerms::options(EmploymentTerms::WORK_SCHEDULES, 'work_schedule');
    $restDayOptions = EmploymentTerms::options(EmploymentTerms::REST_DAYS, 'rest_day');

    $probationUnit = $personal['probation_unit'] ?? null;
    $probationUnitLabel = $probationUnit ? __('personnel::common.employment.probation_unit.'.$probationUnit) : '';
    $workSchedule = $personal['work_schedule'] ?? null;
    $isWeeklySchedule = EmploymentTerms::isWeekly($workSchedule);
    $shiftCount = EmploymentTerms::shiftCount($workSchedule);
@endphp

<div class="flex w-full flex-col items-stretch gap-4 md:flex-row md:items-start">
    <div class="flex min-w-0 flex-1 flex-col space-y-4">
        <div class="border-b border-hairline-subtle pb-2">
            <h3 class="text-[15px] font-semibold text-ink">{{ __('personnel::common.steps.personal_information') }}</h3>
            <p class="mt-1 text-sm leading-5 text-ink-muted">{{ __('personnel::common.messages.required_fields_hint') }}</p>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="flex flex-col">
                <x-label required for="personnel.name">{{ __('personnel::common.labels.name') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.name" wire:model="personalForm.personnel.name"></x-livewire-input>
                @error('personalForm.personnel.name')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <div class="flex items-center justify-between space-x-2">
                    <x-label required for="personnel.surname">{{ __('personnel::common.labels.surname') }}</x-label>
                    <x-checkbox name="addManual" model="personalForm.personnel.has_changed_initials">{{ __('personnel::common.questions.changed') }}</x-checkbox>
                </div>

                <x-livewire-input mode="gray" aria-required="true" name="personnel.surname" wire:model="personalForm.personnel.surname"></x-livewire-input>
                @error('personalForm.personnel.surname')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label required for="personnel.patronymic">{{ __('personnel::common.labels.patronymic') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.patronymic" wire:model="personalForm.personnel.patronymic"></x-livewire-input>
                @error('personalForm.personnel.patronymic')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @if(data_get($personal, 'has_changed_initials'))
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="flex flex-col">
                <x-label required for="personnel.previous_name">{{ __('personnel::common.labels.previous_name') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.previous_name" wire:model="personalForm.personnel.previous_name"></x-livewire-input>
                @error('personalForm.personnel.previous_name')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label required for="personnel.previous_surname">{{ __('personnel::common.labels.previous_surname') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.previous_surname" wire:model="personalForm.personnel.previous_surname"></x-livewire-input>
                @error('personalForm.personnel.previous_surname')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label required for="personnel.previous_patronymic">{{ __('personnel::common.labels.previous_patronymic') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.previous_patronymic" wire:model="personalForm.personnel.previous_patronymic"></x-livewire-input>
                @error('personalForm.personnel.previous_patronymic')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="flex flex-col">
                <x-label required for="personnel.initials_changed_date">{{ __('personnel::common.labels.change_date') }}</x-label>
                <x-pikaday-input mode="gray" aria-required="true" name="personnel.initials_changed_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.initials_changed_date">
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
            <div class="flex flex-col md:col-span-2">
                <x-label required for="personnel.initials_change_reason">{{ __('personnel::common.labels.change_reason') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.initials_change_reason" wire:model="personalForm.personnel.initials_change_reason"></x-livewire-input>
                @error('personalForm.personnel.initials_change_reason')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @endif
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="flex flex-col">
                <x-label required for="personnel.birthdate">{{ __('personnel::common.labels.birthdate') }}</x-label>
                <x-pikaday-input mode="gray" aria-required="true" name="personnel.birthdate" format="Y-MM-DD" wire:model.live="personalForm.personnel.birthdate">
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
                @php $genderInvalid = $errors->has('personalForm.personnel.gender'); @endphp
                <x-label required id="personnel-gender-label">{{ __('personnel::common.labels.gender') }}</x-label>
                <div
                    role="radiogroup"
                    aria-labelledby="personnel-gender-label"
                    aria-required="true"
                    @if ($genderInvalid) aria-invalid="true" @endif
                    @class(['flex flex-row rounded', 'ring-1 ring-rose-300' => $genderInvalid])
                >
                    @foreach(\App\Enums\GenderEnum::genderOptions() as $value => $label)
                        <label @class(['inline-flex items-center px-2 py-2 rounded shadow-sm', 'bg-rose-50' => $genderInvalid, 'bg-gray-100' => ! $genderInvalid])>
                            {{-- aria-invalid on the first radio lets the wizard focus the group after Next. --}}
                            <input type="radio" class="form-radio" name="personnel.gender" wire:model="personalForm.personnel.gender" value="{{ $value }}" @if ($genderInvalid && $loop->first) aria-invalid="true" @endif>
                            <span class="ml-2 text-sm font-normal">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('personalForm.personnel.gender')
                <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
            <div class="flex flex-col">
                <div class="flex items-center justify-between space-x-2">
                    <x-label required>{{ __('personnel::common.labels.nationality') }}</x-label>
                    <x-checkbox name="hasChangedNationality" model="personalForm.personnel.has_changed_nationality">{{ __('personnel::common.questions.changed') }}</x-checkbox>
                </div>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.nationality')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    aria-required="true"
                    wire:model.live="personalForm.personnel.nationality_id"
                    :model="$this->nationalityOptions"
                    :search-model="data_get($stepSearchModels, 'searchNationality', 'searchNationality')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchNationality', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.nationality_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @if(data_get($personal, 'has_changed_nationality'))
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="flex flex-col">
                <x-label for="personnel.previous_nationality">{{ __('personnel::common.labels.previous_nationality') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.previous_nationality')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    aria-required="true"
                    wire:model.live="personalForm.personnel.previous_nationality_id"
                    :model="$this->previousNationalityOptions"
                    :search-model="data_get($stepSearchModels, 'searchPreviousNationality', 'searchPreviousNationality')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchPreviousNationality', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.previous_nationality_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label required for="personnel.nationality_changed_date">{{ __('personnel::common.labels.nationality_change_date') }}</x-label>
                <x-pikaday-input mode="gray" aria-required="true" name="personnel.nationality_changed_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.nationality_changed_date">
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
                <x-label required for="personnel.nationality_change_reason">{{ __('personnel::common.labels.nationality_change_reason') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.nationality_change_reason" wire:model="personalForm.personnel.nationality_change_reason"></x-livewire-input>
                @error('personalForm.personnel.nationality_change_reason')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        @endif
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
            <div class="flex flex-col">
                <x-label for="personnel.phone">{{ __('personnel::common.labels.phone') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.phone" wire:model="personalForm.personnel.phone"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label required for="personnel.mobile">{{ __('personnel::common.labels.mobile') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.mobile" wire:model="personalForm.personnel.mobile"></x-livewire-input>
                @error('personalForm.personnel.mobile')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.email">{{ __('personnel::common.labels.email') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.email" wire:model="personalForm.personnel.email"></x-livewire-input>
            </div>
            <div class="flex flex-col">
                <x-label required for="personnel.pin">{{ __('personnel::common.labels.pin') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.pin" wire:model="personalForm.personnel.pin"></x-livewire-input>
                @error('personalForm.personnel.pin')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="flex flex-col">
                <x-label required for="personnel.residental_address">{{ __('personnel::common.labels.residental_address') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.residental_address" wire:model="personalForm.personnel.residental_address"></x-livewire-input>
                @error('personalForm.personnel.residental_address')
                    <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label required for="personnel.registered_address">{{ __('personnel::common.labels.registered_address') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.registered_address" wire:model="personalForm.personnel.registered_address"></x-livewire-input>
                @error('personalForm.personnel.registered_address')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <h3 class="border-b border-hairline-subtle pb-2 pt-4 text-[15px] font-semibold text-ink">{{ __('personnel::profile.sections.career') }}</h3>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="flex flex-col">
                <x-label required for="personnel.education_degree_id">{{ __('personnel::common.labels.education_degree') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.education_degree')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    aria-required="true"
                    wire:model.live="personalForm.personnel.education_degree_id"
                    :model="$this->educationDegreeOptions"
                    :search-model="data_get($stepSearchModels, 'searchEducationDegree', 'searchEducationDegree')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchEducationDegree', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.education_degree_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label required for="personnel.structure_id">{{ __('personnel::common.labels.structure') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.structure')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    aria-required="true"
                    wire:model.live="personalForm.personnel.structure_id"
                    :model="$this->structureOptions"
                    :search-model="data_get($stepSearchModels, 'searchStructure', 'searchStructure')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchStructure', __('personnel::common.placeholders.search'))"
                    :disabled="!empty($personnelModel)"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.structure_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label required for="personnel.position_id">{{ __('personnel::common.labels.position') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.position')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    aria-required="true"
                    wire:model.live="personalForm.personnel.position_id"
                    :model="$this->positionOptions"
                    :search-model="data_get($stepSearchModels, 'searchPosition', 'searchPosition')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchPosition', __('personnel::common.placeholders.search'))"
                    :disabled="!empty($personnelModel)"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.position_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="flex flex-col">
                <x-label required for="personnel.work_norm_id">{{ __('personnel::common.labels.work_norms') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.work_norms')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    aria-required="true"
                    wire:model.live="personalForm.personnel.work_norm_id"
                    :model="$this->workNormOptions"
                    :search-model="data_get($stepSearchModels, 'searchWorkNorm', 'searchWorkNorm')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchWorkNorm', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.work_norm_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.contract_type">{{ __('personnel::common.labels.contract_type') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.contract_type')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.contract_type"
                    :model="$contractTypeOptions"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.contract_type')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="flex flex-col">
                <x-label for="personnel.contract_date">{{ __('personnel::common.labels.contract_date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.contract_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.contract_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personalForm.personnel.contract_date', $el.value);
                      }
                    </x-slot>
                </x-pikaday-input>
                @error('personalForm.personnel.contract_date')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label required for="personnel.join_work_date">{{ __('personnel::common.labels.join_work_date') }}</x-label>
                <x-pikaday-input mode="gray" aria-required="true" name="personnel.join_work_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.join_work_date">
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
                <x-label for="personnel.leave_work_date">{{ __('personnel::common.labels.leave_work_date') }}</x-label>
                <x-pikaday-input mode="gray" name="personnel.leave_work_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.leave_work_date">
                    <x-slot name="script">
                      $el.onchange = function () {
                      @this.set('personalForm.personnel.leave_work_date', $el.value);
                      }
                    </x-slot>
                </x-pikaday-input>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="flex flex-col">
                <x-label for="personnel.probation_unit">{{ __('personnel::common.labels.probation_period') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.probation_period')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.probation_unit"
                    :model="$probationUnitOptions"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.probation_unit')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            {{-- The length only means something once a unit is chosen, so the field appears with it. --}}
            @if ($probationUnit)
                <div class="flex flex-col">
                    <x-label for="personnel.probation_amount">{{ __('personnel::common.labels.probation_amount') }} ({{ $probationUnitLabel }})</x-label>
                    <x-livewire-input type="number" min="1" mode="gray" name="personnel.probation_amount" wire:model="personalForm.personnel.probation_amount"></x-livewire-input>
                    @error('personalForm.personnel.probation_amount')
                    <x-validation> {{ $message }} </x-validation>
                    @enderror
                </div>
            @endif
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="flex flex-col">
                <x-label for="personnel.workplace_type">{{ __('personnel::common.labels.workplace_type') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.workplace_type')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.workplace_type"
                    :model="$workplaceTypeOptions"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.workplace_type')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.working_time_type">{{ __('personnel::common.labels.working_time_type') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.working_time_type')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.working_time_type"
                    :model="$workingTimeTypeOptions"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.working_time_type')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.work_schedule">{{ __('personnel::common.labels.work_schedule') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.work_schedule')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="personalForm.personnel.work_schedule"
                    :model="$workScheduleOptions"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.work_schedule')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        </div>
        {{-- A working week has fixed daily hours, a lunch break and rest days; a shift rota has neither. --}}
        @if ($isWeeklySchedule)
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                @foreach ([
                    'work_start' => 'work_start_time',
                    'work_end' => 'work_end_time',
                    'lunch_start' => 'lunch_start_time',
                    'lunch_end' => 'lunch_end_time',
                ] as $hourKey => $hourLabel)
                    <div class="flex flex-col">
                        <x-label for="personnel.work_hours.{{ $hourKey }}">{{ __('personnel::common.labels.'.$hourLabel) }}</x-label>
                        <x-livewire-input type="time" mode="gray" name="personnel.work_hours.{{ $hourKey }}" wire:model="personalForm.personnel.work_hours.{{ $hourKey }}"></x-livewire-input>
                        @error('personalForm.personnel.work_hours.'.$hourKey)
                        <x-validation> {{ $message }} </x-validation>
                        @enderror
                    </div>
                @endforeach
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.rest_days">{{ __('personnel::common.labels.rest_days') }}</x-label>
                <div class="flex flex-wrap gap-4 mt-1">
                    @foreach ($restDayOptions as $restDay)
                        <x-checkbox name="personnel.rest_days" model="personalForm.personnel.rest_days" :value="$restDay['id']">{{ $restDay['label'] }}</x-checkbox>
                    @endforeach
                </div>
                @error('personalForm.personnel.rest_days')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
        @endif
        @if ($shiftCount > 0)
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                @for ($shift = 1; $shift <= $shiftCount; $shift++)
                    @foreach (['start' => 'shift_start_time', 'end' => 'shift_end_time'] as $edge => $shiftLabel)
                        <div class="flex flex-col">
                            <x-label for="personnel.work_hours.shift_{{ $shift }}_{{ $edge }}">{{ __('personnel::common.labels.'.$shiftLabel, ['shift' => __('personnel::common.employment.shift.'.$shift)]) }}</x-label>
                            <x-livewire-input type="time" mode="gray" name="personnel.work_hours.shift_{{ $shift }}_{{ $edge }}" wire:model="personalForm.personnel.work_hours.shift_{{ $shift }}_{{ $edge }}"></x-livewire-input>
                            @error('personalForm.personnel.work_hours.shift_'.$shift.'_'.$edge)
                            <x-validation> {{ $message }} </x-validation>
                            @enderror
                        </div>
                    @endforeach
                @endfor
            </div>
        @endif

        <h3 class="border-b border-hairline-subtle pb-2 pt-4 text-[15px] font-semibold text-ink">{{ __('personnel::common.labels.extra_information') }}</h3>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="flex flex-col space-y-2">
                <div class="flex flex-col">
                    <x-label for="personnel.special_inspection_date">{{ __('personnel::common.labels.special_inspection_date') }}</x-label>
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
                        <x-label for="personnel.special_inspection_result">{{ __('personnel::common.labels.special_inspection_result') }}</x-label>
                        <x-textarea mode="gray" name="personnel.special_inspection_result" placeholder=""
                                    wire:model="personalForm.personnel.special_inspection_result"></x-textarea>
                    </div>
                @endif
            </div>
            <div class="flex flex-col space-y-2">
                <div class="flex flex-col">
                    <x-label for="personnel.medical_inspection_date">{{ __('personnel::common.labels.medical_inspection_date') }}</x-label>
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
                        <x-label for="personnel.medical_inspection_result">{{ __('personnel::common.labels.medical_inspection_result') }}</x-label>
                        <x-textarea mode="gray" name="personnel.medical_inspection_result" placeholder=""
                                    wire:model="personalForm.personnel.medical_inspection_result"></x-textarea>
                    </div>
                @endif
            </div>
        </div>

        @if($hasDisability)
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="flex flex-col">
                <x-label required for="personnel.disability_id">{{ __('personnel::common.labels.disability') }}</x-label>
                <x-ui.select-dropdown
                    :aria-label="__('personnel::common.labels.disability')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    aria-required="true"
                    wire:model.live="personalForm.personnel.disability_id"
                    :model="$this->disabilityOptions"
                    :search-model="data_get($stepSearchModels, 'searchDisability', 'searchDisability')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchDisability', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('personalForm.personnel.disability_id')
                <x-validation> {{ $message }} </x-validation>
                @enderror
              </div>
            <div class="flex flex-col">
                <x-label required for="personnel.disability_given_date">{{ __('personnel::common.labels.disability_given_date') }}</x-label>
                <x-pikaday-input mode="gray" aria-required="true" name="personnel.disability_given_date" format="Y-MM-DD" wire:model.live="personalForm.personnel.disability_given_date">
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
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="flex flex-col">
                <div class="flex items-center justify-between space-x-2">
                    <x-label for="personnel.extra_important_information">{{ __('personnel::common.labels.extra_information') }}</x-label>
                    <x-checkbox name="isDisability" model="personalForm.hasDisability">{{ __('personnel::common.questions.has_disability') }}</x-checkbox>
                </div>
                <x-textarea mode="gray" name="personnel.extra_important_information" placeholder=""
                  wire:model="personalForm.personnel.extra_important_information"></x-textarea>
            </div>
            <div class="flex flex-col">
                <x-label for="personnel.computer_knowledge">{{ __('personnel::common.labels.computer_knowledge') }}</x-label>
                <x-textarea mode="gray" name="personnel.computer_knowledge" placeholder=""
                  wire:model="personalForm.personnel.computer_knowledge"></x-textarea>
            </div>

            <div class="flex flex-col">
                <x-label for="personnel.referenced_by">{{ __('personnel::common.labels.referenced_by') }}</x-label>
                <x-livewire-input mode="gray" name="personnel.referenced_by" wire:model="personalForm.personnel.referenced_by"></x-livewire-input>
            </div>
        </div>
    </div>
    <div class="w-full flex-none md:w-40">
        <div class="flex flex-col space-y-2">
            <div class="flex flex-col">
                <x-label required for="personnel.tabel_no">{{ __('personnel::common.labels.tabel_hash') }}</x-label>
                <x-livewire-input mode="gray" aria-required="true" name="personnel.tabel_no" wire:model="personalForm.personnel.tabel_no"></x-livewire-input>
                @error('personalForm.personnel.tabel_no')
                <x-validation> {{ $message }} </x-validation>
                @enderror
            </div>
            <div class="mx-auto w-40 rounded-lg border border-gray-300 p-1 shadow-sm md:mx-0">
                <div class="flex flex-col space-y-4">
                    <div class="flex flex-col mt-2" x-data="{ isUploading: false, progress: 0 }"
                         x-on:livewire-upload-start="isUploading = true"
                         x-on:livewire-upload-finish="isUploading = false"
                         x-on:livewire-upload-error="isUploading = false"
                         x-on:livewire-upload-progress="progress = $event.detail.progress"
                    >
                      <div class="flex flex-col items-center space-y-2">
                        @if ($avatar)
                        <img alt="" class="object-cover w-full h-full" src="{{ $avatar->temporaryUrl() }}">
                        @elseif(!empty($personnelModel) && !empty($personnelPhotoUrl))
{{--                        <img alt="avatar" class="object-cover w-full h-full" src="{{ asset('/storage/'.$personnelModelData->photo) }}">--}}
                           <img alt="" class="object-cover w-full h-full" src="{{ $personnelPhotoUrl }}">
                        @else
                        <img class="w-full h-full" src="{{ asset('assets/images/id-photo.jpeg') }}" alt="">
                        @endif
                        <label
                          class="ml-2 flex h-10 cursor-pointer items-center rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[14px] font-medium text-ink-soft transition hover:bg-[#e4e4e7] focus-within:ring-2 focus-within:ring-zinc-400 focus-within:ring-offset-2">
                          <span class="text-sm leading-normal">{{ __('personnel::common.actions.choose_photo') }}</span>
                          <input type="file" class="sr-only" wire:model="avatar" />
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
