<div class="space-y-6">
    <div class="space-y-2">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">
            {{ $title }}
        </h2>
        <p class="text-sm leading-6 text-slate-500">
            {{ __('candidates::recruitment.labels.transition_note') }}
        </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.requisition')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.job_requisition_id"
                :model="$this->requisitionOptions"
                search-model="searchRequisition"
            />
            @error('form.job_requisition_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.owner')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.owner_id"
                :model="$this->ownerOptions"
                search-model="searchOwner"
            />
            @error('form.owner_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="flex flex-col">
            <x-label for="form.title">{{ __('candidates::recruitment.labels.title') }}</x-label>
            <x-livewire-input mode="gray" name="form.title" wire:model="form.title"></x-livewire-input>
            @error('form.title') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.opening_type')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.opening_type"
                :model="$this->recruitmentOpeningTypeOptions()"
            />
            @error('form.opening_type') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-4">
        <div class="flex flex-col lg:col-span-2">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.structure')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.structure_id"
                :model="$this->structureOptions"
                search-model="searchStructure"
            />
            @error('form.structure_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div class="flex flex-col lg:col-span-2">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.position')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.position_id"
                :model="$this->positionOptions"
                search-model="searchPosition"
            />
            @error('form.position_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-4">
        <div class="flex flex-col">
            @if ($this->recruitmentPackSelectorVisible())
                <x-ui.select-dropdown
                    :label="__('candidates::recruitment.labels.profile_pack')"
                    placeholder="---"
                    mode="gray"
                    class="w-full"
                    wire:model.live="form.profile_pack"
                    :model="$this->recruitmentPackOptions()"
                />
                @error('form.profile_pack') <x-validation>{{ $message }}</x-validation> @enderror
            @else
                <x-label for="form.profile_pack">{{ __('candidates::recruitment.labels.profile_pack') }}</x-label>
                <div class="inline-flex h-11 items-center rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold text-slate-700">
                    {{ $this->recruitmentPackLabel($form['profile_pack'] ?? null) }}
                </div>
            @endif
        </div>

        <div class="flex flex-col">
            <x-livewire-input mode="gray" type="number" name="form.headcount" wire:model="form.headcount"></x-livewire-input>
            @error('form.headcount') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.status')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.status"
                :model="$this->recruitmentStatusOptions()"
            />
            @error('form.status') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div class="flex flex-col">
            <x-label for="form.published_at">{{ __('candidates::recruitment.labels.published_at') }}</x-label>
            <x-pikaday-input mode="gray" name="form.published_at" format="Y-MM-DD" wire:model.live="form.published_at">
                <x-slot name="script">
                    $el.onchange = function () { @this.set('form.published_at', $el.value); }
                </x-slot>
            </x-pikaday-input>
            @error('form.published_at') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="flex flex-col">
            <x-label for="form.closes_at">{{ __('candidates::recruitment.labels.closes_at') }}</x-label>
            <x-pikaday-input mode="gray" name="form.closes_at" format="Y-MM-DD" wire:model.live="form.closes_at">
                <x-slot name="script">
                    $el.onchange = function () { @this.set('form.closes_at', $el.value); }
                </x-slot>
            </x-pikaday-input>
            @error('form.closes_at') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div class="flex flex-col">
            <x-label for="form.note">{{ __('candidates::recruitment.labels.note') }}</x-label>
            <x-textarea mode="gray" name="form.note" wire:model="form.note"></x-textarea>
            @error('form.note') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <x-button mode="black" wire:click="store">{{ __('candidates::recruitment.actions.save') }}</x-button>
    </div>
</div>
