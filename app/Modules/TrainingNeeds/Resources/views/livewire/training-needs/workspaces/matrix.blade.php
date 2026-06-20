    @if ($activeTab === 'matrix')
        <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
            <x-surface-card :title="__('training_needs::dashboard.cards.requirement_matrix')" icon="icons.profile-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.position')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="requirementForm.position_id"
                            :model="$this->positionOptions()"
                            search-model="searchRequirementPosition"
                        ></x-ui.select-dropdown>
                        @error('requirementForm.position_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.competency')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="requirementForm.training_competency_id"
                            :model="$this->competencyOptions()"
                            search-model="searchCompetency"
                        ></x-ui.select-dropdown>
                        @error('requirementForm.training_competency_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.required_level')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="requirementForm.required_level_id"
                            :model="$this->competencyLevelOptions()"
                            search-model="searchCompetencyLevel"
                        ></x-ui.select-dropdown>
                        @error('requirementForm.required_level_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="requirement-priority">{{ __('training_needs::dashboard.fields.priority') }}</x-label>
                        <select id="requirement-priority" wire:model.defer="requirementForm.priority" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="low">{{ __('training_needs::dashboard.priorities.low') }}</option>
                            <option value="medium">{{ __('training_needs::dashboard.priorities.medium') }}</option>
                            <option value="high">{{ __('training_needs::dashboard.priorities.high') }}</option>
                        </select>
                        @error('requirementForm.priority') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="md:col-span-2 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="requirementForm.is_mandatory" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('training_needs::dashboard.fields.is_mandatory') }}
                    </label>
                    <div class="md:col-span-2">
                        <x-button mode="black" wire:click="storeRequirement">{{ __('training_needs::dashboard.actions.save_requirement') }}</x-button>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.recent_requirements')" icon="icons.folder-plus-icon">
                <div class="space-y-3">
                    @forelse ($this->recentRequirements as $requirement)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $requirement->position?->name ?? __('training_needs::dashboard.labels.no_position') }}</p>
                                    <p class="text-sm text-zinc-600">{{ $requirement->competency?->name ?? __('training_needs::dashboard.labels.no_competency') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-small-badge mode="blue">{{ $requirement->requiredLevel?->name ?? '---' }}</x-small-badge>
                                    <x-small-badge mode="{{ $requirement->priority === 'high' ? 'red' : ($requirement->priority === 'medium' ? 'green' : 'secondary') }}">
                                        {{ __('training_needs::dashboard.priorities.'.$requirement->priority) }}
                                    </x-small-badge>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('training_needs::dashboard.empty.recent_requirements') }}</p>
                    @endforelse
                </div>
            </x-surface-card>
        </div>
    @endif

