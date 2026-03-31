<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.name">{{ __('candidates::common.labels.name') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.name" wire:model="candidate.name"></x-livewire-input>
        @error('candidate.name') <x-validation>{{ $message }}</x-validation> @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.surname">{{ __('candidates::common.labels.surname') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.surname" wire:model="candidate.surname"></x-livewire-input>
        @error('candidate.surname') <x-validation>{{ $message }}</x-validation> @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.patronymic">{{ __('candidates::common.labels.patronymic') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.patronymic" wire:model="candidate.patronymic"></x-livewire-input>
        @error('candidate.patronymic') <x-validation>{{ $message }}</x-validation> @enderror
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
        />
        @error('candidate.structure_id') <x-validation>{{ $message }}</x-validation> @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.phone">{{ __('candidates::common.labels.phone') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.phone" wire:model="candidate.phone"></x-livewire-input>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.birthdate">{{ __('candidates::common.labels.birthdate') }}</x-label>
        <x-pikaday-input mode="gray" name="candidate.birthdate" format="Y-MM-DD" wire:model.live="candidate.birthdate">
            <x-slot name="script">
                $el.onchange = function () { @this.set('candidate.birthdate', $el.value); }
            </x-slot>
        </x-pikaday-input>
        @error('candidate.birthdate') <x-validation>{{ $message }}</x-validation> @enderror
    </div>
    <div class="flex flex-col space-y-1">
        <x-label for="candidate.gender">{{ __('candidates::common.labels.gender') }}</x-label>
        <div class="flex flex-row">
            @foreach (\App\Enums\GenderEnum::genderOptions() as $value => $label)
                <label class="inline-flex items-center px-2 py-2 bg-gray-100 rounded shadow-sm">
                    <input type="radio" class="form-radio" name="candidate.gender" wire:model="candidate.gender" value="{{ $value }}">
                    <span class="ml-2 text-sm font-normal">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('candidate.gender') <x-validation>{{ $message }}</x-validation> @enderror
    </div>
</div>

<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.application_date">{{ __('candidates::common.labels.application_date') }}</x-label>
        <x-pikaday-input mode="gray" name="candidate.application_date" format="Y-MM-DD" wire:model.live="candidate.application_date">
            <x-slot name="script">
                $el.onchange = function () { @this.set('candidate.application_date', $el.value); }
            </x-slot>
        </x-pikaday-input>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.appeal_date">{{ __('candidates::common.labels.appeal_date') }}</x-label>
        <x-pikaday-input mode="gray" name="candidate.appeal_date" format="Y-MM-DD" wire:model.live="candidate.appeal_date">
            <x-slot name="script">
                $el.onchange = function () { @this.set('candidate.appeal_date', $el.value); }
            </x-slot>
        </x-pikaday-input>
    </div>
    <div class="flex flex-col">
        @php
            $statusOptions = $statuses->map(fn ($status) => ['id' => $status->id, 'label' => trim($status->name)])->values()->all();
        @endphp
        <x-ui.select-dropdown
            :label="__('candidates::common.labels.status')"
            placeholder="---"
            mode="gray"
            class="w-full"
            wire:model.live="candidate.status_id"
            :model="$statusOptions"
        />
        @error('candidate.status_id') <x-validation>{{ $message }}</x-validation> @enderror
    </div>
</div>
