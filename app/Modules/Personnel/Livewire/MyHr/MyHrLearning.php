<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\EmployeeContentAssignment;
use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\MyHr\MyHrLearningReadService;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyHrLearning extends Component
{
    public int $personnelId;

    public function mount(MyHrAccess $access, int $personnelId): void
    {
        $access->authorize(Auth::user());
        abort_unless($access->canAccess(Auth::user(), 'view-own-learning-content'), 403);
        abort_if($personnelId <= 0, 404);

        $this->personnelId = $personnelId;
    }

    public function openContent(int $assignmentId)
    {
        $assignment = $this->assignment($assignmentId);

        $view = $assignment->view()->firstOrCreate([], [
            'opened_at' => now(),
        ]);

        if (blank($view->opened_at)) {
            $view->forceFill(['opened_at' => now()])->save();
        }

        if ($assignment->status === 'assigned') {
            $assignment->forceFill(['status' => 'opened'])->save();
        }

        $url = $assignment->asset?->contentUrl();
        if (! $url) {
            $this->dispatch('notify', type: 'error', message: __('personnel::my_hr.learning.messages.file_not_available'));

            return;
        }

        return $this->redirect($url, navigate: false);
    }

    public function complete(int $assignmentId): void
    {
        $assignment = $this->assignment($assignmentId);

        $view = $assignment->view()->firstOrCreate([], [
            'opened_at' => now(),
        ]);

        $view->forceFill([
            'opened_at' => $view->opened_at ?: now(),
            'completed_at' => now(),
            'watch_progress_percent' => 100,
        ])->save();

        $assignment->forceFill(['status' => 'completed'])->save();

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.learning.messages.completed_success'));
    }

    #[Computed]
    public function payload(): array
    {
        return app(MyHrLearningReadService::class)->build($this->personnel());
    }

    protected function assignment(int $assignmentId): EmployeeContentAssignment
    {
        return EmployeeContentAssignment::query()
            ->where('personnel_id', $this->personnelId)
            ->with(['asset', 'view'])
            ->findOrFail($assignmentId);
    }

    protected function personnel(): Personnel
    {
        return Personnel::query()
            ->select(['id', 'tabel_no', 'surname', 'name', 'patronymic'])
            ->findOrFail($this->personnelId);
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.learning');
    }
}
