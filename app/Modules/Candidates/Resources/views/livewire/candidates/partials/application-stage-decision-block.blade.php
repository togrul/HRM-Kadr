@if ($this->isRejectStage())
    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.rejection_reason')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.rejection_reason_id"
                :model="$this->rejectionReasonOptions"
            />
            @error('form.rejection_reason_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="form.final_decision">{{ __('candidates::recruitment.labels.final_decision') }}</x-label>
            <x-livewire-input mode="gray" name="form.final_decision" wire:model="form.final_decision"></x-livewire-input>
            @error('form.final_decision') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
    </div>
@elseif ($this->isFinalStage())
    <div class="mt-4 flex flex-col">
        <x-label for="form.final_decision">{{ __('candidates::recruitment.labels.final_decision') }}</x-label>
        <x-livewire-input mode="gray" name="form.final_decision" wire:model="form.final_decision"></x-livewire-input>
        @error('form.final_decision') <x-validation>{{ $message }}</x-validation> @enderror
    </div>
@endif
