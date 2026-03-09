<div class="sidemenu-title">
    <h2 class="text-xl font-semibold text-gray-500 font-title" id="slide-over-title">
        {!! $title ?? '' !!}
    </h2>
    <div class="mt-1">
        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
            {{ __('candidates::common.labels.mode') }}: {{ $this->candidateModeLabel() }}
        </span>
    </div>
</div>

<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.name">{{ __('candidates::common.labels.name') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.name" wire:model="candidate.name"></x-livewire-input>
        @error('candidate.name')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.surname">{{ __('candidates::common.labels.surname') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.surname" wire:model="candidate.surname"></x-livewire-input>
        @error('candidate.surname')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.patronymic">{{ __('candidates::common.labels.patronymic') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.patronymic"
            wire:model="candidate.patronymic"></x-livewire-input>
        @error('candidate.patronymic')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>
<div class="grid grid-cols-4 gap-2">
    <div class="flex flex-col">
        <x-ui.select-dropdown
            :label="__('candidates::common.labels.structure')"
            placeholder="---"
            mode="gray"
            class="w-full"
            wire:model.live="candidate.structure_id"
            :model="$this->structureOptions"
                    search-model="searchStructure"
        >
        </x-ui.select-dropdown>
        @error('candidate.structure_id')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.height">{{ __('candidates::common.labels.height') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.height" wire:model="candidate.height"></x-livewire-input>
        @error('candidate.height')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    @if ($this->isMilitaryCandidateMode())
        <div class="flex flex-col">
            <x-label for="candidate.military_service">{{ __('candidates::common.labels.military_service') }}</x-label>
            <x-livewire-input mode="gray" name="candidate.military_service"
                wire:model="candidate.military_service"></x-livewire-input>
        </div>
    @endif
    <div class="flex flex-col">
        <x-label for="candidate.phone">{{ __('candidates::common.labels.phone') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.phone" wire:model="candidate.phone"></x-livewire-input>
    </div>
</div>

<div class="grid grid-cols-6 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.knowledge_test">{{ __('candidates::common.labels.knowledge_test') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.knowledge_test"
            wire:model="candidate.knowledge_test"></x-livewire-input>
        @error('candidate.knowledge_test')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    @if ($this->isMilitaryCandidateMode())
        <div class="flex flex-col">
            <x-label for="candidate.physical_fitness_exam">{{ __('candidates::common.labels.physical_fitness') }}</x-label>
            <x-livewire-input mode="gray" name="candidate.physical_fitness_exam"
                wire:model="candidate.physical_fitness_exam"></x-livewire-input>
            @error('candidate.physical_fitness_exam')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    @endif
    <div class="flex flex-col col-span-2">
        <x-label for="candidate.research_date">{{ __('candidates::common.labels.research_date') }}</x-label>
        <x-pikaday-input mode="gray" name="candidate.research_date" format="Y-MM-DD"
            wire:model.live="candidate.research_date">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('candidate.research_date', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
    </div>
    <div class="flex flex-col col-span-2">
        <x-label for="candidate.research_result">{{ __('candidates::common.labels.research_result') }}</x-label>
        <div class="flex flex-row">
            @foreach (\App\Enums\ResearchResultEnum::values() as $researchResult)
                <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                    <input type="radio" class="form-radio" name="candidate.research_result"
                        wire:model="candidate.research_result" value="{{ $researchResult }}">
                    <span class="ml-2 text-sm font-normal">{{ $researchResult }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-4 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.examination_date">{{ __('candidates::common.labels.examination_date') }}</x-label>
        <x-pikaday-input mode="gray" name="candidate.examination_date" format="Y-MM-DD"
            wire:model.live="candidate.examination_date">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('candidate.examination_date', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.appeal_date">{{ __('candidates::common.labels.appeal_date') }}</x-label>
        <x-pikaday-input mode="gray" name="candidate.appeal_date" format="Y-MM-DD"
            wire:model.live="candidate.appeal_date">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('candidate.appeal_date', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.application_date">{{ __('candidates::common.labels.application_date') }}</x-label>
        <x-pikaday-input mode="gray" name="candidate.application_date" format="Y-MM-DD"
            wire:model.live="candidate.application_date">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('candidate.application_date', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.requisition_date">{{ __('candidates::common.labels.requisition_date') }}</x-label>
        <x-pikaday-input mode="gray" name="candidate.requisition_date" format="Y-MM-DD"
            wire:model.live="candidate.requisition_date">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('candidate.requisition_date', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
    </div>
</div>

<div class="grid grid-cols-4 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.initial_documents">{{ __('candidates::common.labels.initial_documents') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.initial_documents"
            wire:model="candidate.initial_documents"></x-livewire-input>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.documents_completeness">{{ __('candidates::common.labels.documents_completeness') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.documents_completeness"
            wire:model="candidate.documents_completeness"></x-livewire-input>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.birthdate">{{ __('candidates::common.labels.birthdate') }}</x-label>
        <x-pikaday-input mode="gray" name="candidate.birthdate" format="Y-MM-DD"
            wire:model.live="candidate.birthdate">
            <x-slot name="script">
                $el.onchange = function () {
                @this.set('candidate.birthdate', $el.value);
                }
            </x-slot>
        </x-pikaday-input>
        @error('candidate.birthdate')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
    <div class="flex flex-col space-y-1">
        <x-label for="candidate.gender">{{ __('candidates::common.labels.gender') }}</x-label>
        <div class="flex flex-row">
            @foreach (\App\Enums\GenderEnum::genderOptions() as $value => $label)
                <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                    <input type="radio" class="form-radio" name="candidate.gender" wire:model="candidate.gender"
                        value="{{ $value }}">
                    <span class="ml-2 text-sm font-normal">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('candidate.gender')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>

@if ($this->isMilitaryCandidateMode())
    <div class="grid grid-cols-4 gap-2">
        <div class="flex flex-col">
            <x-label for="candidate.hhk_date">{{ __('candidates::common.labels.hhk_date') }}</x-label>
            <x-pikaday-input mode="gray" name="candidate.hhk_date" format="Y-MM-DD"
                wire:model.live="candidate.hhk_date">
                <x-slot name="script">
                    $el.onchange = function () {
                    @this.set('candidate.hhk_date', $el.value);
                    }
                </x-slot>
            </x-pikaday-input>
        </div>

        <div class="flex flex-col space-y-1">
            <x-label for="candidate.hhk_result">{{ __('candidates::common.labels.hhk_result') }}</x-label>
            <div class="flex flex-row w-full">
                @foreach (\App\Enums\MilitaryStatusEnum::values() as $military)
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="candidate.hhk_result"
                            wire:model.live="candidate.hhk_result" value="{{ $military }}">
                        <span class="ml-2 text-sm font-normal">{{ $military }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        @if (array_key_exists('hhk_result', $candidate) &&
                $candidate['hhk_result'] == \App\Enums\MilitaryStatusEnum::Useless->value)
            <div class="flex flex-col">
                <x-label for="candidate.useless_info">{{ __('candidates::common.labels.useless_information') }}</x-label>
                <x-livewire-input mode="gray" name="candidate.useless_info"
                    wire:model="candidate.useless_info"></x-livewire-input>
            </div>
        @endif

        <div class="flex flex-col space-y-1">
            <x-label for="candidate.attitude_to_military">{{ __('candidates::common.labels.attitude_to_military') }}</x-label>
            <div class="flex flex-row">
                @foreach (\App\Enums\AttitudeMilitaryEnum::values() as $attitude)
                    <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                        <input type="radio" class="form-radio" name="candidate.attitude_to_military"
                            wire:model="candidate.attitude_to_military" value="{{ $attitude }}">
                        <span class="ml-2 text-sm font-normal">{{ $attitude }}</span>
                    </label>
                @endforeach
            </div>
            @error('candidate.attitude_to_military')
                <x-validation> {{ $message }} </x-validation>
            @enderror
        </div>
    </div>
@endif

<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.characteristics">{{ __('candidates::common.labels.characteristics') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.characteristics"
            wire:model="candidate.characteristics"></x-livewire-input>
    </div>
</div>

<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.discrediting_information">{{ __('candidates::common.labels.discrediting_information') }}</x-label>
        <x-textarea mode="gray" name="candidate.discrediting_information" placeholder=""
            wire:model="candidate.discrediting_information"></x-textarea>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.note">{{ __('candidates::common.labels.note') }}</x-label>
        <x-textarea mode="gray" name="candidate.note" placeholder=""
            wire:model="candidate.note"></x-textarea>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.presented_by">{{ __('candidates::common.labels.presented_by') }}</x-label>
        <x-textarea mode="gray" name="candidate.presented_by" placeholder=""
            wire:model="candidate.presented_by"></x-textarea>
    </div>
</div>

<div class="grid grid-cols-2 gap-2">
    <div class="flex flex-col">
        @php
            $statusOptions = $statuses->map(fn ($status) => [
                'id' => $status->id,
                'label' => trim($status->name),
            ])->values()->all();
        @endphp
        <x-ui.select-dropdown
            :label="__('candidates::common.labels.status')"
            placeholder="---"
            mode="gray"
            class="w-full"
            wire:model.live="candidate.status_id"
            :model="$statusOptions"
        />
        @error('candidate.status_id')
            <x-validation> {{ $message }} </x-validation>
        @enderror
    </div>
</div>

<div class="flex items-end justify-between w-full">
    <x-modal-button>{{ __('candidates::common.labels.save') }}</x-modal-button>
</div>
