<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Livewire\Concerns\ConfirmsDestructiveActions;
use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\TrainingSession;
use App\Models\TrainingSessionParticipant;
use App\Modules\TrainingNeeds\Application\Services\TrainingDeliveryService;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsAccess;
use Illuminate\Support\Str;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class SessionDetailWorkspace extends Component
{
    use ConfirmsDestructiveActions;
    use InteractsWithTrainingNeedsAccess;
    use WithRuntimeMemo;

    public int $sessionId;

    /**
     * @var array<int>
     */
    public array $bulkParticipantIds = [];

    public string $bulkAttendanceStatus = 'confirmed';

    public string $searchSelectedParticipant = '';

    public string $selectedParticipantAttendanceFilter = 'all';

    public string $selectedParticipantSourceFilter = 'all';

    public function mount(int $sessionId): void
    {
        $this->authorizeTrainingNeedsView();
        $this->sessionId = $sessionId;
    }

    public function getSelectedSessionProperty(): ?TrainingSession
    {
        return $this->rememberRuntime('trainingNeeds.sessionDetail.'.$this->sessionId, function () {
            return TrainingSession::query()
                ->with([
                    'program:id,title,duration_hours',
                    'participants.personnel:id,tabel_no,surname,name,patronymic',
                    'participants.trainingNeed:id,reason,priority,status,source',
                ])
                ->find($this->sessionId);
        });
    }

    public function getFilteredParticipantsProperty()
    {
        if (! $this->selectedSession) {
            return collect();
        }

        $search = Str::lower(trim($this->searchSelectedParticipant));
        $attendanceFilter = (string) ($this->selectedParticipantAttendanceFilter ?: 'all');
        $sourceFilter = (string) ($this->selectedParticipantSourceFilter ?: 'all');

        return $this->selectedSession->participants->filter(function (TrainingSessionParticipant $participant) use ($search, $attendanceFilter, $sourceFilter) {
            $fullname = Str::lower((string) ($participant->personnel?->fullname ?? ''));
            $tabelNo = Str::lower((string) ($participant->personnel?->tabel_no ?? ''));
            $reason = Str::lower($participant->trainingNeed?->presentedReason() ?? '');
            $source = (string) ($participant->trainingNeed?->source ?? 'manual');

            if ($attendanceFilter !== 'all' && $participant->attendance_status !== $attendanceFilter) {
                return false;
            }

            if ($sourceFilter !== 'all' && $source !== $sourceFilter) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            return str_contains($fullname, $search)
                || str_contains($tabelNo, $search)
                || str_contains($reason, $search);
        })->values();
    }

    public function selectVisibleParticipants(): void
    {
        $this->bulkParticipantIds = $this->filteredParticipants
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function clearSelectedParticipants(): void
    {
        $this->bulkParticipantIds = [];
    }

    public function quickSetParticipantStatus(int $participantId, string $status): void
    {
        $this->authorizeTrainingNeedsManage();
        abort_unless(in_array($status, ['planned', 'confirmed', 'attended', 'absent', 'cancelled'], true), 404);

        $participant = TrainingSessionParticipant::query()
            ->where('training_session_id', $this->sessionId)
            ->findOrFail($participantId);

        $participant->forceFill([
            'attendance_status' => $status,
            'attended_at' => $status === 'attended' ? ($participant->attended_at ?? now()) : null,
        ])->save();

        $this->refreshWorkspace();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.participant_status_updated'));
        $this->dispatch('training-needs:calendar-mutated');
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
            'bulkAttendanceStatus' => 'required|in:planned,confirmed,attended,absent,cancelled',
            'bulkParticipantIds' => 'required|array|min:1',
            'bulkParticipantIds.*' => 'integer|exists:training_session_participants,id',
        ], attributes: [
            'bulkAttendanceStatus' => __('training_needs::dashboard.fields.attendance_status'),
            'bulkParticipantIds' => __('training_needs::dashboard.fields.selected_participants'),
        ]);

        $status = (string) $validated['bulkAttendanceStatus'];

        TrainingSessionParticipant::query()
            ->where('training_session_id', $this->sessionId)
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
        $this->refreshWorkspace();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.bulk_participants_updated', [
            'count' => $count,
        ]));
        $this->dispatch('training-needs:calendar-mutated');
    }

    public function confirmRemoveSelectedParticipants(): void
    {
        $validated = $this->validate([
            'bulkParticipantIds' => 'required|array|min:1',
        ], attributes: [
            'bulkParticipantIds' => __('training_needs::dashboard.fields.selected_participants'),
        ]);

        $this->confirmDeletion(
            action: 'removeSelectedParticipants',
            message: __('training_needs::dashboard.confirmations.remove_selected_participants'),
            description: __('training_needs::dashboard.fields.selected_participants').': '.count((array) $validated['bulkParticipantIds']),
            confirmLabel: __('training_needs::dashboard.actions.remove_selected_participants'),
        );
    }

    public function removeSelectedParticipants(): void
    {
        $this->authorizeTrainingNeedsManage();

        $validated = $this->validate([
            'bulkParticipantIds' => 'required|array|min:1',
            'bulkParticipantIds.*' => 'integer|exists:training_session_participants,id',
        ], attributes: [
            'bulkParticipantIds' => __('training_needs::dashboard.fields.selected_participants'),
        ]);

        TrainingSessionParticipant::query()
            ->where('training_session_id', $this->sessionId)
            ->whereIn('id', $validated['bulkParticipantIds'])
            ->delete();

        $count = count($validated['bulkParticipantIds']);
        $this->bulkParticipantIds = [];
        $this->refreshWorkspace();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.bulk_participants_removed', [
            'count' => $count,
        ]));
        $this->dispatch('training-needs:calendar-mutated');
    }

    public function completeSession(TrainingDeliveryService $service): void
    {
        $this->authorizeTrainingNeedsManage();

        $session = TrainingSession::query()->findOrFail($this->sessionId);
        $stats = $service->completeSession($session);

        $this->refreshWorkspace();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.session_completed', [
            'attended' => $stats['attended_count'],
            'records' => $stats['record_count'],
        ]));
        $this->dispatch('training-needs:calendar-mutated');
    }

    protected function refreshWorkspace(): void
    {
        $this->resetRuntimeMemo();
        $this->resetValidation();
    }

    public function render()
    {
        return view('training-needs::livewire.training-needs.session-detail-workspace');
    }
}
