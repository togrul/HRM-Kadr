    @if ($activeTab === 'catalogs')
        <div class="grid gap-4 xl:grid-cols-2">
            <x-surface-card :title="__('training_needs::dashboard.cards.competency_groups')" icon="icons.folder-plus-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-label for="group-name">{{ __('training_needs::dashboard.fields.group_name') }}</x-label>
                        <x-livewire-input mode="gray" id="group-name" wire:model.defer="groupForm.name" />
                        @error('groupForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="group-description">{{ __('training_needs::dashboard.fields.description') }}</x-label>
                        <textarea id="group-description" wire:model.defer="groupForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('groupForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="group-sort-order">{{ __('training_needs::dashboard.fields.sort_order') }}</x-label>
                        <x-livewire-input mode="gray" id="group-sort-order" type="number" wire:model.defer="groupForm.sort_order" />
                        @error('groupForm.sort_order') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="mt-6 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="groupForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('training_needs::dashboard.fields.is_active') }}
                    </label>
                    <div class="md:col-span-2">
                        <x-button mode="black" wire:click="storeGroup">{{ __('training_needs::dashboard.actions.save_group') }}</x-button>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.competency_levels')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <x-label for="level-name">{{ __('training_needs::dashboard.fields.level_name') }}</x-label>
                        <x-livewire-input mode="gray" id="level-name" wire:model.defer="levelForm.name" />
                        @error('levelForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="level-score">{{ __('training_needs::dashboard.fields.score') }}</x-label>
                        <x-livewire-input mode="gray" id="level-score" type="number" wire:model.defer="levelForm.score" />
                        @error('levelForm.score') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="level-sort-order">{{ __('training_needs::dashboard.fields.sort_order') }}</x-label>
                        <x-livewire-input mode="gray" id="level-sort-order" type="number" wire:model.defer="levelForm.sort_order" />
                        @error('levelForm.sort_order') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="mt-6 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="levelForm.is_default" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('training_needs::dashboard.fields.is_default') }}
                    </label>
                    <div class="md:col-span-2">
                        <x-label for="level-description">{{ __('training_needs::dashboard.fields.description') }}</x-label>
                        <textarea id="level-description" wire:model.defer="levelForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('levelForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-button mode="black" wire:click="storeLevel">{{ __('training_needs::dashboard.actions.save_level') }}</x-button>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.competencies')" icon="icons.profile-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.group')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            wire:model.live="competencyForm.training_competency_group_id"
                            :model="$this->competencyGroupOptions()"
                            search-model="searchCompetencyGroup"
                        ></x-ui.select-dropdown>
                        @error('competencyForm.training_competency_group_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="competency-name">{{ __('training_needs::dashboard.fields.competency_name') }}</x-label>
                        <x-livewire-input mode="gray" id="competency-name" wire:model.defer="competencyForm.name" />
                        @error('competencyForm.name') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="competency-description">{{ __('training_needs::dashboard.fields.description') }}</x-label>
                        <textarea id="competency-description" wire:model.defer="competencyForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('competencyForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="competencyForm.is_mandatory" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('training_needs::dashboard.fields.is_mandatory') }}
                    </label>
                    <label class="inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="competencyForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('training_needs::dashboard.fields.is_active') }}
                    </label>
                    <div class="md:col-span-2">
                        <x-button mode="black" wire:click="storeCompetency">{{ __('training_needs::dashboard.actions.save_competency') }}</x-button>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.programs')" icon="icons.clock-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-label for="program-title">{{ __('training_needs::dashboard.fields.program_title') }}</x-label>
                        <x-livewire-input mode="gray" id="program-title" wire:model.defer="programForm.title" />
                        @error('programForm.title') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="program-code">{{ __('training_needs::dashboard.fields.program_code') }}</x-label>
                        <x-livewire-input mode="gray" id="program-code" wire:model.defer="programForm.code" />
                        @error('programForm.code') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="program-hours">{{ __('training_needs::dashboard.fields.duration_hours') }}</x-label>
                        <x-livewire-input mode="gray" id="program-hours" type="number" step="0.25" wire:model.defer="programForm.duration_hours" />
                        @error('programForm.duration_hours') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="delivery-type">{{ __('training_needs::dashboard.fields.delivery_type') }}</x-label>
                        <select id="delivery-type" wire:model.defer="programForm.delivery_type" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="internal">{{ __('training_needs::dashboard.delivery_types.internal') }}</option>
                            <option value="external">{{ __('training_needs::dashboard.delivery_types.external') }}</option>
                            <option value="hybrid">{{ __('training_needs::dashboard.delivery_types.hybrid') }}</option>
                        </select>
                        @error('programForm.delivery_type') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="mt-6 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="programForm.is_active" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('training_needs::dashboard.fields.is_active') }}
                    </label>
                    <div class="md:col-span-2">
                        <x-label for="program-description">{{ __('training_needs::dashboard.fields.description') }}</x-label>
                        <textarea id="program-description" wire:model.defer="programForm.description" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('programForm.description') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-button mode="black" wire:click="storeProgram">{{ __('training_needs::dashboard.actions.save_program') }}</x-button>
                    </div>
                </div>
            </x-surface-card>
        </div>

        <x-surface-card :title="__('training_needs::dashboard.cards.program_competency_map')" icon="icons.pending-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-4">
                <div>
                    <x-ui.select-dropdown
                        :label="__('training_needs::dashboard.fields.program')"
                        placeholder="---"
                        mode="gray"
                        direction="up"
                        class="w-full"
                        wire:model.live="programMapForm.training_program_id"
                        :model="$this->trainingProgramOptions()"
                        search-model="searchTrainingProgram"
                    ></x-ui.select-dropdown>
                    @error('programMapForm.training_program_id') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div>
                    <x-ui.select-dropdown
                        :label="__('training_needs::dashboard.fields.competency')"
                        placeholder="---"
                        mode="gray"
                        direction="up"
                        class="w-full"
                        wire:model.live="programMapForm.training_competency_id"
                        :model="$this->competencyOptions()"
                        search-model="searchCompetency"
                    ></x-ui.select-dropdown>
                    @error('programMapForm.training_competency_id') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div>
                    <x-ui.select-dropdown
                        :label="__('training_needs::dashboard.fields.target_level')"
                        placeholder="---"
                        mode="gray"
                        direction="up"
                        class="w-full"
                        wire:model.live="programMapForm.target_level_id"
                        :model="$this->competencyLevelOptions()"
                        search-model="searchCompetencyLevel"
                    ></x-ui.select-dropdown>
                    @error('programMapForm.target_level_id') <x-validation>{{ $message }}</x-validation> @enderror
                </div>
                <div class="flex items-end">
                    <x-button mode="black" wire:click="storeProgramMap">{{ __('training_needs::dashboard.actions.save_program_map') }}</x-button>
                </div>
            </div>
        </x-surface-card>
    @endif

