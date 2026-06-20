<?php

namespace App\Modules\Leaves\Livewire;

use App\Livewire\Forms\LeaveForm;
use App\Models\Leave;
use App\Modules\Leaves\Livewire\Concerns\InteractsWithLeaveForm;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class EditLeave extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use InteractsWithLeaveForm;

    #[Locked]
    public string $title = '';

    public LeaveForm $leave;
    public ?int $leaveModel = null;

    public function mount(?int $leaveModel = null): void
    {
        $this->authorize('viewAny', Leave::class);
        $this->resetEditor();

        if ($leaveModel) {
            $this->loadLeaveForEdit($leaveModel);
        }
    }

    public function store(): void
    {
        if (! $this->leaveModel) {
            return;
        }

        $record = Leave::query()->find($this->leaveModel);

        if (! $record) {
            return;
        }

        $this->authorize('update', $record);
        $currentAssignmentPreview = $this->leave->assignment_mode === 'auto'
            ? $this->assignmentPreview
            : null;
        $this->syncAssignmentForPersistence();
        $this->leave->validate();

        $payload = $this->leave->toPayload();

        $file = $this->leave->document_path;
        if ($file instanceof TemporaryUploadedFile) {
            $payload['document_path'] = $file->store('leaves', 'public');
        }

        DB::transaction(fn () => $record->update($payload));

        $record = $record->fresh($this->leaveRelations());
        $this->leave->fillFromModel($record);
        $this->syncSelectedLeaveTypeMeta();
        $this->rehydrateAssignmentModeAfterSave($record, $currentAssignmentPreview);

        if ($file instanceof TemporaryUploadedFile) {
            $this->leave->document_path = null;
        }

        $this->reset('personnelName', 'assignedSearch');

        $this->dispatch('leaveAdded', __('leaves::common.messages.leave_updated'));
    }

    #[On('setEditLeaveModel')]
    public function loadLeaveForEdit(int $leaveId): void
    {
        $this->resetEditor();
        $this->leaveModel = $leaveId;
        $record = Leave::query()
            ->with($this->leaveRelations())
            ->find($leaveId);

        if (! $record) {
            return;
        }

        $this->authorize('update', $record);
        $this->leave->fillFromModel($record);
        $this->resetAssignmentPreviewState();
        $this->syncSelectedLeaveTypeMeta();
        $this->initializeAssignmentMode($record);
    }

    #[On('closeSideMenu')]
    public function resetEditor(): void
    {
        $this->title = __('leaves::common.titles.edit_leave');
        $this->leaveModel = null;
        $this->leave->resetForm();
        $this->resetAssignmentPreviewState();
        $this->syncSelectedLeaveTypeMeta();
        $this->initializeAssignmentMode();
        $this->reset('personnelName', 'assignedSearch');
    }

    public function render()
    {
        return view('leaves::livewire.leaves.edit-leave');
    }

    protected function leaveRelations(): array
    {
        return [
            'personnel' => fn ($query) => $query->select([
                'tabel_no',
                'surname',
                'name',
                'patronymic',
            ]),
            'assigned' => fn ($query) => $query->select([
                'id',
                'tabel_no',
                'surname',
                'name',
                'patronymic',
                'position_id',
                'structure_id',
            ])->with([
                'position:id,name,approval_rank,is_approval_target',
            ]),
            'fallbackApprover' => fn ($query) => $query->select([
                'id',
                'tabel_no',
                'surname',
                'name',
                'patronymic',
                'position_id',
                'structure_id',
            ])->with([
                'position:id,name,approval_rank,is_approval_target',
            ]),
        ];
    }
}
