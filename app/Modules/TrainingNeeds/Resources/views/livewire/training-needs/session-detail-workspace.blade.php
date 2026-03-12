<x-surface-card :title="__('training_needs::dashboard.cards.session_detail')" icon="icons.profile-outline-icon">
    @if ($this->selectedSession)
        <div class="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
            <div class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.fields.session') }}</p>
                        <p class="mt-1 text-sm font-semibold text-zinc-900">{{ $this->selectedSession->title }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.fields.program') }}</p>
                        <p class="mt-1 text-sm font-semibold text-zinc-900">{{ $this->selectedSession->program?->title ?? '---' }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.fields.scheduled_start_at') }}</p>
                        <p class="mt-1 text-sm font-semibold text-zinc-900">{{ optional($this->selectedSession->scheduled_start_at)->format('d.m.Y H:i') ?: '---' }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.fields.location') }}</p>
                        <p class="mt-1 text-sm font-semibold text-zinc-900">{{ $this->selectedSession->location ?: '---' }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.participant_count', ['count' => 0]) }}</p>
                        <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->selectedSession->participants->count() }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase text-zinc-400">{{ __('training_needs::dashboard.labels.attended_participants') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $this->selectedSession->participants->where('attendance_status', 'attended')->count() }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-zinc-800">{{ __('training_needs::dashboard.fields.participant_search') }}</p>
                            <p class="text-xs text-zinc-500">{{ __('training_needs::dashboard.empty.filtered_session_participants') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-ui.action-pill mode="secondary" wire:click="selectVisibleParticipants" icon="icons.profile-icon">{{ __('training_needs::dashboard.actions.select_visible_participants') }}</x-ui.action-pill>
                            <x-ui.action-pill mode="secondary" wire:click="clearSelectedParticipants">{{ __('training_needs::dashboard.actions.clear_selected_participants') }}</x-ui.action-pill>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="min-w-56 flex-1">
                            <x-label for="selected-participant-search">{{ __('training_needs::dashboard.fields.participant_search') }}</x-label>
                            <x-livewire-input mode="gray" id="selected-participant-search" wire:model.live.debounce.300ms="searchSelectedParticipant" />
                        </div>
                        <div class="min-w-48 flex-1">
                            <x-label for="selected-attendance-filter">{{ __('training_needs::dashboard.fields.participant_attendance_filter') }}</x-label>
                            <select id="selected-attendance-filter" wire:model.live="selectedParticipantAttendanceFilter" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                                <option value="all">---</option>
                                <option value="planned">{{ __('training_needs::dashboard.attendance_statuses.planned') }}</option>
                                <option value="confirmed">{{ __('training_needs::dashboard.attendance_statuses.confirmed') }}</option>
                                <option value="attended">{{ __('training_needs::dashboard.attendance_statuses.attended') }}</option>
                                <option value="absent">{{ __('training_needs::dashboard.attendance_statuses.absent') }}</option>
                                <option value="cancelled">{{ __('training_needs::dashboard.attendance_statuses.cancelled') }}</option>
                            </select>
                        </div>
                        <div class="min-w-48 flex-1">
                            <x-label for="selected-source-filter">{{ __('training_needs::dashboard.fields.participant_source_filter') }}</x-label>
                            <select id="selected-source-filter" wire:model.live="selectedParticipantSourceFilter" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                                <option value="all">---</option>
                                <option value="manual">{{ __('training_needs::dashboard.sources.manual') }}</option>
                                <option value="performance_gap">{{ __('training_needs::dashboard.sources.performance_gap') }}</option>
                                <option value="skill_gap">{{ __('training_needs::dashboard.sources.skill_gap') }}</option>
                                <option value="manager_request">{{ __('training_needs::dashboard.sources.manager_request') }}</option>
                                <option value="employee_request">{{ __('training_needs::dashboard.sources.employee_request') }}</option>
                            </select>
                        </div>
                        <div class="min-w-56 flex-1">
                            <x-label for="bulk-attendance-status">{{ __('training_needs::dashboard.fields.bulk_attendance_status') }}</x-label>
                            <select id="bulk-attendance-status" wire:model.defer="bulkAttendanceStatus" class="h-10 w-full rounded-lg border-none bg-neutral-100 px-3 text-sm shadow-sm focus:ring-blue-500">
                                <option value="planned">{{ __('training_needs::dashboard.attendance_statuses.planned') }}</option>
                                <option value="confirmed">{{ __('training_needs::dashboard.attendance_statuses.confirmed') }}</option>
                                <option value="attended">{{ __('training_needs::dashboard.attendance_statuses.attended') }}</option>
                                <option value="absent">{{ __('training_needs::dashboard.attendance_statuses.absent') }}</option>
                                <option value="cancelled">{{ __('training_needs::dashboard.attendance_statuses.cancelled') }}</option>
                            </select>
                        </div>
                        <x-ui.action-pill wire:click="applyBulkParticipantStatus">{{ __('training_needs::dashboard.actions.apply_bulk_status') }}</x-ui.action-pill>
                        <x-ui.action-pill mode="delete" wire:click="confirmRemoveSelectedParticipants" icon="icons.delete-icon">{{ __('training_needs::dashboard.actions.remove_selected_participants') }}</x-ui.action-pill>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <x-ui.action-pill mode="secondary" wire:click="applyBulkParticipantStatusShortcut('confirmed')">{{ __('training_needs::dashboard.attendance_statuses.confirmed') }}</x-ui.action-pill>
                        <x-ui.action-pill mode="secondary" wire:click="applyBulkParticipantStatusShortcut('attended')">{{ __('training_needs::dashboard.attendance_statuses.attended') }}</x-ui.action-pill>
                        <x-ui.action-pill mode="secondary" wire:click="applyBulkParticipantStatusShortcut('absent')">{{ __('training_needs::dashboard.attendance_statuses.absent') }}</x-ui.action-pill>
                        <x-ui.action-pill mode="secondary" wire:click="applyBulkParticipantStatusShortcut('cancelled')">{{ __('training_needs::dashboard.attendance_statuses.cancelled') }}</x-ui.action-pill>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                @forelse ($this->filteredParticipants as $participant)
                    <x-ui.list-card>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" wire:model.live="bulkParticipantIds" value="{{ $participant->id }}" class="mt-1 rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900">{{ $participant->personnel?->fullname ?? '---' }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $participant->personnel?->tabel_no ? '#'.$participant->personnel->tabel_no : '---' }} @if($participant->trainingNeed?->reason) • {{ $participant->trainingNeed->presentedReason() }} @endif</p>
                                </div>
                            </div>
                            <x-small-badge mode="{{ $participant->attendance_status === 'attended' ? 'green' : ($participant->attendance_status === 'absent' ? 'red' : 'sky') }}">
                                {{ __('training_needs::dashboard.attendance_statuses.'.$participant->attendance_status) }}
                            </x-small-badge>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" wire:click="quickSetParticipantStatus({{ $participant->id }}, 'confirmed')" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:border-blue-300 hover:text-blue-700">{{ __('training_needs::dashboard.attendance_statuses.confirmed') }}</button>
                            <button type="button" wire:click="quickSetParticipantStatus({{ $participant->id }}, 'attended')" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:border-green-300 hover:text-green-700">{{ __('training_needs::dashboard.attendance_statuses.attended') }}</button>
                            <button type="button" wire:click="quickSetParticipantStatus({{ $participant->id }}, 'absent')" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:border-rose-300 hover:text-rose-700">{{ __('training_needs::dashboard.attendance_statuses.absent') }}</button>
                            <button type="button" wire:click="quickSetParticipantStatus({{ $participant->id }}, 'cancelled')" class="rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:border-zinc-400 hover:text-zinc-900">{{ __('training_needs::dashboard.attendance_statuses.cancelled') }}</button>
                        </div>
                    </x-ui.list-card>
                @empty
                    <x-ui.empty-state icon="icons.users-icon" :message="__('training_needs::dashboard.empty.filtered_session_participants')" />
                @endforelse
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <x-button mode="success" wire:click="completeSession">{{ __('training_needs::dashboard.actions.complete_session') }}</x-button>
        </div>
    @else
        <x-ui.empty-state icon="icons.calendar-icon" :message="__('training_needs::dashboard.empty.sessions')" />
    @endif

    <x-ui.delete-confirmation-modal />
</x-surface-card>
