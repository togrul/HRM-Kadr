<div class="mt-4 rounded-[24px] border border-slate-200 bg-slate-50 p-4">
    <div class="text-[11px] font-semibold uppercase tracking-tight text-slate-400">
        {{ __('candidates::recruitment.titles.assessment_focus') }}
    </div>

    @if ($this->assessmentChecklist)
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($this->assessmentChecklist as $item)
                <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ __('candidates::recruitment.assessment_checklists.'.$item) }}
                </span>
            @endforeach
        </div>
    @endif

    @if ($this->needsAssessmentFields())
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="flex flex-col">
                <x-label for="form.score">{{ __('candidates::recruitment.labels.score') }}</x-label>
                <x-livewire-input mode="gray" type="number" name="form.score" wire:model="form.score"></x-livewire-input>
                @error('form.score') <x-validation>{{ $message }}</x-validation> @enderror
            </div>
            <div class="flex flex-col">
                <x-label for="form.decision">{{ __('candidates::recruitment.labels.decision') }}</x-label>
                <x-livewire-input mode="gray" name="form.decision" wire:model="form.decision"></x-livewire-input>
                @error('form.decision') <x-validation>{{ $message }}</x-validation> @enderror
            </div>
        </div>

        @if ($this->assessmentChecklist)
            <div class="mt-4 grid gap-3">
                @foreach ($this->assessmentChecklist as $item)
                    <div class="rounded-[20px] border border-slate-200 bg-white p-4">
                        <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                            <div class="space-y-3">
                                <div class="text-sm font-semibold text-slate-900">
                                    {{ __('candidates::recruitment.assessment_checklists.'.$item) }}
                                </div>
                                <div class="flex flex-col">
                                    <x-label for="form.assessment_items.{{ $item }}.note">{{ __('candidates::recruitment.labels.assessment_note') }}</x-label>
                                    <x-textarea mode="gray" name="form.assessment_items.{{ $item }}.note" wire:model="form.assessment_items.{{ $item }}.note"></x-textarea>
                                    @error('form.assessment_items.'.$item.'.note') <x-validation>{{ $message }}</x-validation> @enderror
                                </div>
                            </div>
                            <div class="flex flex-col">
                                <x-ui.select-dropdown
                                    :label="__('candidates::recruitment.labels.assessment_status')"
                                    placeholder="---"
                                    mode="gray"
                                    class="w-full"
                                    wire:model.live="form.assessment_items.{{ $item }}.status"
                                    :model="$this->assessmentStatusOptions()"
                                />
                                @error('form.assessment_items.'.$item.'.status') <x-validation>{{ $message }}</x-validation> @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
