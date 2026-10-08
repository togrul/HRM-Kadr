<?php

namespace App\Modules\Leaves\Livewire;

use App\Livewire\Forms\LeaveForm;
use App\Models\Leave;
use App\Models\Personnel;
use App\Modules\Leaves\Application\Services\LeaveRecordService;
use App\Modules\Leaves\Livewire\Concerns\InteractsWithLeaveForm;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AddLeave extends Component
{
    use AuthorizesRequests;
    use InteractsWithLeaveForm;
    use WithFileUploads;

    #[Locked]
    public string $title = '';

    public LeaveForm $leave;

    /**
     * @param  string|null  $tabelNo  preselects the applicant when the form is opened from a personnel file
     */
    public function mount(?string $tabelNo = null): void
    {
        $this->authorize('create', Leave::class);
        $this->title = __('leaves::common.titles.add_leave');
        $this->leave->resetForm();
        $this->syncSelectedLeaveTypeMeta();
        $this->initializeAssignmentMode();

        $applicant = filled($tabelNo)
            ? Personnel::query()->select('tabel_no', 'surname', 'name', 'patronymic')->where('tabel_no', $tabelNo)->first()
            : null;

        if ($applicant !== null) {
            $this->selectPersonnel($applicant->tabel_no, $applicant->fullname, 'tabel_no');
        }
    }

    public function store(): void
    {
        $this->authorize('create', Leave::class);
        $this->syncAssignmentForPersistence();
        $this->leave->validate();

        $payload = $this->leave->toPayload();
        $records = app(LeaveRecordService::class);

        // Business rules first, so a rejected leave never leaves an orphan upload behind.
        $this->withLeaveFormErrors(fn () => $records->assertValid($payload, auth()->user()));

        $file = $this->leave->document_path;
        if ($file instanceof TemporaryUploadedFile) {
            $payload['document_path'] = $file->store('leaves', 'public');
        }

        $this->withLeaveFormErrors(fn () => $records->create($payload, auth()->user()));

        $this->dispatch('leaveAdded', __('leaves::common.messages.leave_added'));

        $this->leave->resetForm();
        $this->syncSelectedLeaveTypeMeta();
        $this->initializeAssignmentMode();
        $this->reset('personnelName', 'assignedSearch');
    }

    public function render(): View
    {
        return view('leaves::livewire.leaves.add-leave');
    }
}
