<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.knowledge_test">{{ __('candidates::common.labels.knowledge_test') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.knowledge_test" wire:model="candidate.knowledge_test"></x-livewire-input>
        @error('candidate.knowledge_test') <x-validation>{{ $message }}</x-validation> @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.height">{{ __('candidates::common.labels.height') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.height" wire:model="candidate.height"></x-livewire-input>
        @error('candidate.height') <x-validation>{{ $message }}</x-validation> @enderror
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.requisition_date">{{ __('candidates::common.labels.requisition_date') }}</x-label>
        <x-pikaday-input mode="gray" name="candidate.requisition_date" format="Y-MM-DD" wire:model.live="candidate.requisition_date">
            <x-slot name="script">
                $el.onchange = function () { @this.set('candidate.requisition_date', $el.value); }
            </x-slot>
        </x-pikaday-input>
    </div>
</div>

<div class="grid grid-cols-3 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.initial_documents">{{ __('candidates::common.labels.initial_documents') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.initial_documents" wire:model="candidate.initial_documents"></x-livewire-input>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.documents_completeness">{{ __('candidates::common.labels.documents_completeness') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.documents_completeness" wire:model="candidate.documents_completeness"></x-livewire-input>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.characteristics">{{ __('candidates::common.labels.characteristics') }}</x-label>
        <x-livewire-input mode="gray" name="candidate.characteristics" wire:model="candidate.characteristics"></x-livewire-input>
    </div>
</div>

<div class="grid grid-cols-2 gap-2">
    <div class="flex flex-col">
        <x-label for="candidate.note">{{ __('candidates::common.labels.note') }}</x-label>
        <x-textarea mode="gray" name="candidate.note" wire:model="candidate.note"></x-textarea>
    </div>
    <div class="flex flex-col">
        <x-label for="candidate.presented_by">{{ __('candidates::common.labels.presented_by') }}</x-label>
        <x-textarea mode="gray" name="candidate.presented_by" wire:model="candidate.presented_by"></x-textarea>
    </div>
</div>
