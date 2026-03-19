<?php

namespace App\Modules\Leaves\Livewire;

use App\Livewire\Forms\LeaveForm;
use App\Models\Leave;
use App\Modules\Leaves\Livewire\Concerns\InteractsWithLeaveForm;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AddLeave extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use InteractsWithLeaveForm;

    #[Locked]
    public string $title = '';

    public LeaveForm $leave;

    public function mount(): void
    {
        $this->authorize('create', Leave::class);
        $this->title = __('leaves::common.titles.add_leave');
        $this->leave->resetForm();
        $this->syncSelectedLeaveTypeMeta();
    }

    public function store(): void
    {
        $this->authorize('create', Leave::class);
        $this->leave->validate();

        $payload = $this->leave->toPayload();

        $file = $this->leave->document_path;
        if ($file instanceof TemporaryUploadedFile) {
            $payload['document_path'] = $file->store('leaves', 'public');
        }

        DB::transaction(fn () => Leave::create($payload));

        $this->dispatch('leaveAdded', __('leaves::common.messages.leave_added'));

        $this->leave->resetForm();
        $this->syncSelectedLeaveTypeMeta();
        $this->reset('personnelName', 'assignedSearch');
    }

    public function render()
    {
        return view('leaves::livewire.leaves.add-leave');
    }
}
