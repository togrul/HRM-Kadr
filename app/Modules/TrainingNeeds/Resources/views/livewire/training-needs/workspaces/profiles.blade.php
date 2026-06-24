    @if ($activeTab === 'profiles')
        <div class="grid gap-4 xl:grid-cols-2">
            <x-surface-card :title="__('training_needs::dashboard.cards.employee_profiles')" icon="icons.profile-icon" bodyClass="overflow-visible" contentClass="overflow-visible p-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.select-dropdown
                            :label="__('training_needs::dashboard.fields.personnel')"
                            placeholder="---"
                            mode="gray"
                            direction="auto"
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

