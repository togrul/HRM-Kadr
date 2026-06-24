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
                            direction="auto"
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
                            direction="auto"
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
                            direction="auto"
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

