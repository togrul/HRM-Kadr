<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Education place')" mode="gray" :selected="$institutionName" name="educational_institution_id">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchInstitution" wire:model.live="searchInstitution"></x-livewire-input>

            <x-select-list-item wire:click="setData('education','educational_institution_id','institution','---',null)" :selected="'---' == $institutionName"
              wire:model='education.educational_institution_id.id'>
              ---
            </x-select-list-item>
            @foreach($institutions as $institution)
            <x-select-list-item wire:click="setData('education','educational_institution_id','institution','{{ $institution->name }}',{{ $institution->id }})"
              :selected="$institution->id === $institutionId" wire:model='education.educational_institution_id.id'>
                {{ $institution->name }}
            </x-select-list-item>
            @endforeach
        </x-select-list>
        @error('education.educational_institution_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Education form')" mode="gray" :selected="$educationFormName" name="educationFormId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchEducationForm" wire:model.live="searchEducationForm"></x-livewire-input>

            <x-select-list-item wire:click="setData('education','education_form_id','educationForm','---',null)" :selected="'---' == $educationFormName"
              wire:model='education.education_form_id.id'>
              ---
            </x-select-list-item>
            @foreach($education_forms as $ef)
            <x-select-list-item wire:click="setData('education','education_form_id','educationForm','{{ $ef->name }}',{{ $ef->id }})"
              :selected="$ef->id === $educationFormId" wire:model='education.education_form_id.id'>
                {{ $ef->name }}
            </x-select-list-item>
            @endforeach
        </x-select-list>
        @error('education.education_form_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="education.education_language">{{ __('Education language') }}</x-label>
        <x-livewire-input mode="gray" name="education.education_language" wire:model="education.education_language"></x-livewire-input>
        @error('education.education_language')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>
<div class="grid grid-cols-4 gap-2">
    <div class="flex flex-col">
        <x-label for="education.specialty">{{ __('Specialty') }}</x-label>
        <x-livewire-input mode="gray" name="education.specialty" wire:model="education.specialty"></x-livewire-input>
        @error('education.specialty')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="education.admission_year">{{ __('Admission year') }}</x-label>
        <x-pikaday-input mode="gray" name="education.admission_year" format="Y-MM-DD" wire:model.live="education.admission_year">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('education.admission_year', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
        @error('education.admission_year')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="education.graduated_year">{{ __('Graduated year') }}</x-label>
        <x-pikaday-input mode="gray" name="education.graduated_year" format="Y-MM-DD" wire:model.live="education.graduated_year">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('education.graduated_year', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
        @error('education.graduated_year')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="education.profession_by_document">{{ __('Profession') }}</x-label>
        <x-livewire-input mode="gray" name="education.profession_by_document" wire:model="education.profession_by_document"></x-livewire-input>
        @error('education.profession_by_document')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>
<div class="grid grid-cols-3 gap-2">
    <div class="grid grid-cols-3 gap-2">
        <div class="flex flex-col">
            <x-label for="education.diplom_serie">{{ __('Diplom serie') }}</x-label>
            <x-livewire-input mode="gray" name="education.diplom_serie" wire:model="education.diplom_serie"></x-livewire-input>
            @error('education.diplom_serie')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col col-span-2">
            <x-label for="education.diplom_no">{{ __('Diplom no') }}</x-label>
            <x-livewire-input type="number" mode="gray" name="education.diplom_no" wire:model="education.diplom_no"></x-livewire-input>
            @error('education.diplom_no')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>

    <div class="flex flex-col">
        <x-label for="education.diplom_given_date">{{ __('Diplom given date') }}</x-label>
        <x-pikaday-input mode="gray" name="education.diplom_given_date" format="Y-MM-DD" wire:model.live="education.diplom_given_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('education.diplom_given_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
        @error('education.diplom_given_date')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>

    <div class="flex space-x-2 items-start">
        <div class="flex flex-col">
            <x-label for="education.coefficient">{{ __('Coefficient') }}</x-label>
            <x-livewire-input mode="gray" type="number" name="education.coefficient" wire:model.live="education.coefficient"></x-livewire-input>
        </div>
        <div class="flex flex-col items-start space-y-1">
            <div class="flex flex-row-reverse">
                <x-label for="education.calculate_as_seniority">{{ __('Calculate as seniority') }}</x-label>
                <x-checkbox name="education.calculate_as_seniority" model="education.calculate_as_seniority"></x-checkbox>
            </div>
            <div class="flex flex-row-reverse">
                <x-label for="education.is_military">{{ __('Is military?') }}</x-label>
                <x-checkbox name="education.is_military" model="education.is_military"></x-checkbox>
            </div>
        </div>

    </div>


</div>
@if(Arr::has($education,['admission_year']) && !empty($education['admission_year']))
<div class="my-2 flex justify-between items-center border border-gray-200 p-2 shadow-sm bg-gray-50 rounded-lg">
        <div class="flex space-x-2 items-center">
            <span class="font-medium text-gray-500">{{ __('Duration') }}:</span>
            <span class="font-medium text-gray-900">
                {{ $calculatedDataEducation['diff'] }} {{ __('month') }}
                ({{ $calculatedDataEducation['year'] }} {{ __('year') }}
                {{ $calculatedDataEducation['month'] }} {{ __('month') }} )
            </span>
        </div>
        @if($education['coefficient'] > 0)
        <div class="flex space-x-2 items-center">
            <span class="font-medium text-gray-500">{{ __('Coefficient') }}:</span>
            <span class="font-medium text-teal-500">{{ $education['coefficient'] }}</span>
        </div>
        <div class="flex space-x-2 items-center">
            <span class="font-medium text-gray-500">{{ __('Extra seniority') }}:</span>
            <span class="font-medium text-rose-500">
                {{ $calculatedDataEducation['duration'] }} {{ __('month') }}
                ({{ $calculatedDataEducation['year_coefficient'] }} {{ __('year') }}
                {{ $calculatedDataEducation['month_coefficient'] }} {{ __('month') }})
            </span>
        </div>
        @endif
</div>
@endif
<hr>
<div class="flex justify-center items-center px-4 py-3 rounded-xl shadow-sm w-full bg-slate-100 space-x-2">
    <span class="text-lg">{{ __('Extra Education') }}</span>
    <x-checkbox name="hasExtraEducation" model="hasExtraEducation">{{ __('Has extra education?') }}</x-checkbox>
</div>

@if($hasExtraEducation)
<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Education type')" mode="gray" :selected="$educationTypeName" name="educationTypeId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchEducationType" wire:model.live="searchEducationType"></x-livewire-input>

            <x-select-list-item wire:click="setData('extra_education','education_type_id','educationType','---',null)" :selected="'---' == $educationTypeName"
              wire:model='extra_education.education_type_id'>
              ---
            </x-select-list-item>
            @foreach($education_types as $type)
            <x-select-list-item wire:click="setData('extra_education','education_type_id','educationType','{{ $type->name }}',{{ $type->id }})"
              :selected="$type->id === $educationTypeId" wire:model='extra_education.education_type_id'>
                {{ $type->name }}
            </x-select-list-item>
            @endforeach
        </x-select-list>
        @error('extra_education.education_type_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Education place')" mode="gray" :selected="$extraInstitutionName" name="extraInstitutionId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchExtraInstitution" wire:model.live="searchExtraInstitution"></x-livewire-input>

            <x-select-list-item wire:click="setData('extra_education','educational_institution_id','extraInstitution','---',null)" :selected="'---' == $extraInstitutionName"
              wire:model='extra_education.educational_institution_id.id'>
              ---
            </x-select-list-item>
            @foreach($institutions as $institution)
            <x-select-list-item wire:click="setData('extra_education','educational_institution_id','extraInstitution','{{ $institution->name }}',{{ $institution->id }})"
              :selected="$institution->id === $extraInstitutionId" wire:model='extra_education.educational_institution_id.id'>
                {{ $institution->name }}
            </x-select-list-item>
            @endforeach
        </x-select-list>
        @error('extra_education.educational_institution_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Education form')" mode="gray" :selected="$extraEducationFormName" name="extraEducationFormId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchExtraEducationForm" wire:model.live="searchExtraEducationForm"></x-livewire-input>

            <x-select-list-item wire:click="setData('extra_education','education_form_id','extraEducationForm','---',null)" :selected="'---' == $extraEducationFormName"
              wire:model='extra_education.education_form_id.id'>
              ---
            </x-select-list-item>
            @foreach($education_forms as $ef)
            <x-select-list-item wire:click="setData('extra_education','education_form_id','extraEducationForm','{{ $ef->name }}',{{ $ef->id }})"
              :selected="$ef->id === $extraEducationFormId" wire:model='extra_education.education_form_id.id'>
                {{ $ef->name }}
            </x-select-list-item>
            @endforeach
        </x-select-list>
        @error('extra_education.education_form_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>

</div>

<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="extra_education.name">{{ __('Name') }}</x-label>
        <x-livewire-input mode="gray" name="extra_education.name" wire:model="extra_education.name"></x-livewire-input>
        @error('extra_education.name')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="extra_education.shortname">{{ __('Shortname') }}</x-label>
        <x-livewire-input mode="gray" name="extra_education.shortname" wire:model="extra_education.shortname"></x-livewire-input>
        @error('extra_education.shortname')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="extra_education.education_language">{{ __('Education language') }}</x-label>
        <x-livewire-input mode="gray" name="extra_education.education_language" wire:model="extra_education.education_language"></x-livewire-input>
        @error('extra_education.education_language')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>

<div class="grid grid-cols-4 gap-2">
    <div class="flex flex-col">
        <x-label for="extra_education.education_program_name">{{ __('Program name') }}</x-label>
        <x-livewire-input mode="gray" name="extra_education.education_program_name" wire:model="extra_education.education_program_name"></x-livewire-input>
        @error('extra_education.education_program_name')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="extra_education.admission_year">{{ __('Admission year') }}</x-label>
        <x-pikaday-input mode="gray" name="extra_education.admission_year" format="Y-MM-DD" wire:model.live="extra_education.admission_year">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('extra_education.admission_year', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
        @error('extra_education.admission_year')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="extra_education.graduated_year">{{ __('Graduated year') }}</x-label>
        <x-pikaday-input mode="gray" name="extra_education.graduated_year" format="Y-MM-DD" wire:model.live="extra_education.graduated_year">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('extra_education.graduated_year', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
        @error('extra_education.graduated_year')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-select-list class="w-full" :title="__('Document type')" mode="gray" :selected="$educationDocumentTypeName" name="educationDocumentTypeId">
            <x-livewire-input  @click.stop="open = true" mode="gray" name="searchDocumentTyoe" wire:model.live="searchDocumentTyoe"></x-livewire-input>

            <x-select-list-item wire:click="setData('extra_education','education_document_type_id','educationDocumentType','---',null)" :selected="'---' == $extraEducationFormName"
              wire:model='extra_education.education_document_type_id.id'>
              ---
            </x-select-list-item>
            @foreach($document_types as $dt)
            <x-select-list-item wire:click="setData('extra_education','education_document_type_id','educationDocumentType','{{ $dt->name }}',{{ $dt->id }})"
              :selected="$dt->id === $educationDocumentTypeId" wire:model='extra_education.education_document_type_id.id'>
                {{ $dt->name }}
            </x-select-list-item>
            @endforeach
        </x-select-list>
        @error('extra_education.education_document_type_id.id')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>
<div class="grid grid-cols-3 gap-2">
    <div class="grid grid-cols-3 gap-2">
        <div class="flex flex-col">
            <x-label for="extra_education.diplom_serie">{{ __('Diplom serie') }}</x-label>
            <x-livewire-input mode="gray" name="extra_education.diplom_serie" wire:model="extra_education.diplom_serie"></x-livewire-input>
            @error('extra_education.diplom_serie')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
        <div class="flex flex-col col-span-2">
            <x-label for="extra_education.diplom_no">{{ __('Diplom no') }}</x-label>
            <x-livewire-input type="number" mode="gray" name="extra_education.diplom_no" wire:model="extra_education.diplom_no"></x-livewire-input>
            @error('extra_education.diplom_no')
            <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>

    <div class="flex flex-col">
        <x-label for="extra_education.diplom_given_date">{{ __('Diplom given date') }}</x-label>
        <x-pikaday-input mode="gray" name="extra_education.diplom_given_date" format="Y-MM-DD" wire:model.live="extra_education.diplom_given_date">
            <x-slot name="script">
              $el.onchange = function () {
              @this.set('extra_education.diplom_given_date', $el.value);
              }
            </x-slot>
          </x-pikaday-input>
        @error('extra_education.diplom_given_date')
        <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>


    <div class="flex space-x-2 items-start">
        <div class="flex flex-col">
            <x-label for="extra_education.coefficient">{{ __('Coefficient') }}</x-label>
            <x-livewire-input mode="gray" type="number" name="extra_education.coefficient" wire:model.live="extra_education.coefficient"></x-livewire-input>
        </div>

        <div class="flex flex-col items-start space-y-1">
            <div class="flex flex-row-reverse">
                <x-label for="extra_education.calculate_as_seniority">{{ __('Calculate as seniority') }}</x-label>
                <x-checkbox name="extra_education.calculate_as_seniority" model="extra_education.calculate_as_seniority"></x-checkbox>
            </div>
            <div class="flex flex-row-reverse">
                <x-label for="extra_education.is_military">{{ __('Is military?') }}</x-label>
                <x-checkbox name="extra_education.is_military" model="extra_education.is_military"></x-checkbox>
            </div>
        </div>
    </div>
</div>
<div class="flex justify-end">
    <x-button  mode="black" wire:click="addEducation">{{ __('Add') }}</x-button>
</div>

<div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
    <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
        <x-table.tbl :headers="[__('Education place'),__('Info'),__('Duration'),'action']">
            @forelse ($extra_education_list as $key => $eeModel)
            <tr>
                <x-table.td>
                    <div class="flex flex-col">
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Name') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $eeModel['name'] }} ({{ $eeModel['shortname'] }})
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Type') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $eeModel['education_type_id']['name'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Form') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $eeModel['education_form_id']['name'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Language') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $eeModel['education_language']}}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Category') }}:</span>
                            <span @class([
                                    'text-sm font-medium',
                                    'text-emerald-500' => !$eeModel['is_military'],
                                    'text-rose-500' => $eeModel['is_military']
                            ])>
                                {{ $eeModel['is_military'] ? __('Military') : __('Property')}}
                           </span>
                        </div>
                    </div>

                </x-table.td>
                <x-table.td>
                    <div class="flex flex-col">
                        <div class="flex space-x-1 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                {{ __('Program') }}:
                            </span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $eeModel['education_program_name']}}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Document') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $eeModel['education_document_type_id']['name'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Serial number') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $eeModel['diplom_serie'] }}{{ $eeModel['diplom_no'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Given date') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ \Carbon\Carbon::parse($eeModel['diplom_given_date'])->format('d.m.Y') }}
                           </span>
                        </div>
                    </div>
                </x-table.td>
                <x-table.td>
                    <div class="flex flex-col">
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Admission year') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $eeModel['admission_year'] }}
                           </span>
                        </div>
                        <div class="flex space-x-2">
                            <span class="text-sm text-gray-500 font-medium">{{ __('Graduated year') }}:</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $eeModel['graduated_year'] }}
                           </span>
                        </div>

                        <div class="flex space-x-1 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                {{ __('Duration') }}:
                            </span>
                            <span class="text-sm font-medium text-gray-800">
                                {{ $calculatedDataExtraEducation['data'][$key]['duration']['year'] }} {{ __('year') }}
                                {{ $calculatedDataExtraEducation['data'][$key]['duration']['month'] }} {{ __('month') }}
                                ({{ $calculatedDataExtraEducation['data'][$key]['duration']['diff'] }} {{ __('month') }})
                            </span>
                        </div>
                        @if(!empty($eeModel['coefficient']))
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                {{ __('Coefficient') }}:
                            </span>
                            <span class="text-sm font-medium text-blue-500">
                                {{ $eeModel['coefficient'] }}
                            </span>
                        </div>
                        <div class="flex space-x-2 items-center">
                            <span class="text-sm font-medium text-gray-500">
                                {{ __('Extra seniority') }}:
                            </span>
                            <span class="text-sm font-medium text-blue-500">
                                {{ $calculatedDataExtraEducation['data'][$key]['coefficient']['year'] }} {{ __('year') }}
                                {{ $calculatedDataExtraEducation['data'][$key]['coefficient']['month'] }} {{ __('month') }}
                                ({{$calculatedDataExtraEducation['data'][$key]['duration']['duration'] }} {{ __('month')}})
                            </span>
                        </div>
                        @endif
                    </div>
                </x-table.td>
                <x-table.td :isButton="true">
                     <button
                        onclick="confirm('Are you sure you want to remove this?') || event.stopImmediatePropagation()"
                        wire:click="forceDeleteData({{ $key }})"
                        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase transition duration-300 rounded-lg text-gray-500 hover:bg-red-50 hover:text-gray-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-500">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
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
        @if(count($extra_education_list) > 0)
        <div class="my-2 flex justify-between items-center border border-gray-300 p-2 shadow-sm bg-gray-50 rounded-lg">
            <div class="flex space-x-2 items-center">
                <span class="font-medium text-gray-500">{{ __('Total duration') }}:</span>
                <span class="font-medium text-gray-900">
                    {{ $calculatedDataExtraEducation['total_duration'] }} {{ __('month') }}
                    ( {{ $calculatedDataExtraEducation['total_duration_diff']['year'] }} {{ __('year') }}
                    {{ $calculatedDataExtraEducation['total_duration_diff']['month'] }} {{ __('month')  }})
                </span>
            </div>
            <div class="flex space-x-2 items-center">
                <span class="font-medium text-gray-500">{{ __('Extra seniority') }}:</span>
                <span class="font-medium text-rose-500">
                    {{ $calculatedDataExtraEducation['extra_seniority'] }} {{ __('month') }}
                    ( {{ $calculatedDataExtraEducation['extra_seniority_full']['year'] }} {{ __('year') }}
                    {{ $calculatedDataExtraEducation['extra_seniority_full']['month'] }} {{ __('month')  }})
                </span>
            </div>

        </div>
        @endif
    </div>
</div>


@endif
