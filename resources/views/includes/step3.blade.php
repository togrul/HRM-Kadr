@php
    use Illuminate\Support\Arr;

    $educationState = $educationForm->education ?? [];
    $extraEducationState = $educationForm->extraEducation ?? [];
    $extraEducationList = $educationForm->extraEducationList ?? [];
@endphp

<div class="flex flex-col space-y-4">
    <x-form-card title="{{ __('Education') }}">
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('Education place') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.education.educational_institution_id"
                    :model="$this->educationInstitutionOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchEducationInstitution"
                        wire:model.live.debounce.300ms="searchEducationInstitution"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('educationForm.education.educational_institution_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('Education form') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.education.education_form_id"
                    :model="$this->educationFormOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchEducationForm"
                        wire:model.live.debounce.300ms="searchEducationForm"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('educationForm.education.education_form_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.education.education_language">{{ __('Education language') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.education.education_language" wire:model="educationForm.education.education_language"></x-livewire-input>
                @error('educationForm.education.education_language')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-4 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.education.specialty">{{ __('Specialty') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.education.specialty" wire:model="educationForm.education.specialty"></x-livewire-input>
                @error('educationForm.education.specialty')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.education.admission_year">{{ __('Admission year') }}</x-label>
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
                <x-label for="educationForm.education.graduated_year">{{ __('Graduated year') }}</x-label>
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
                <x-label for="educationForm.education.profession_by_document">{{ __('Profession') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.education.profession_by_document" wire:model="educationForm.education.profession_by_document"></x-livewire-input>
                @error('educationForm.education.profession_by_document')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.education.diplom_serie">{{ __('Diplom serie') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.education.diplom_serie" wire:model="educationForm.education.diplom_serie"></x-livewire-input>
                @error('educationForm.education.diplom_serie')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.education.diplom_no">{{ __('Diplom no') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="educationForm.education.diplom_no" wire:model="educationForm.education.diplom_no"></x-livewire-input>
                @error('educationForm.education.diplom_no')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.education.diplom_given_date">{{ __('Diplom given date') }}</x-label>
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
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.education.coefficient">{{ __('Coefficient') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="educationForm.education.coefficient" wire:model.live="educationForm.education.coefficient"></x-livewire-input>
            </div>
            <div class="flex flex-col items-start space-y-1">
                <div class="flex flex-row-reverse">
                    <x-label for="educationForm.education.calculate_as_seniority">{{ __('Calculate as seniority') }}</x-label>
                    <x-checkbox name="educationForm.education.calculate_as_seniority" model="educationForm.education.calculate_as_seniority"></x-checkbox>
                </div>
                <div class="flex flex-row-reverse">
                    <x-label for="educationForm.education.is_military">{{ __('Is military?') }}</x-label>
                    <x-checkbox name="educationForm.education.is_military" model="educationForm.education.is_military"></x-checkbox>
                </div>
            </div>
        </div>
        @if(Arr::has($educationState, ['admission_year']) && ! empty(Arr::get($educationState, 'admission_year')))
            <div class="flex items-center justify-between p-2 my-2 border border-gray-200 rounded-lg shadow-sm bg-gray-50">
                <div class="flex items-center space-x-2">
                    <span class="font-medium text-gray-500">{{ __('Duration') }}:</span>
                    @if(! empty($calculatedDataEducation))
                        <span class="font-medium text-gray-900">
                            {{ $calculatedDataEducation['diff'] }} {{ __('month') }}
                            ({{ $calculatedDataEducation['year'] }} {{ __('year') }}
                            {{ $calculatedDataEducation['month'] }} {{ __('month') }}
                            {{ $calculatedDataEducation['day'] ?? 0 }} {{ __('day') }})
                        </span>
                    @endif
                </div>

                @if(Arr::get($educationState, 'coefficient') > 0)
                    <div class="flex items-center space-x-2">
                        <span class="font-medium text-gray-500">{{ __('Coefficient') }}:</span>
                        <span class="font-medium text-teal-500">{{ $educationState['coefficient'] }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="font-medium text-gray-500">{{ __('Extra seniority') }}:</span>
                        @if(! empty($calculatedDataEducation))
                            <span class="font-medium text-rose-500">
                                {{ $calculatedDataEducation['duration'] }} {{ __('month') }}
                                ({{ $calculatedDataEducation['year_coefficient'] }} {{ __('year') }}
                                {{ $calculatedDataEducation['month_coefficient'] }} {{ __('month') }})
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </x-form-card>
</div>

<x-form-card
    title="{{ __('Extra Education') }}"
    checkbox="hasExtraEducation"
    checkboxTitle="{{ __('Has extra education?') }}"
>
    @if($hasExtraEducation)
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('Education type') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.extraEducation.education_type_id"
                    :model="$this->educationTypeOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchEducationType"
                        wire:model.live.debounce.300ms="searchEducationType"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('educationForm.extraEducation.education_type_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('Education place') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.extraEducation.educational_institution_id"
                    :model="$this->extraEducationInstitutionOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchExtraEducationInstitution"
                        wire:model.live.debounce.300ms="searchExtraEducationInstitution"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('educationForm.extraEducation.educational_institution_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-ui.select-dropdown
                    label="{{ __('Education form') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.extraEducation.education_form_id"
                    :model="$this->extraEducationFormOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchExtraEducationForm"
                        wire:model.live.debounce.300ms="searchExtraEducationForm"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('educationForm.extraEducation.education_form_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.name">{{ __('Name') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.extraEducation.name" wire:model="educationForm.extraEducation.name"></x-livewire-input>
                @error('educationForm.extraEducation.name')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.shortname">{{ __('Shortname') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.extraEducation.shortname" wire:model="educationForm.extraEducation.shortname"></x-livewire-input>
                @error('educationForm.extraEducation.shortname')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.education_language">{{ __('Education language') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.extraEducation.education_language" wire:model="educationForm.extraEducation.education_language"></x-livewire-input>
                @error('educationForm.extraEducation.education_language')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.education_program_name">{{ __('Program name') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.extraEducation.education_program_name" wire:model="educationForm.extraEducation.education_program_name"></x-livewire-input>
                @error('educationForm.extraEducation.education_program_name')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.admission_year">{{ __('Admission year') }}</x-label>
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
                <x-label for="educationForm.extraEducation.graduated_year">{{ __('Graduated year') }}</x-label>
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
                    label="{{ __('Document type') }}"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="educationForm.extraEducation.education_document_type_id"
                    :model="$this->educationDocumentTypeOptions"
                >
                    <x-livewire-input
                        mode="gray"
                        name="searchEducationDocumentType"
                        wire:model.live.debounce.300ms="searchEducationDocumentType"
                        @click.stop="isOpen = true"
                        x-on:input.stop="null"
                        x-on:keyup.stop="null"
                        x-on:keydown.stop="null"
                        x-on:change.stop="null"
                    />
                </x-ui.select-dropdown>
                @error('educationForm.extraEducation.education_document_type_id')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.diplom_serie">{{ __('Diplom serie') }}</x-label>
                <x-livewire-input mode="gray" name="educationForm.extraEducation.diplom_serie" wire:model="educationForm.extraEducation.diplom_serie"></x-livewire-input>
                @error('educationForm.extraEducation.diplom_serie')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.diplom_no">{{ __('Diplom no') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="educationForm.extraEducation.diplom_no" wire:model="educationForm.extraEducation.diplom_no"></x-livewire-input>
                @error('educationForm.extraEducation.diplom_no')
                <x-validation>{{ $message }}</x-validation>
                @enderror
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div class="flex flex-col">
                <x-label for="educationForm.extraEducation.diplom_given_date">{{ __('Diplom given date') }}</x-label>
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
                <x-label for="educationForm.extraEducation.coefficient">{{ __('Coefficient') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="educationForm.extraEducation.coefficient" wire:model.live="educationForm.extraEducation.coefficient"></x-livewire-input>
            </div>
            <div class="flex flex-col items-start space-y-1">
                <div class="flex flex-row-reverse">
                    <x-label for="educationForm.extraEducation.calculate_as_seniority">{{ __('Calculate as seniority') }}</x-label>
                    <x-checkbox name="educationForm.extraEducation.calculate_as_seniority" model="educationForm.extraEducation.calculate_as_seniority"></x-checkbox>
                </div>
                <div class="flex flex-row-reverse">
                    <x-label for="educationForm.extraEducation.is_military">{{ __('Is military?') }}</x-label>
                    <x-checkbox name="educationForm.extraEducation.is_military" model="educationForm.extraEducation.is_military"></x-checkbox>
                </div>
            </div>
        </div>
        <div class="flex items-end justify-start">
            <x-button mode="black" wire:click="addExtraEducation">{{ __('Add') }}</x-button>
        </div>

        <div class="relative -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden border-b border-gray-200 shadow sm:rounded-lg">
                    <x-table.tbl :headers="[__('Serial number'), __('Given date'), __('Valid date'), __('Status'), '']">
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
                                        onclick="confirm('{{ __('Are you sure you want to remove this data?') }}') || event.stopImmediatePropagation()"
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
                                        <span class="font-medium">{{ __('No information added') }}</span>
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
                    <span class="font-medium text-gray-500">{{ __('Total duration') }}:</span>
                    <span class="font-medium text-gray-900">
                        {{ data_get($calculatedDataExtraEducation, 'total_duration', 0) }} {{ __('month') }}
                        ({{ data_get($calculatedDataExtraEducation, 'total_duration_diff.year', 0) }} {{ __('year') }}
                        {{ data_get($calculatedDataExtraEducation, 'total_duration_diff.month', 0) }} {{ __('month') }})
                        — {{ data_get($calculatedDataExtraEducation, 'total_duration_days', 0) }} {{ __('day') }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="font-medium text-gray-500">{{ __('Extra seniority') }}:</span>
                    <span class="font-medium text-rose-500">
                        {{ data_get($calculatedDataExtraEducation, 'extra_seniority', 0) }} {{ __('month') }}
                        ({{ data_get($calculatedDataExtraEducation, 'extra_seniority_full.year', 0) }} {{ __('year') }}
                        {{ data_get($calculatedDataExtraEducation, 'extra_seniority_full.month', 0) }} {{ __('month') }})
                        — {{ data_get($calculatedDataExtraEducation, 'extra_seniority_days', 0) }} {{ __('day') }}
                    </span>
                </div>
            </div>
        @endif
    @endif
</x-form-card>
