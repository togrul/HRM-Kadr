<div class="flex flex-col space-y-4 px-6 py-4">
    <x-surface-card :title="__('training_needs::dashboard.title')" icon="icons.folder-plus-icon">
        <div class="space-y-4">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.workspace.title') }}</p>
                    <p class="max-w-3xl text-sm text-zinc-500">{{ __('training_needs::dashboard.workspace.description') }}</p>
                    <div class="pt-2">
                        <a
                            href="{{ route('docs.guide', ['focus' => 'training']) }}#training-module"
                            class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-800 transition hover:border-sky-300 hover:bg-sky-100"
                        >
                            {{ __('training_needs::dashboard.actions.open_user_guide') }}
                        </a>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-emerald-700">{{ __('training_needs::dashboard.stats.groups') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $this->stats['groups'] }}</p>
                    </div>
                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-sky-700">{{ __('training_needs::dashboard.stats.competencies') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-sky-900">{{ $this->stats['competencies'] }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-amber-700">{{ __('training_needs::dashboard.stats.programs') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $this->stats['programs'] }}</p>
                    </div>
                    <div class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-violet-700">{{ __('training_needs::dashboard.stats.requirements') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-violet-900">{{ $this->stats['requirements'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.sections.title') }}</p>
                    <span class="text-xs text-zinc-500">{{ __('training_needs::dashboard.sections.description') }}</span>
                </div>

                <x-filter.nav class="min-w-0">
                    <x-filter.item wire:click.prevent="switchTab('overview')" :active="$activeTab === 'overview'">
                        {{ __('training_needs::dashboard.tabs.overview') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('catalogs')" :active="$activeTab === 'catalogs'">
                        {{ __('training_needs::dashboard.tabs.catalogs') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('matrix')" :active="$activeTab === 'matrix'">
                        {{ __('training_needs::dashboard.tabs.matrix') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('profiles')" :active="$activeTab === 'profiles'">
                        {{ __('training_needs::dashboard.tabs.profiles') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('planning')" :active="$activeTab === 'planning'">
                        {{ __('training_needs::dashboard.tabs.planning') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('calendar')" :active="$activeTab === 'calendar'">
                        {{ __('training_needs::dashboard.tabs.calendar') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('results')" :active="$activeTab === 'results'">
                        {{ __('training_needs::dashboard.tabs.results') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('analytics')" :active="$activeTab === 'analytics'">
                        {{ __('training_needs::dashboard.tabs.analytics') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('reports')" :active="$activeTab === 'reports'">
                        {{ __('training_needs::dashboard.tabs.reports') }}
                    </x-filter.item>
                    <x-filter.item wire:click.prevent="switchTab('lists')" :active="$activeTab === 'lists'">
                        {{ __('training_needs::dashboard.tabs.lists') }}
                    </x-filter.item>
                </x-filter.nav>
            </div>
        </div>
    </x-surface-card>

    @if ($activeTab === 'overview')
        <livewire:training-needs.overview lazy />
    @endif

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

    @if ($activeTab === 'profiles')
        <div class="grid gap-4 xl:grid-cols-2">
            <x-surface-card :title="__('training_needs::dashboard.cards.employee_profiles')" icon="icons.profile-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.personnel')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="profileForm.personnel_id"
                            :model="$this->personnelOptions()"
                            search-model="searchPersonnel"
                        ></x-ui.select-dropdown>
                        @error('profileForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.competency')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            wire:model.live="profileForm.training_competency_id"
                            :model="$this->competencyOptions()"
                            search-model="searchCompetency"
                        ></x-ui.select-dropdown>
                        @error('profileForm.training_competency_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.current_level')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            wire:model.live="profileForm.current_level_id"
                            :model="$this->competencyLevelOptions()"
                            search-model="searchCompetencyLevel"
                        ></x-ui.select-dropdown>
                        @error('profileForm.current_level_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="profile-source">{{ __('training_needs::dashboard.fields.source') }}</x-label>
                        <select id="profile-source" wire:model.defer="profileForm.source" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="manual">{{ __('training_needs::dashboard.sources.manual') }}</option>
                            <option value="manager_review">{{ __('training_needs::dashboard.sources.manager_review') }}</option>
                            <option value="hr_review">{{ __('training_needs::dashboard.sources.hr_review') }}</option>
                            <option value="exam">{{ __('training_needs::dashboard.sources.exam') }}</option>
                        </select>
                        @error('profileForm.source') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="profile-assessed-at">{{ __('training_needs::dashboard.fields.last_assessed_at') }}</x-label>
                        <input id="profile-assessed-at" type="date" wire:model.defer="profileForm.last_assessed_at" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                        @error('profileForm.last_assessed_at') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-button mode="black" wire:click="storeProfile">{{ __('training_needs::dashboard.actions.save_profile') }}</x-button>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.development_plan_shell')" icon="icons.folder-plus-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.personnel')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            wire:model.live="needForm.personnel_id"
                            :model="$this->personnelOptions()"
                            search-model="searchPersonnel"
                        ></x-ui.select-dropdown>
                        @error('needForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.competency')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            wire:model.live="needForm.training_competency_id"
                            :model="$this->competencyOptions()"
                            search-model="searchCompetency"
                        ></x-ui.select-dropdown>
                        @error('needForm.training_competency_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.recommended_program')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            wire:model.live="needForm.recommended_program_id"
                            :model="$this->trainingProgramOptions()"
                            search-model="searchTrainingProgram"
                        ></x-ui.select-dropdown>
                        @error('needForm.recommended_program_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.target_level')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            wire:model.live="needForm.target_level_id"
                            :model="$this->competencyLevelOptions()"
                            search-model="searchCompetencyLevel"
                        ></x-ui.select-dropdown>
                        @error('needForm.target_level_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="need-priority">{{ __('training_needs::dashboard.fields.priority') }}</x-label>
                        <select id="need-priority" wire:model.defer="needForm.priority" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="low">{{ __('training_needs::dashboard.priorities.low') }}</option>
                            <option value="medium">{{ __('training_needs::dashboard.priorities.medium') }}</option>
                            <option value="high">{{ __('training_needs::dashboard.priorities.high') }}</option>
                        </select>
                        @error('needForm.priority') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="need-status">{{ __('training_needs::dashboard.fields.status') }}</x-label>
                        <select id="need-status" wire:model.defer="needForm.status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="draft">{{ __('training_needs::dashboard.statuses.draft') }}</option>
                            <option value="review">{{ __('training_needs::dashboard.statuses.review') }}</option>
                            <option value="approved">{{ __('training_needs::dashboard.statuses.approved') }}</option>
                            <option value="planned">{{ __('training_needs::dashboard.statuses.planned') }}</option>
                        </select>
                        @error('needForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="need-source">{{ __('training_needs::dashboard.fields.source') }}</x-label>
                        <select id="need-source" wire:model.defer="needForm.source" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="manual">{{ __('training_needs::dashboard.sources.manual') }}</option>
                            <option value="manager_request">{{ __('training_needs::dashboard.sources.manager_request') }}</option>
                            <option value="employee_request">{{ __('training_needs::dashboard.sources.employee_request') }}</option>
                            <option value="performance_gap">{{ __('training_needs::dashboard.sources.performance_gap') }}</option>
                        </select>
                        @error('needForm.source') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="need-target-date">{{ __('training_needs::dashboard.fields.target_completion_date') }}</x-label>
                        <input id="need-target-date" type="date" wire:model.defer="needForm.target_completion_date" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                        @error('needForm.target_completion_date') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="need-reason">{{ __('training_needs::dashboard.fields.reason') }}</x-label>
                        <textarea id="need-reason" wire:model.defer="needForm.reason" class="min-h-20 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('needForm.reason') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="need-plan-note">{{ __('training_needs::dashboard.fields.plan_note') }}</x-label>
                        <textarea id="need-plan-note" wire:model.defer="needForm.plan_note" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('needForm.plan_note') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-button mode="black" wire:click="storeNeed">{{ __('training_needs::dashboard.actions.save_need') }}</x-button>
                    </div>
                </div>
            </x-surface-card>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <x-surface-card :title="__('training_needs::dashboard.cards.recent_profiles')" icon="icons.profile-outline-icon">
                <div class="space-y-3">
                    @forelse ($this->recentProfiles as $profile)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $profile->personnel?->fullname ?? '---' }}</p>
                                    <p class="text-sm text-zinc-600">{{ $profile->competency?->name ?? '---' }}</p>
                                </div>
                                <x-small-badge mode="blue">{{ $profile->currentLevel?->name ?? __('training_needs::dashboard.labels.unrated') }}</x-small-badge>
                            </div>
                            <p class="mt-1 text-xs text-zinc-500">
                                {{ __('training_needs::dashboard.labels.profile_meta', [
                                    'source' => __('training_needs::dashboard.sources.'.($profile->source ?: 'manual')),
                                    'date' => optional($profile->last_assessed_at)->format('d.m.Y') ?: '---',
                                ]) }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('training_needs::dashboard.empty.recent_profiles') }}</p>
                    @endforelse
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.need_queue')" icon="icons.pending-icon">
                <div class="space-y-3">
                    @forelse ($this->recentNeeds as $need)
                        <x-ui.list-card :tone="$need->priority === 'high' ? 'rose' : ($need->priority === 'medium' ? 'sky' : 'neutral')">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $need->personnel?->fullname ?? '---' }}</p>
                                    <p class="text-sm text-zinc-600">{{ $need->competency?->name ?? '---' }}</p>
                                    @if ($need->recommendedProgram?->title)
                                        <p class="mt-1 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.recommended_program_meta', ['program' => $need->recommendedProgram->title]) }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-small-badge mode="{{ $need->priority === 'high' ? 'red' : ($need->priority === 'medium' ? 'green' : 'secondary') }}">
                                        {{ __('training_needs::dashboard.priorities.'.$need->priority) }}
                                    </x-small-badge>
                                    @if ($need->source === 'performance_gap')
                                        <x-small-badge :mode="$need->priority === 'high' ? 'red' : 'sky'">
                                            {{ __('training_needs::dashboard.labels.'.($need->priority === 'high' ? 'weak_result' : 'medium_result')) }}
                                        </x-small-badge>
                                    @endif
                                    <x-small-badge mode="sky">{{ __('training_needs::dashboard.statuses.'.$need->status) }}</x-small-badge>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">
                                {{ __('training_needs::dashboard.labels.development_plan_meta', [
                                    'source' => __('training_needs::dashboard.sources.'.($need->source ?: 'manual')),
                                    'level' => $need->targetLevel?->name ?? __('training_needs::dashboard.labels.no_target_level'),
                                    'date' => optional($need->target_completion_date)->format('d.m.Y') ?: __('training_needs::dashboard.labels.no_date'),
                                ]) }}
                            </p>
                            @if ($need->reason)
                                <p class="mt-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-600">{{ $need->presentedReason() }}</p>
                            @endif
                            @if ($need->plan_note)
                                <p class="mt-2 rounded-lg bg-white px-3 py-2 text-sm text-zinc-600">{{ $need->presentedPlanNote() }}</p>
                            @endif
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.pending-icon" :message="__('training_needs::dashboard.empty.recent_needs')" />
                    @endforelse
                </div>
            </x-surface-card>
        </div>
    @endif

    @if ($activeTab === 'planning')
        <div class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
            <x-surface-card :title="__('training_needs::dashboard.cards.annual_planning_board')" icon="icons.training-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                @if ($editingPlanId)
                    <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        {{ __('training_needs::dashboard.labels.editing_plan_hint') }}
                    </div>
                @endif
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-label for="plan-title">{{ __('training_needs::dashboard.fields.plan_title') }}</x-label>
                        <x-livewire-input mode="gray" id="plan-title" wire:model.defer="planForm.title" />
                        @error('planForm.title') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="plan-year">{{ __('training_needs::dashboard.fields.plan_year') }}</x-label>
                        <x-livewire-input mode="gray" id="plan-year" type="number" wire:model.defer="planForm.plan_year" />
                        @error('planForm.plan_year') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="plan-quarter">{{ __('training_needs::dashboard.fields.plan_quarter') }}</x-label>
                        <select id="plan-quarter" wire:model.defer="planForm.plan_quarter" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="">{{ __('training_needs::dashboard.labels.all_year') }}</option>
                            <option value="1">Q1</option>
                            <option value="2">Q2</option>
                            <option value="3">Q3</option>
                            <option value="4">Q4</option>
                        </select>
                        @error('planForm.plan_quarter') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="plan-status">{{ __('training_needs::dashboard.fields.status') }}</x-label>
                        <select id="plan-status" wire:model.defer="planForm.status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="draft">{{ __('training_needs::dashboard.plan_statuses.draft') }}</option>
                            <option value="review">{{ __('training_needs::dashboard.plan_statuses.review') }}</option>
                            <option value="approved">{{ __('training_needs::dashboard.plan_statuses.approved') }}</option>
                            <option value="published">{{ __('training_needs::dashboard.plan_statuses.published') }}</option>
                        </select>
                        @error('planForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="mt-6 inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="planForm.auto_generate" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('training_needs::dashboard.fields.auto_generate_plan_items') }}
                    </label>
                    <div class="md:col-span-2">
                        <x-label for="plan-notes">{{ __('training_needs::dashboard.fields.notes') }}</x-label>
                        <textarea id="plan-notes" wire:model.defer="planForm.notes" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('planForm.notes') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex flex-wrap gap-2">
                            <x-button mode="black" wire:click="storePlan">
                                {{ $editingPlanId ? __('training_needs::dashboard.actions.update_plan') : __('training_needs::dashboard.actions.save_plan') }}
                            </x-button>
                            @if ($editingPlanId)
                                <x-button mode="secondary" wire:click="cancelPlanEdit">{{ __('training_needs::dashboard.actions.cancel_edit') }}</x-button>
                            @endif
                        </div>
                        <p class="mt-3 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.plan_status_hint') }}</p>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.recent_plans')" icon="icons.pending-icon">
                <div class="space-y-3">
                    @forelse ($this->recentPlans as $plan)
                        <x-ui.list-card>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $plan->title }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.plan_scope', ['year' => $plan->plan_year, 'quarter' => $plan->plan_quarter ? 'Q'.$plan->plan_quarter : __('training_needs::dashboard.labels.all_year')]) }}</p>
                                </div>
                                <x-small-badge mode="sky">{{ __('training_needs::dashboard.plan_statuses.'.$plan->status) }}</x-small-badge>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.plan_summary', ['count' => $plan->items_count, 'participants' => $plan->planned_participants, 'budget' => number_format((float) $plan->estimated_budget, 2)]) }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <x-ui.action-pill mode="secondary" wire:click="editPlan({{ $plan->id }})" icon="icons.edit-icon">{{ __('training_needs::dashboard.actions.edit') }}</x-ui.action-pill>
                                <x-ui.action-pill mode="delete" wire:click="confirmDeletePlan({{ $plan->id }})" icon="icons.delete-icon">{{ __('training_needs::dashboard.actions.delete') }}</x-ui.action-pill>
                            </div>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.training-icon" :message="__('training_needs::dashboard.empty.recent_plans')" />
                    @endforelse
                </div>
            </x-surface-card>
        </div>

        <x-surface-card :title="__('training_needs::dashboard.cards.suggested_plan_board')" icon="icons.performance-icon">
            <div class="grid gap-3 xl:grid-cols-2">
                @forelse ($this->suggestedPlanGroups as $suggestion)
                    <x-ui.list-card tone="violet">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-zinc-900">{{ $suggestion['training_program_title'] ?? __('training_needs::dashboard.labels.no_program') }}</p>
                                    <x-small-badge mode="sky">{{ __('training_needs::dashboard.labels.system_suggested') }}</x-small-badge>
                                </div>
                                <p class="mt-1 text-sm text-zinc-600">{{ $suggestion['training_competency_name'] ?? __('training_needs::dashboard.labels.no_competency') }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $suggestion['position_name'] ?? __('training_needs::dashboard.labels.no_position') }} • {{ __('training_needs::dashboard.priorities.'.$suggestion['priority']) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-small-badge mode="green">{{ __('training_needs::dashboard.labels.suggested_score', ['score' => number_format((float) $suggestion['suggested_score'], 1)]) }}</x-small-badge>
                                <x-small-badge mode="blue">{{ __('training_needs::dashboard.labels.participant_count', ['count' => $suggestion['participant_count']]) }}</x-small-badge>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($suggestion['suggested_reasons'] as $reason)
                                <x-small-badge :mode="in_array($reason, ['mandatory', 'overdue'], true) ? 'red' : (in_array($reason, ['high_gap', 'role_critical'], true) ? 'green' : 'secondary')">
                                    {{ __('training_needs::dashboard.suggestion_reasons.'.$reason) }}
                                </x-small-badge>
                            @endforeach
                        </div>
                        <p class="mt-3 text-xs text-zinc-500">
                            {{ __('training_needs::dashboard.labels.suggestion_meta', [
                                'needs' => $suggestion['need_count'],
                                'budget' => number_format((float) $suggestion['estimated_budget'], 2),
                                'level' => $suggestion['target_level_name'] ?? __('training_needs::dashboard.labels.no_target_level'),
                                'sources' => $suggestion['source_mix'] ?: __('training_needs::dashboard.sources.manual'),
                                'date' => $suggestion['latest_due_date'] ? \Illuminate\Support\Carbon::parse($suggestion['latest_due_date'])->format('d.m.Y') : __('training_needs::dashboard.labels.no_date'),
                            ]) }}
                        </p>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.performance-icon" :message="__('training_needs::dashboard.empty.suggested_plan_groups')" />
                @endforelse
            </div>
        </x-surface-card>

        @if ($this->selectedPlanItem)
            <x-surface-card :title="__('training_needs::dashboard.cards.plan_item_review')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-zinc-900">{{ $this->selectedPlanItem->program?->title ?? __('training_needs::dashboard.labels.no_program') }}</p>
                                <x-small-badge :mode="$this->selectedPlanItem->review_status === 'approved' ? 'green' : ($this->selectedPlanItem->review_status === 'hr_adjusted' ? 'sky' : 'secondary')">
                                    {{ __('training_needs::dashboard.review_statuses.'.$this->selectedPlanItem->review_status) }}
                                </x-small-badge>
                            </div>
                            <p class="mt-1 text-sm text-zinc-600">{{ $this->selectedPlanItem->competency?->name ?? __('training_needs::dashboard.labels.no_competency') }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $this->selectedPlanItem->position?->name ?? __('training_needs::dashboard.labels.no_position') }} • {{ $this->selectedPlanItem->plan?->title }}</p>
                        </div>
                    </div>
                    <div>
                        <x-label for="review-participant-count">{{ __('training_needs::dashboard.fields.participant_count') }}</x-label>
                        <x-livewire-input mode="gray" id="review-participant-count" type="number" wire:model.defer="planItemReviewForm.participant_count" />
                        @error('planItemReviewForm.participant_count') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="review-budget">{{ __('training_needs::dashboard.fields.planned_budget') }}</x-label>
                        <x-livewire-input mode="gray" id="review-budget" type="number" step="0.01" wire:model.defer="planItemReviewForm.estimated_budget" />
                        @error('planItemReviewForm.estimated_budget') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="review-priority">{{ __('training_needs::dashboard.fields.priority') }}</x-label>
                        <select id="review-priority" wire:model.defer="planItemReviewForm.priority" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="low">{{ __('training_needs::dashboard.priorities.low') }}</option>
                            <option value="medium">{{ __('training_needs::dashboard.priorities.medium') }}</option>
                            <option value="high">{{ __('training_needs::dashboard.priorities.high') }}</option>
                        </select>
                        @error('planItemReviewForm.priority') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="review-note">{{ __('training_needs::dashboard.fields.review_note') }}</x-label>
                        <textarea id="review-note" wire:model.defer="planItemReviewForm.review_note" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('planItemReviewForm.review_note') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2 flex flex-wrap gap-2">
                        <x-button mode="default" wire:click="cancelPlanItemReview">{{ __('training_needs::dashboard.actions.cancel_review') }}</x-button>
                        <x-button mode="light-blue" wire:click="savePlanItemReview('hr_adjusted')">{{ __('training_needs::dashboard.actions.mark_hr_adjusted') }}</x-button>
                        <x-button mode="black" wire:click="savePlanItemReview('approved')">{{ __('training_needs::dashboard.actions.approve_plan_item') }}</x-button>
                    </div>
                </div>
            </x-surface-card>
        @endif

        <x-surface-card :title="__('training_needs::dashboard.cards.plan_items_board')" icon="icons.profile-outline-icon">
            <div class="space-y-3">
                @forelse ($this->recentPlanItems as $item)
                    <x-ui.list-card :tone="$item->review_status === 'approved' ? 'emerald' : ($item->review_status === 'hr_adjusted' ? 'sky' : 'neutral')">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-zinc-900">{{ $item->program?->title ?? __('training_needs::dashboard.labels.no_program') }}</p>
                                <p class="text-sm text-zinc-600">{{ $item->competency?->name ?? __('training_needs::dashboard.labels.no_competency') }}</p>
                                <p class="mt-1 text-xs text-zinc-500">{{ $item->position?->name ?? __('training_needs::dashboard.labels.no_position') }} • {{ $item->plan?->title }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-small-badge :mode="$item->review_status === 'approved' ? 'green' : ($item->review_status === 'hr_adjusted' ? 'sky' : 'secondary')">{{ __('training_needs::dashboard.review_statuses.'.$item->review_status) }}</x-small-badge>
                                <x-small-badge mode="green">{{ __('training_needs::dashboard.labels.participant_count', ['count' => $item->participant_count]) }}</x-small-badge>
                                <x-small-badge mode="blue">{{ __('training_needs::dashboard.labels.need_count', ['count' => $item->need_count]) }}</x-small-badge>
                                <x-small-badge :mode="$item->priority === 'high' ? 'red' : ($item->priority === 'medium' ? 'green' : 'secondary')">{{ __('training_needs::dashboard.priorities.'.$item->priority) }}</x-small-badge>
                                <x-small-badge mode="sky">{{ __('training_needs::dashboard.labels.suggested_score', ['score' => number_format((float) $item->suggested_score, 1)]) }}</x-small-badge>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.plan_item_meta', ['level' => $item->targetLevel?->name ?? __('training_needs::dashboard.labels.no_target_level'), 'budget' => number_format((float) $item->estimated_budget, 2), 'sources' => $item->source_mix ?: __('training_needs::dashboard.sources.manual')]) }}</p>
                        @if ($item->review_note)
                            <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.review_note_meta', ['note' => $item->review_note]) }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-button mode="default" wire:click="selectPlanItemForReview({{ $item->id }})">{{ __('training_needs::dashboard.actions.review_plan_item') }}</x-button>
                            @if ($item->review_status !== 'approved')
                                <x-button mode="black" wire:click="selectPlanItemForReview({{ $item->id }})">{{ __('training_needs::dashboard.actions.open_review') }}</x-button>
                            @endif
                        </div>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.profile-outline-icon" :message="__('training_needs::dashboard.empty.plan_items')" />
                @endforelse
            </div>
        </x-surface-card>
    @endif

    @if ($activeTab === 'calendar')
        <x-surface-card :title="__('training_needs::dashboard.cards.session_proposal_board')" icon="icons.performance-icon">
            <div class="mb-4 rounded-2xl border border-zinc-200 bg-zinc-50/90 px-4 py-3 text-xs leading-6 text-zinc-500">
                {{ __('training_needs::dashboard.labels.session_proposal_applied_hint') }}
            </div>
            @if (count($this->sessionProposals))
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200 bg-zinc-50/90 px-4 py-3">
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-zinc-800">{{ __('training_needs::dashboard.actions.create_selected_sessions') }}</p>
                        <p class="text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.proposal_selection_meta', ['count' => count($bulkProposalPlanItemIds)]) }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.action-pill mode="secondary" wire:click="selectVisibleSessionProposals" icon="icons.profile-icon">{{ __('training_needs::dashboard.actions.select_visible_proposals') }}</x-ui.action-pill>
                        <x-ui.action-pill mode="secondary" wire:click="clearSelectedSessionProposals">{{ __('training_needs::dashboard.actions.clear_selected_proposals') }}</x-ui.action-pill>
                        <x-ui.action-pill wire:click="createSelectedSessionProposals" icon="icons.calendar-icon">{{ __('training_needs::dashboard.actions.create_selected_sessions') }}</x-ui.action-pill>
                    </div>
                </div>
            @endif
            <div class="grid gap-3 xl:grid-cols-2">
                @forelse ($this->sessionProposals as $proposal)
                    <x-ui.list-card tone="sky">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" wire:model.live="bulkProposalPlanItemIds" value="{{ $proposal['plan_item_id'] }}" class="mt-1 rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-semibold text-zinc-900">{{ $proposal['program_title'] ?? __('training_needs::dashboard.labels.no_program') }}</p>
                                                <x-small-badge mode="sky">{{ __('training_needs::dashboard.labels.session_proposal') }}</x-small-badge>
                                            </div>
                                            <p class="mt-1 text-sm text-zinc-600">{{ $proposal['competency_name'] ?? __('training_needs::dashboard.labels.no_competency') }}</p>
                                            <p class="mt-1 text-xs text-zinc-500">{{ $proposal['position_name'] ?? __('training_needs::dashboard.labels.no_position') }} • {{ $proposal['plan_title'] }}</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <x-small-badge mode="green">{{ __('training_needs::dashboard.labels.participant_count', ['count' => $proposal['participant_count']]) }}</x-small-badge>
                                            <x-small-badge mode="blue">{{ __('training_needs::dashboard.labels.suggested_score', ['score' => number_format((float) $proposal['score'], 1)]) }}</x-small-badge>
                                        </div>
                                    </div>
                                    <p class="mt-3 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.session_proposal_meta', [
                                        'start' => \Illuminate\Support\Carbon::parse($proposal['scheduled_start_at'])->format('d.m.Y H:i'),
                                        'end' => \Illuminate\Support\Carbon::parse($proposal['scheduled_end_at'])->format('d.m.Y H:i'),
                                        'budget' => number_format((float) $proposal['estimated_budget'], 2),
                                    ]) }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 border-t border-sky-200/80 pt-3">
                                <x-ui.action-pill mode="secondary" wire:click="applySessionProposal({{ $proposal['plan_item_id'] }})" icon="icons.edit-icon">{{ __('training_needs::dashboard.actions.apply_session_proposal') }}</x-ui.action-pill>
                                <x-ui.action-pill wire:click="createSessionFromProposal({{ $proposal['plan_item_id'] }})" icon="icons.calendar-icon">{{ __('training_needs::dashboard.actions.create_session_from_proposal') }}</x-ui.action-pill>
                            </div>
                        </div>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.calendar-icon" :message="__('training_needs::dashboard.empty.session_proposals')" />
                @endforelse
            </div>
        </x-surface-card>

        <div class="grid gap-4 xl:grid-cols-2">
            <x-surface-card :title="__('training_needs::dashboard.cards.training_calendar')" icon="icons.training-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                @if ($editingSessionId)
                    <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        {{ __('training_needs::dashboard.labels.editing_session_hint') }}
                    </div>
                @elseif ($selectedSessionProposalPlanItemId)
                    <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        {{ __('training_needs::dashboard.labels.session_proposal_applied_hint') }}
                    </div>
                @endif
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.plan')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            wire:model.live="sessionForm.training_annual_plan_id"
                            :model="$this->planOptions()"
                            search-model="searchSessionPlan"
                        ></x-ui.select-dropdown>
                        @error('sessionForm.training_annual_plan_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.program')"
                            placeholder="---"
                            mode="gray"
                            class="w-full"
                            wire:model.live="sessionForm.training_program_id"
                            :model="$this->trainingProgramOptions()"
                            search-model="searchTrainingProgram"
                        ></x-ui.select-dropdown>
                        @error('sessionForm.training_program_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="session-title">{{ __('training_needs::dashboard.fields.session_title') }}</x-label>
                        <x-livewire-input mode="gray" id="session-title" wire:model.defer="sessionForm.title" />
                        @error('sessionForm.title') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="session-start">{{ __('training_needs::dashboard.fields.scheduled_start_at') }}</x-label>
                        <input id="session-start" type="datetime-local" wire:model.defer="sessionForm.scheduled_start_at" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                        @error('sessionForm.scheduled_start_at') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="session-end">{{ __('training_needs::dashboard.fields.scheduled_end_at') }}</x-label>
                        <input id="session-end" type="datetime-local" wire:model.defer="sessionForm.scheduled_end_at" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500" />
                        @error('sessionForm.scheduled_end_at') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="session-location">{{ __('training_needs::dashboard.fields.location') }}</x-label>
                        <x-livewire-input mode="gray" id="session-location" wire:model.defer="sessionForm.location" />
                        @error('sessionForm.location') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="session-trainer">{{ __('training_needs::dashboard.fields.trainer_name') }}</x-label>
                        <x-livewire-input mode="gray" id="session-trainer" wire:model.defer="sessionForm.trainer_name" />
                        @error('sessionForm.trainer_name') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="session-capacity">{{ __('training_needs::dashboard.fields.capacity') }}</x-label>
                        <x-livewire-input mode="gray" id="session-capacity" type="number" wire:model.defer="sessionForm.capacity" />
                        @error('sessionForm.capacity') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="session-budget">{{ __('training_needs::dashboard.fields.planned_budget') }}</x-label>
                        <x-livewire-input mode="gray" id="session-budget" type="number" step="0.01" wire:model.defer="sessionForm.planned_budget" />
                        @error('sessionForm.planned_budget') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="session-actual-budget">{{ __('training_needs::dashboard.fields.actual_budget') }}</x-label>
                        <x-livewire-input mode="gray" id="session-actual-budget" type="number" step="0.01" wire:model.defer="sessionForm.actual_budget" />
                        @error('sessionForm.actual_budget') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <label class="inline-flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" wire:model.defer="sessionForm.auto_fill_participants" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                        {{ __('training_needs::dashboard.fields.auto_fill_participants') }}
                    </label>
                    <div>
                        <x-label for="session-status">{{ __('training_needs::dashboard.fields.status') }}</x-label>
                        <select id="session-status" wire:model.defer="sessionForm.status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="draft">{{ __('training_needs::dashboard.session_statuses.draft') }}</option>
                            <option value="scheduled">{{ __('training_needs::dashboard.session_statuses.scheduled') }}</option>
                            <option value="in_progress">{{ __('training_needs::dashboard.session_statuses.in_progress') }}</option>
                            <option value="completed">{{ __('training_needs::dashboard.session_statuses.completed') }}</option>
                            <option value="cancelled">{{ __('training_needs::dashboard.session_statuses.cancelled') }}</option>
                        </select>
                        @error('sessionForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="session-notes">{{ __('training_needs::dashboard.fields.notes') }}</x-label>
                        <textarea id="session-notes" wire:model.defer="sessionForm.notes" class="min-h-20 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('sessionForm.notes') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex flex-wrap gap-2">
                            <x-button mode="black" wire:click="storeSession">
                                {{ $editingSessionId ? __('training_needs::dashboard.actions.update_session') : __('training_needs::dashboard.actions.save_session') }}
                            </x-button>
                            @if ($editingSessionId)
                                <x-button mode="secondary" wire:click="cancelSessionEdit">{{ __('training_needs::dashboard.actions.cancel_edit') }}</x-button>
                            @endif
                        </div>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.session_participants')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.session')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="participantForm.training_session_id"
                            :model="$this->sessionOptions()"
                            search-model="searchSession"
                        ></x-ui.select-dropdown>
                        @error('participantForm.training_session_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.personnel')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="participantForm.personnel_id"
                            :model="$this->personnelOptions()"
                            search-model="searchPersonnel"
                        ></x-ui.select-dropdown>
                        @error('participantForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.training_need')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="participantForm.training_need_item_id"
                            :model="$this->trainingNeedOptions()"
                        ></x-ui.select-dropdown>
                        @error('participantForm.training_need_item_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="participant-status">{{ __('training_needs::dashboard.fields.attendance_status') }}</x-label>
                        <select id="participant-status" wire:model.defer="participantForm.attendance_status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="planned">{{ __('training_needs::dashboard.attendance_statuses.planned') }}</option>
                            <option value="confirmed">{{ __('training_needs::dashboard.attendance_statuses.confirmed') }}</option>
                            <option value="attended">{{ __('training_needs::dashboard.attendance_statuses.attended') }}</option>
                            <option value="absent">{{ __('training_needs::dashboard.attendance_statuses.absent') }}</option>
                            <option value="cancelled">{{ __('training_needs::dashboard.attendance_statuses.cancelled') }}</option>
                        </select>
                        @error('participantForm.attendance_status') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="flex items-end gap-2">
                        <x-button mode="black" wire:click="storeSessionParticipant">{{ __('training_needs::dashboard.actions.add_participant') }}</x-button>
                        <x-button mode="success" wire:click="completeSession">{{ __('training_needs::dashboard.actions.complete_session') }}</x-button>
                    </div>
                </div>
            </x-surface-card>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <x-surface-card :title="__('training_needs::dashboard.cards.upcoming_sessions')" icon="icons.clock-icon">
                <div class="space-y-3">
                    @forelse ($this->recentSessions as $session)
                        <x-ui.list-card :active="$selectedSessionId === $session->id" tone="{{ $selectedSessionId === $session->id ? 'sky' : 'neutral' }}">
                            <button type="button" wire:click="selectSessionDetail({{ $session->id }})" class="w-full text-left">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $session->title }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $session->program?->title ?? __('training_needs::dashboard.labels.no_program') }} • {{ optional($session->scheduled_start_at)->format('d.m.Y H:i') ?: '---' }}</p>
                                </div>
                                <x-small-badge mode="sky">{{ __('training_needs::dashboard.session_statuses.'.$session->status) }}</x-small-badge>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.session_meta', ['location' => $session->location ?: '—', 'trainer' => $session->trainer_name ?: '—', 'participants' => $session->participants->count()]) }}</p>
                            </button>
                            <div class="mt-3 flex flex-wrap gap-2 border-t border-zinc-200/80 pt-3">
                                <x-ui.action-pill mode="secondary" wire:click="editSession({{ $session->id }})" icon="icons.edit-icon">{{ __('training_needs::dashboard.actions.edit') }}</x-ui.action-pill>
                                <x-ui.action-pill mode="delete" wire:click="confirmDeleteSession({{ $session->id }})" icon="icons.delete-icon">{{ __('training_needs::dashboard.actions.delete') }}</x-ui.action-pill>
                            </div>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.calendar-icon" :message="__('training_needs::dashboard.empty.sessions')" />
                    @endforelse
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.delivery_snapshot')" icon="icons.profile-icon">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.scheduled_sessions') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->deliverySummary['scheduled_sessions'] }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.completed_sessions') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->deliverySummary['completed_sessions'] }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.attended_participants') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->deliverySummary['attended_participants'] }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.delivery_records') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->deliverySummary['delivery_records'] }}</p>
                    </div>
                </div>
            </x-surface-card>
        </div>

        @if ($selectedSessionId)
            <livewire:training-needs.session-detail-workspace
                :session-id="$selectedSessionId"
                :key="'training-session-detail-'.$selectedSessionId.'-'.$sessionDetailWorkspaceVersion"
            />
        @endif
    @endif

    @if ($activeTab === 'results')
        <div class="grid gap-4 xl:grid-cols-2">
            <x-surface-card :title="__('training_needs::dashboard.cards.feedback_forms')" icon="icons.folder-plus-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                @if ($editingFeedbackFormId)
                    <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        {{ __('training_needs::dashboard.labels.editing_feedback_form_hint') }}
                    </div>
                @endif
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.session')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="feedbackForm.training_session_id"
                            :model="$this->sessionOptions()"
                            search-model="searchSession"
                        ></x-ui.select-dropdown>
                        @error('feedbackForm.training_session_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-title">{{ __('training_needs::dashboard.fields.feedback_title') }}</x-label>
                        <x-livewire-input mode="gray" id="feedback-title" wire:model.defer="feedbackForm.title" />
                        @error('feedbackForm.title') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-status">{{ __('training_needs::dashboard.fields.status') }}</x-label>
                        <select id="feedback-status" wire:model.defer="feedbackForm.status" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="draft">{{ __('training_needs::dashboard.feedback_statuses.draft') }}</option>
                            <option value="open">{{ __('training_needs::dashboard.feedback_statuses.open') }}</option>
                            <option value="closed">{{ __('training_needs::dashboard.feedback_statuses.closed') }}</option>
                        </select>
                        @error('feedbackForm.status') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-question-type">{{ __('training_needs::dashboard.fields.default_question_type') }}</x-label>
                        <select id="feedback-question-type" wire:model.defer="feedbackForm.default_question_type" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                            <option value="rating">{{ __('training_needs::dashboard.question_types.rating') }}</option>
                            <option value="text">{{ __('training_needs::dashboard.question_types.text') }}</option>
                            <option value="multiple_choice">{{ __('training_needs::dashboard.question_types.multiple_choice') }}</option>
                        </select>
                        @error('feedbackForm.default_question_type') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-questions">{{ __('training_needs::dashboard.fields.feedback_questions') }}</x-label>
                        <textarea id="feedback-questions" wire:model.defer="feedbackForm.questions_text" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('feedbackForm.questions_text') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex flex-wrap gap-2">
                            <x-button mode="black" wire:click="storeFeedbackForm">
                                {{ $editingFeedbackFormId ? __('training_needs::dashboard.actions.update_feedback_form') : __('training_needs::dashboard.actions.save_feedback_form') }}
                            </x-button>
                            @if ($editingFeedbackFormId)
                                <x-button mode="secondary" wire:click="cancelFeedbackFormEdit">{{ __('training_needs::dashboard.actions.cancel_edit') }}</x-button>
                            @endif
                        </div>
                    </div>
                </div>
            </x-surface-card>

            <x-surface-card :title="__('training_needs::dashboard.cards.feedback_responses')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.feedback_form')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="feedbackResponseForm.training_feedback_form_id"
                            :model="$this->feedbackFormOptions()"
                            search-model="searchFeedbackForm"
                        ></x-ui.select-dropdown>
                        @error('feedbackResponseForm.training_feedback_form_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.personnel')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="feedbackResponseForm.personnel_id"
                            :model="$this->personnelOptions()"
                            search-model="searchPersonnel"
                        ></x-ui.select-dropdown>
                        @error('feedbackResponseForm.personnel_id') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div>
                        <x-label for="feedback-score">{{ __('training_needs::dashboard.fields.overall_score') }}</x-label>
                        <x-livewire-input mode="gray" id="feedback-score" type="number" min="1" max="5" wire:model.defer="feedbackResponseForm.overall_score" />
                        @error('feedbackResponseForm.overall_score') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-comments">{{ __('training_needs::dashboard.fields.comments') }}</x-label>
                        <textarea id="feedback-comments" wire:model.defer="feedbackResponseForm.comments" class="min-h-20 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('feedbackResponseForm.comments') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-label for="feedback-answers">{{ __('training_needs::dashboard.fields.feedback_answers') }}</x-label>
                        <textarea id="feedback-answers" wire:model.defer="feedbackResponseForm.answers_text" class="min-h-24 w-full rounded-lg border-none bg-neutral-100 px-3 py-2 text-sm shadow-sm focus:ring-blue-500"></textarea>
                        @error('feedbackResponseForm.answers_text') <x-validation>{{ $message }}</x-validation> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <x-button mode="black" wire:click="submitFeedbackResponse">{{ __('training_needs::dashboard.actions.save_feedback_response') }}</x-button>
                    </div>
                </div>
            </x-surface-card>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
            <x-surface-card :title="__('training_needs::dashboard.cards.delivered_trainings')" icon="icons.clock-icon">
                <div class="space-y-3">
                    @forelse ($this->recentDeliveryRecords as $record)
                        <x-ui.list-card>
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $record->personnel?->fullname ?? '---' }}</p>
                                    <p class="text-sm text-zinc-600">{{ $record->program?->title ?? __('training_needs::dashboard.labels.no_program') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-ui.action-pill wire:click="selectDeliveryRecord({{ $record->id }})" icon="icons.edit-icon">{{ __('training_needs::dashboard.actions.replace_certificate') }}</x-ui.action-pill>
                                    @if ($record->certificate_path)
                                        <x-ui.action-pill mode="secondary" wire:click="previewDeliveryCertificate({{ $record->id }})">{{ __('training_needs::dashboard.actions.preview_certificate') }}</x-ui.action-pill>
                                        <x-ui.action-pill mode="secondary" wire:click="downloadDeliveryCertificate({{ $record->id }})">{{ __('training_needs::dashboard.actions.download_certificate') }}</x-ui.action-pill>
                                        <x-ui.action-pill mode="delete" wire:click="confirmDeleteDeliveryCertificate({{ $record->id }})" icon="icons.delete-icon">{{ __('training_needs::dashboard.actions.delete_certificate') }}</x-ui.action-pill>
                                    @endif
                                    <x-small-badge mode="green">{{ __('training_needs::dashboard.delivery_result_statuses.'.$record->result_status) }}</x-small-badge>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500">{{ __('training_needs::dashboard.labels.delivery_meta', ['session' => $record->session?->title ?? '—', 'date' => optional($record->completed_at)->format('d.m.Y H:i') ?: '—', 'hours' => $record->attended_hours ?: 0]) }}</p>
                        </x-ui.list-card>
                    @empty
                        <x-ui.empty-state icon="icons.document-icon" :message="__('training_needs::dashboard.empty.deliveries')" />
                    @endforelse
                </div>
            </x-surface-card>

            <div class="space-y-4">
                <livewire:training-needs.results-summary :key="'training-needs-results-summary-'.$resultsSummaryVersion" lazy />

                <x-surface-card :title="__('training_needs::dashboard.cards.delivery_documents')" icon="icons.profile-outline-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                    <div class="grid gap-3">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.delivery_record')"
                            placeholder="---"
                            mode="gray"
                            direction="up"
                            class="w-full"
                            wire:model.live="deliveryDocumentForm.training_delivery_record_id"
                            :model="$this->deliveryRecordOptions()"
                            search-model="searchDeliveryRecord"
                        ></x-ui.select-dropdown>
                        @error('deliveryDocumentForm.training_delivery_record_id') <x-validation>{{ $message }}</x-validation> @enderror

                        <div>
                            <x-label for="delivery-certificate">{{ __('training_needs::dashboard.fields.certificate_file') }}</x-label>
                            <x-ui.file-upload
                                model="deliveryDocumentForm.certificate_file"
                                :data="data_get($deliveryDocumentForm, 'certificate_file')"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
                            />
                            @error('deliveryDocumentForm.certificate_file') <x-validation>{{ $message }}</x-validation> @enderror
                        </div>

                        @if ($selectedDeliveryRecord = $this->selectedDeliveryRecord)
                            @php
                                $pendingCertificate = data_get($deliveryDocumentForm, 'certificate_file');
                                $hasPendingCertificate = $pendingCertificate instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
                                $pendingCertificateName = $hasPendingCertificate ? $pendingCertificate->getClientOriginalName() : null;
                                $pendingCertificateExtension = $pendingCertificateName
                                    ? strtolower(pathinfo($pendingCertificateName, PATHINFO_EXTENSION) ?: 'file')
                                    : null;
                                $pendingCertificatePreviewUrl = $hasPendingCertificate
                                    && in_array($pendingCertificateExtension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)
                                    ? $pendingCertificate->temporaryUrl()
                                    : null;
                                $pendingCertificateKey = $hasPendingCertificate ? $pendingCertificate->getFilename() : 'persisted';
                            @endphp
                            <livewire:training-needs.certificate-viewer
                                :delivery-record-id="(int) $selectedDeliveryRecord->id"
                                :record-snapshot="[
                                    'id' => $selectedDeliveryRecord->id,
                                    'certificate_path' => $selectedDeliveryRecord->certificate_path,
                                    'certificate_name' => $selectedDeliveryRecord->certificate_name,
                                    'result_status' => $selectedDeliveryRecord->result_status,
                                    'completed_at' => optional($selectedDeliveryRecord->completed_at)?->toISOString(),
                                    'session' => ['title' => $selectedDeliveryRecord->session?->title],
                                    'program' => ['title' => $selectedDeliveryRecord->program?->title],
                                    'personnel' => ['fullname' => $selectedDeliveryRecord->personnel?->fullname],
                                ]"
                                :temporary-certificate-name="$pendingCertificateName"
                                :temporary-certificate-preview-url="$pendingCertificatePreviewUrl"
                                :temporary-certificate-extension="$pendingCertificateExtension"
                                :has-pending-upload="$hasPendingCertificate"
                                :key="'training-certificate-viewer-'.(int) $selectedDeliveryRecord->id.'-'.$pendingCertificateKey"
                            />
                        @endif

                        <x-button mode="black" wire:click="storeDeliveryDocument">{{ __('training_needs::dashboard.actions.save_certificate') }}</x-button>
                    </div>
                </x-surface-card>
            </div>
        </div>
    @endif

    @if ($activeTab === 'analytics')
        <livewire:training-needs.analytics lazy />
    @endif

    @if ($activeTab === 'reports')
        <livewire:training-needs.reports :key="'training-needs-reports-'.$reportsVersion" lazy />
    @endif

    @if ($activeTab === 'lists')
        <livewire:training-needs.lists lazy />
    @endif

    <x-ui.delete-confirmation-modal />
</div>
