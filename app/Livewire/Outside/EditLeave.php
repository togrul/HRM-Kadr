<?php

namespace App\Livewire\Outside;

use App\Models\Leave;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\Locked;
use App\Livewire\Forms\LeaveForm;
use App\Livewire\Outside\Concerns\InteractsWithLeaveForm;

class EditLeave extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use InteractsWithLeaveForm;

    #[Locked] public string $title = '';

    public LeaveForm $leave;
    public ?Leave $record = null;
    public ?int $leaveModel = null;

    public function mount(?int $leaveModel = null): void
    {
        $this->title = __('Edit leave');
        $this->leave->resetForm();

        if (! $leaveModel) {
            return;
        }

        $this->leaveModel = $leaveModel;
        $this->record = Leave::query()
            ->with(['personnel', 'assigned'])
            ->find($leaveModel);

        if (! $this->record) {
            return;
        }

        $this->leave->fillFromModel($this->record);
    }

    public function store(): void
    {
        if (! $this->record) {
            return;
        }

        $this->leave->validate();

        $payload = $this->leave->toPayload();

        $file = $this->leave->document_path;
        if ($file instanceof TemporaryUploadedFile) {
            $payload['document_path'] = $file->store('leaves', 'public');
        }

        DB::transaction(fn () => $this->record->update($payload));

        $this->record = $this->record->fresh(['personnel', 'assigned']);
        $this->leave->fillFromModel($this->record);

        if ($file instanceof TemporaryUploadedFile) {
            $this->leave->document_path = null;
        }

        $this->reset('personnelName', 'assignedSearch');

        $this->dispatch('leaveAdded', __('Leave was updated successfully!'));
    }

    public function render()
    {
        return view('livewire.outside.edit-leave');
    }
}
