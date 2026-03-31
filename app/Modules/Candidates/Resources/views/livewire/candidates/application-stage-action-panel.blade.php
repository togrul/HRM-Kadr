<section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-[0_28px_60px_-45px_rgba(15,23,42,0.35)]">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
                {{ __('candidates::recruitment.titles.stage_actions') }}
            </div>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                {{ __('candidates::recruitment.titles.stage_actions') }}
            </h2>
        </div>
    </div>

    @include('candidates::livewire.candidates.partials.application-stage-action-buttons')

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="flex flex-col">
            <x-ui.select-dropdown
                :label="__('candidates::recruitment.labels.target_stage')"
                placeholder="---"
                mode="gray"
                class="w-full"
                wire:model.live="form.to_stage"
                :model="$this->stageOptions"
            />
            @error('form.to_stage') <x-validation>{{ $message }}</x-validation> @enderror
        </div>

        <div class="flex flex-col">
            <x-label for="form.occurred_at">{{ __('candidates::recruitment.labels.occurred_at') }}</x-label>
            <x-pikaday-input mode="gray" name="form.occurred_at" format="Y-MM-DD" wire:model.live="form.occurred_at">
                <x-slot name="script">
                    $el.onchange = function () { @this.set('form.occurred_at', $el.value); }
                </x-slot>
            </x-pikaday-input>
            @error('form.occurred_at') <x-validation>{{ $message }}</x-validation> @enderror
        </div>
    </div>

    @include('candidates::livewire.candidates.partials.application-stage-assessment-block')
    @include('candidates::livewire.candidates.partials.application-stage-document-block')
    @include('candidates::livewire.candidates.partials.application-stage-profile-fields')
    @include('candidates::livewire.candidates.partials.application-stage-decision-block')

    <div class="mt-4 flex flex-col">
        <x-label for="form.note">{{ __('candidates::recruitment.labels.note') }}</x-label>
        <x-textarea mode="gray" name="form.note" wire:model="form.note"></x-textarea>
        @error('form.note') <x-validation>{{ $message }}</x-validation> @enderror
    </div>

    <div class="mt-6 flex items-center justify-between gap-4">
        @if (! $this->canSaveCurrentStageAction)
            <div class="text-sm font-medium text-rose-500">
                {{ __('candidates::recruitment.labels.permission_note') }}
            </div>
        @else
            <div></div>
        @endif

        <button
            type="button"
            wire:click="applyStageTransition"
            @disabled(! $this->canSaveCurrentStageAction)
            class="{{ $this->canSaveCurrentStageAction ? 'bg-slate-900 text-white hover:bg-slate-800' : 'cursor-not-allowed bg-slate-100 text-slate-400' }} inline-flex h-11 items-center rounded-2xl px-5 text-sm font-semibold transition"
        >
            {{ __('candidates::recruitment.actions.save_stage_action') }}
        </button>
    </div>
</section>
