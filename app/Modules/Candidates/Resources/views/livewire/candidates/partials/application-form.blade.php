<div class="space-y-6">
    <div class="space-y-2">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">
            {{ $title }}
        </h2>
        <p class="text-sm leading-6 text-slate-500">
            {{ __('candidates::recruitment.labels.application_note') }}
        </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.opening')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.job_opening_id"
                :model="$this->openingOptions"
                search-model="searchOpening"
            />
            @error('form.job_opening_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.candidate')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.candidate_id"
                :model="$this->candidateOptions"
                search-model="searchCandidate"
            />
            @error('form.candidate_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
    </div>

    @if ($this->selectedOpening)
        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">{{ __('candidates::recruitment.labels.opening') }}</div>
            <div class="mt-2 text-lg font-semibold tracking-tight text-slate-900">{{ $this->selectedOpening->title }}</div>
            <div class="mt-2 text-sm text-slate-500">{{ $this->selectedOpening->structure?->name ?? '—' }} / {{ $this->selectedOpening->position?->name ?? '—' }}</div>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.source')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.candidate_source_id"
                :model="$this->sourceOptions"
                search-model="searchSource"
            />
            @error('form.candidate_source_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.assigned_recruiter')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.assigned_recruiter_id"
                :model="$this->recruiterOptions"
                search-model="searchRecruiter"
            />
            @error('form.assigned_recruiter_id') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
        <div class="flex flex-col">
            <x-label for="form.applied_at">{{ __('candidates::recruitment.labels.applied_at') }}</x-label>
            <x-pikaday-input mode="gray" name="form.applied_at" format="Y-MM-DD" wire:model.live="form.applied_at">
                <x-slot name="script">
                    $el.onchange = function () { @this.set('form.applied_at', $el.value); }
                </x-slot>
            </x-pikaday-input>
            @error('form.applied_at') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
    </div>

    <div class="flex flex-col">
        <x-label for="form.note">{{ __('candidates::recruitment.labels.note') }}</x-label>
        <x-textarea mode="gray" name="form.note" wire:model="form.note"></x-textarea>
        @error('form.note') <x-validation>{{ $message }}</x-validation> @enderror
    </div>

    <div class="flex items-center justify-end gap-3">
        <x-button mode="black" wire:click="store">{{ __('candidates::recruitment.actions.save') }}</x-button>
    </div>
</div>
