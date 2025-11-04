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

class AddLeave extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use InteractsWithLeaveForm;

    #[Locked] public string $title = '';

    public LeaveForm $leave;

    public function mount(): void
    {
        $this->title = __('Add leave');
        $this->leave->resetForm();
    }

    public function store(): void
    {
        $this->leave->validate();

        $payload = $this->leave->toPayload();

        $file = $this->leave->document_path;
        if ($file instanceof TemporaryUploadedFile) {
            $payload['document_path'] = $file->store('leaves', 'public');
        }

        DB::transaction(fn () => Leave::create($payload));

        $this->dispatch('leaveAdded', __('Leave was added successfully!'));

        $this->leave->resetForm();
        $this->reset('personnelName', 'assignedSearch');
    }

    public function render()
    {
        return view('livewire.outside.add-leave');
    }
}
