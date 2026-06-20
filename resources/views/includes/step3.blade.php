@php
    use App\Support\ViewNumberFormatter;
    use Illuminate\Support\Arr;

    $educationState = $educationForm->education ?? [];
    $extraEducationState = $educationForm->extraEducation ?? [];
    $extraEducationList = $educationForm->extraEducationList ?? [];
@endphp

<div class="flex flex-col space-y-4">
    <x-form-card title="{{ __('personnel::wizard.sections.education') }}">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('personnel::common.labels.education_place') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.education.educational_institution_id"
                    :model="$this->educationInstitutionOptions"
                    :search-model="data_get($stepSearchModels, 'searchEducationInstitution', 'searchEducationInstitution')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchEducationInstitution', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('educationForm.education.educational_institution_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('personnel::common.labels.education_form') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.education.education_form_id"
                    :model="$this->educationFormOptions"
                    :search-model="data_get($stepSearchModels, 'searchEducationForm', 'searchEducationForm')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchEducationForm', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('educationForm.education.education_form_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.education.education_language">{{ __('personnel::common.labels.education_language') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.education.education_language" wire:model="educationForm.education.education_language"></x-livewire-input>
                @error('educationForm.education.education_language')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.education.specialty">{{ __('personnel::common.labels.specialty') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.education.specialty" wire:model="educationForm.education.specialty"></x-livewire-input>
                @error('educationForm.education.specialty')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.education.admission_year">{{ __('personnel::common.labels.admission_year') }}</x-label>
                <x-pikaday-input mode="gray" name="educationForm.education.admission_year" format="Y-MM-DD" wire:model.live="educationForm.education.admission_year">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('educationForm.education.admission_year', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('educationForm.education.admission_year')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.education.graduated_year">{{ __('personnel::common.labels.graduated_year') }}</x-label>
                <x-pikaday-input mode="gray" name="educationForm.education.graduated_year" format="Y-MM-DD" wire:model.live="educationForm.education.graduated_year">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('educationForm.education.graduated_year', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('educationForm.education.graduated_year')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.education.profession_by_document">{{ __('personnel::common.labels.profession') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.education.profession_by_document" wire:model="educationForm.education.profession_by_document"></x-livewire-input>
                @error('educationForm.education.profession_by_document')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.education.diplom_serie">{{ __('personnel::common.labels.diplom_serie') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.education.diplom_serie" wire:model="educationForm.education.diplom_serie"></x-livewire-input>
                @error('educationForm.education.diplom_serie')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.education.diplom_no">{{ __('personnel::common.labels.diplom_no') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="educationForm.education.diplom_no" wire:model="educationForm.education.diplom_no"></x-livewire-input>
                @error('educationForm.education.diplom_no')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.education.diplom_given_date">{{ __('personnel::common.labels.diplom_given_date') }}</x-label>
                <x-pikaday-input mode="gray" name="educationForm.education.diplom_given_date" format="Y-MM-DD" wire:model.live="educationForm.education.diplom_given_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('educationForm.education.diplom_given_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('educationForm.education.diplom_given_date')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid items-end grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.education.coefficient">{{ __('personnel::common.labels.coefficient') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="educationForm.education.coefficient" wire:model.live="educationForm.education.coefficient"></x-livewire-input>
            </div>
            <div class="flex flex-col items-start space-y-1">
                <div class="flex flex-row-reverse">
                    <x-label for="educationForm.education.calculate_as_seniority">{{ __('personnel::common.labels.calculate_as_seniority') }}</x-label>
                    <x-checkbox name="educationForm.education.calculate_as_seniority" model="educationForm.education.calculate_as_seniority"></x-checkbox>
                </div>
                <div class="flex flex-row-reverse">
                    <x-label for="educationForm.education.is_military">{{ __('personnel::common.labels.is_military') }}</x-label>
                    <x-checkbox name="educationForm.education.is_military" model="educationForm.education.is_military"></x-checkbox>
                </div>
            </div>
        </div>
        @if(Arr::has($educationState, ['admission_year']) && ! empty(Arr::get($educationState, 'admission_year')))
            <div class="flex items-center justify-between p-2 my-2 border border-gray-200 rounded-lg shadow-sm bg-gray-50">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium text-gray-500 uppercase">{{ __('personnel::common.labels.duration') }}:</span>
                    @if(! empty($calculatedDataEducation))
                        <span class="font-medium text-gray-900">
                            {{ ViewNumberFormatter::decimal($calculatedDataEducation['diff']) }} {{ __('personnel::common.labels.month') }}
                            ({{ $calculatedDataEducation['year'] }} {{ __('personnel::common.labels.year') }}
                            {{ $calculatedDataEducation['month'] }} {{ __('personnel::common.labels.month') }}
                            {{ $calculatedDataEducation['day'] ?? 0 }} {{ __('personnel::common.labels.day') }})
                        </span>
                    @endif
                </div>

                @if(Arr::get($educationState, 'coefficient') > 0)
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-500 uppercase">{{ __('personnel::common.labels.coefficient') }}:</span>
                        <span class="font-medium text-teal-500">{{ ViewNumberFormatter::decimal($educationState['coefficient']) }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-500 uppercase">{{ __('personnel::common.labels.extra_seniority') }}:</span>
                        @if(! empty($calculatedDataEducation))
                            <span class="font-medium text-rose-500">
                                {{ ViewNumberFormatter::decimal($calculatedDataEducation['duration']) }} {{ __('personnel::common.labels.month') }}
                                ({{ $calculatedDataEducation['year_coefficient'] }} {{ __('personnel::common.labels.year') }}
                                {{ $calculatedDataEducation['month_coefficient'] }} {{ __('personnel::common.labels.month') }})
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </x-form-card>
</div>

<x-form-card
    title="{{ __('personnel::wizard.sections.extra_education') }}"
    checkbox="educationForm.hasExtraEducation"
    checkboxTitle="{{ __('personnel::wizard.questions.has_extra_education') }}"
>
    @if(data_get($educationForm ?? null, 'hasExtraEducation'))
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('personnel::common.labels.education_type') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.extraEducation.education_type_id"
                    :model="$this->educationTypeOptions"
                    :search-model="data_get($stepSearchModels, 'searchEducationType', 'searchEducationType')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchEducationType', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('educationForm.extraEducation.education_type_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('personnel::common.labels.education_place') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.extraEducation.educational_institution_id"
                    :model="$this->extraEducationInstitutionOptions"
                    :search-model="data_get($stepSearchModels, 'searchExtraEducationInstitution', 'searchExtraEducationInstitution')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchExtraEducationInstitution', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('educationForm.extraEducation.educational_institution_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('personnel::common.labels.education_form') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.extraEducation.education_form_id"
                    :model="$this->extraEducationFormOptions"
                    :search-model="data_get($stepSearchModels, 'searchExtraEducationForm', 'searchExtraEducationForm')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchExtraEducationForm', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('educationForm.extraEducation.education_form_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.name">{{ __('personnel::common.labels.name') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.extraEducation.name" wire:model="educationForm.extraEducation.name"></x-livewire-input>
                @error('educationForm.extraEducation.name')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.shortname">{{ __('personnel::common.labels.shortname') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.extraEducation.shortname" wire:model="educationForm.extraEducation.shortname"></x-livewire-input>
                @error('educationForm.extraEducation.shortname')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.education_language">{{ __('personnel::common.labels.education_language') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.extraEducation.education_language" wire:model="educationForm.extraEducation.education_language"></x-livewire-input>
                @error('educationForm.extraEducation.education_language')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.education_program_name">{{ __('personnel::common.labels.program_name') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.extraEducation.education_program_name" wire:model="educationForm.extraEducation.education_program_name"></x-livewire-input>
                @error('educationForm.extraEducation.education_program_name')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.admission_year">{{ __('personnel::common.labels.admission_year') }}</x-label>
                <x-pikaday-input mode="gray" name="educationForm.extraEducation.admission_year" format="Y-MM-DD" wire:model.live="educationForm.extraEducation.admission_year">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('educationForm.extraEducation.admission_year', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('educationForm.extraEducation.admission_year')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.graduated_year">{{ __('personnel::common.labels.graduated_year') }}</x-label>
                <x-pikaday-input mode="gray" name="educationForm.extraEducation.graduated_year" format="Y-MM-DD" wire:model.live="educationForm.extraEducation.graduated_year">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('educationForm.extraEducation.graduated_year', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('educationForm.extraEducation.graduated_year')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('personnel::common.labels.document_type') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.extraEducation.education_document_type_id"
                    :model="$this->educationDocumentTypeOptions"
                    :search-model="data_get($stepSearchModels, 'searchEducationDocumentType', 'searchEducationDocumentType')"
                    :search-placeholder="data_get($stepSearchPlaceholders, 'searchEducationDocumentType', __('personnel::common.placeholders.search'))"
                >
                </x-ui.select-dropdown>
                @error('educationForm.extraEducation.education_document_type_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.diplom_serie">{{ __('personnel::common.labels.diplom_serie') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.extraEducation.diplom_serie" wire:model="educationForm.extraEducation.diplom_serie"></x-livewire-input>
                @error('educationForm.extraEducation.diplom_serie')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.diplom_no">{{ __('personnel::common.labels.diplom_no') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="educationForm.extraEducation.diplom_no" wire:model="educationForm.extraEducation.diplom_no"></x-livewire-input>
                @error('educationForm.extraEducation.diplom_no')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.diplom_given_date">{{ __('personnel::common.labels.diplom_given_date') }}</x-label>
                <x-pikaday-input mode="gray" name="educationForm.extraEducation.diplom_given_date" format="Y-MM-DD" wire:model.live="educationForm.extraEducation.diplom_given_date">
                    <x-slot name="script">
                        $el.onchange = function () {
                        @this.set('educationForm.extraEducation.diplom_given_date', $el.value);
                        }
                    </x-slot>
                </x-pikaday-input>
                @error('educationForm.extraEducation.diplom_given_date')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.coefficient">{{ __('personnel::common.labels.coefficient') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="educationForm.extraEducation.coefficient" wire:model.live="educationForm.extraEducation.coefficient"></x-livewire-input>
            </div>
            <div class="flex flex-col items-start space-y-1">
                <div class="flex flex-row-reverse">
                    <x-label for="educationForm.extraEducation.calculate_as_seniority">{{ __('personnel::common.labels.calculate_as_seniority') }}</x-label>
                    <x-checkbox name="educationForm.extraEducation.calculate_as_seniority" model="educationForm.extraEducation.calculate_as_seniority"></x-checkbox>
                </div>
                <div class="flex flex-row-reverse">
                    <x-label for="educationForm.extraEducation.is_military">{{ __('personnel::common.labels.is_military') }}</x-label>
                    <x-checkbox name="educationForm.extraEducation.is_military" model="educationForm.extraEducation.is_military"></x-checkbox>
                </div>
            </div>
        </div>
        <div class="flex items-end justify-start">
            <x-button mode="black" wire:click="addExtraEducation">{{ __('personnel::common.actions.add') }}</x-button>
        </div>

        <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-visible">
                    <x-table.tbl :headers="[__('personnel::common.labels.serial_number'), __('personnel::common.labels.given_date'), __('personnel::common.labels.valid_date'), __('personnel::common.labels.status'), '']">
                        @forelse ($extraEducationList as $key => $extra)
                            <tr wire:key="extra-education-{{ $key }}">
                                <x-table.td>
                                    <span class="text-sm font-medium text-zinc-900">
                                        {{ $extra['diplom_no'] }}
                                   </span>
                                </x-table.td>
                                <x-table.td>
                                    <span class="text-sm font-medium">{{ $extra['diplom_given_date'] }}</span>
                                </x-table.td>
                                <x-table.td>
                                    <span class="text-sm font-medium">{{ $extra['graduated_year'] ?? '---' }}</span>
                                </x-table.td>
                                <x-table.td>
                                    <x-status-badge :valid="(bool) ($extra['calculate_as_seniority'] ?? false)"></x-status-badge>
                                </x-table.td>
                                <x-table.td :isButton="true">
                                    <button
                                        onclick="confirm('{{ __('personnel::common.messages.remove_data_confirm') }}') || event.stopImmediatePropagation()"
                                        wire:click="removeExtraEducation({{ $key }})"
                                        class="flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 uppercase transition duration-300 rounded-lg hover:bg-red-50 hover:text-gray-700"
                                    >
                                        <x-icons.force-delete></x-icons.force-delete>
                                    </button>
                                </x-table.td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="flex items-center justify-center py-4">
                                        <span class="font-medium">{{ __('personnel::common.labels.no_information_added') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </x-table.tbl>
                </div>
            </div>
        </div>

        @if(! empty($calculatedDataExtraEducation))
            <div class="flex flex-col p-2 my-2 space-y-2 border border-gray-200 rounded-lg shadow-sm bg-gray-50">
                <div class="flex items-center space-x-2">
                    <span class="font-medium text-gray-500">{{ __('personnel::common.labels.total_duration') }}:</span>
                    <span class="font-medium text-gray-900">
                        {{ ViewNumberFormatter::decimal(data_get($calculatedDataExtraEducation, 'total_duration', 0)) }} {{ __('personnel::common.labels.month') }}
                        ({{ data_get($calculatedDataExtraEducation, 'total_duration_diff.year', 0) }} {{ __('personnel::common.labels.year') }}
                        {{ data_get($calculatedDataExtraEducation, 'total_duration_diff.month', 0) }} {{ __('personnel::common.labels.month') }})
                        — {{ data_get($calculatedDataExtraEducation, 'total_duration_days', 0) }} {{ __('personnel::common.labels.day') }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="font-medium text-gray-500">{{ __('personnel::common.labels.extra_seniority') }}:</span>
                    <span class="font-medium text-rose-500">
                        {{ ViewNumberFormatter::decimal(data_get($calculatedDataExtraEducation, 'extra_seniority', 0)) }} {{ __('personnel::common.labels.month') }}
                        ({{ data_get($calculatedDataExtraEducation, 'extra_seniority_full.year', 0) }} {{ __('personnel::common.labels.year') }}
                        {{ data_get($calculatedDataExtraEducation, 'extra_seniority_full.month', 0) }} {{ __('personnel::common.labels.month') }})
                        — {{ data_get($calculatedDataExtraEducation, 'extra_seniority_days', 0) }} {{ __('personnel::common.labels.day') }}
                    </span>
                </div>
            </div>
        @endif
    @endif
</x-form-card>
