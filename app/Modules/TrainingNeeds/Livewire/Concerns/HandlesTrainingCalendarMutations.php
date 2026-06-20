<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\TrainingSession;
use App\Models\TrainingSessionParticipant;
use App\Modules\TrainingNeeds\Application\Services\TrainingDeliveryService;

trait HandlesTrainingCalendarMutations
{
    public function storeSessionParticipant(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'participantForm.training_session_id' => 'required|exists:training_sessions,id',
            'participantForm.personnel_id' => 'required|exists:personnels,id',
            'participantForm.training_need_item_id' => 'nullable|exists:training_need_items,id',
            'participantForm.attendance_status' => 'required|in:planned,confirmed,attended,absent,cancelled',
        ], attributes: [
            'participantForm.training_session_id' => __('training_needs::dashboard.fields.session'),
            'participantForm.personnel_id' => __('training_needs::dashboard.fields.personnel'),
            'participantForm.training_need_item_id' => __('training_needs::dashboard.fields.training_need'),
            'participantForm.attendance_status' => __('training_needs::dashboard.fields.attendance_status'),
        ]);

        TrainingSessionParticipant::query()->updateOrCreate(
            [
                'training_session_id' => (int) data_get($validated, 'participantForm.training_session_id'),
                'personnel_id' => (int) data_get($validated, 'participantForm.personnel_id'),
            ],
            [
                'training_need_item_id' => data_get($validated, 'participantForm.training_need_item_id'),
                'attendance_status' => (string) data_get($validated, 'participantForm.attendance_status'),
                'attended_at' => in_array((string) data_get($validated, 'participantForm.attendance_status'), ['attended', 'completed'], true) ? now() : null,
            ]
        );

        $this->reset('participantForm', 'searchSession', 'searchPersonnel');
        $this->participantForm = $this->participantDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.participant_saved'));
        $this->dispatch('training-needs:calendar-mutated');
    }

    public function quickSetParticipantStatus(int $participantId, string $status): void
    {
        $this->authorizeTrainingNeedsManage();
        abort_unless(in_array($status, ['planned', 'confirmed', 'attended', 'absent', 'cancelled'], true), 404);

        $participant = TrainingSessionParticipant::query()->findOrFail($participantId);
        $participant->forceFill([
            'attendance_status' => $status,
            'attended_at' => $status === 'attended' ? ($participant->attended_at ?? now()) : null,
        ])->save();

        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.participant_status_updated'));
        $this->dispatch('training-needs:calendar-mutated');
    }

    public function selectVisibleParticipants(): void
    {
        $this->bulkParticipantIds = collect($this->filteredSelectedParticipants)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function clearSelectedParticipants(): void
    {
        $this->bulkParticipantIds = [];
    }

    public function applyBulkParticipantStatusShortcut(string $status): void
    {
        $this->bulkAttendanceStatus = $status;
        $this->applyBulkParticipantStatus();
    }

    public function applyBulkParticipantStatus(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'selectedSessionId' => 'required|exists:training_sessions,id',
            'bulkAttendanceStatus' => 'required|in:planned,confirmed,attended,absent,cancelled',
            'bulkParticipantIds' => 'required|array|min:1',
            'bulkParticipantIds.*' => 'integer|exists:training_session_participants,id',
        ], attributes: [
            'selectedSessionId' => __('training_needs::dashboard.fields.session'),
            'bulkAttendanceStatus' => __('training_needs::dashboard.fields.attendance_status'),
            'bulkParticipantIds' => __('training_needs::dashboard.fields.selected_participants'),
        ]);

        $status = (string) $validated['bulkAttendanceStatus'];

        TrainingSessionParticipant::query()
            ->where('training_session_id', (int) $validated['selectedSessionId'])
            ->whereIn('id', $validated['bulkParticipantIds'])
            ->get()
            ->each(function (TrainingSessionParticipant $participant) use ($status): void {
                $participant->forceFill([
                    'attendance_status' => $status,
                    'attended_at' => $status === 'attended' ? ($participant->attended_at ?? now()) : null,
                ])->save();
            });

        $count = count($validated['bulkParticipantIds']);
        $this->bulkParticipantIds = [];
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.bulk_participants_updated', [
            'count' => $count,
        ]));
        $this->dispatch('training-needs:calendar-mutated');
    }

    public function removeSelectedParticipants(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'selectedSessionId' => 'required|exists:training_sessions,id',
            'bulkParticipantIds' => 'required|array|min:1',
            'bulkParticipantIds.*' => 'integer|exists:training_session_participants,id',
        ], attributes: [
            'selectedSessionId' => __('training_needs::dashboard.fields.session'),
            'bulkParticipantIds' => __('training_needs::dashboard.fields.selected_participants'),
        ]);

        TrainingSessionParticipant::query()
            ->where('training_session_id', (int) $validated['selectedSessionId'])
            ->whereIn('id', $validated['bulkParticipantIds'])
            ->delete();

        $count = count($validated['bulkParticipantIds']);
        $this->bulkParticipantIds = [];
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.bulk_participants_removed', [
            'count' => $count,
        ]));
        $this->dispatch('training-needs:calendar-mutated');
    }

    public function completeSession(TrainingDeliveryService $service): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'participantForm.training_session_id' => 'required|exists:training_sessions,id',
        ], attributes: [
            'participantForm.training_session_id' => __('training_needs::dashboard.fields.session'),
        ]);

        $session = TrainingSession::query()->findOrFail((int) data_get($validated, 'participantForm.training_session_id'));
        $stats = $service->completeSession($session);

        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.session_completed', [
            'attended' => $stats['attended_count'],
            'records' => $stats['record_count'],
        ]));
        $this->dispatch('training-needs:calendar-mutated');
    }
}
