<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\OnboardingDocumentAssignment;
use App\Models\Personnel;
use App\Modules\Personnel\Application\Services\MyHr\MyHrOnboardingReadService;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyHrOnboarding extends Component
{
    public int $personnelId;

    public function mount(MyHrAccess $access, int $personnelId): void
    {
        $access->authorize(Auth::user());
        abort_unless($access->canAccess(Auth::user(), 'view-own-onboarding-documents'), 403);
        abort_if($personnelId <= 0, 404);

        $this->personnelId = $personnelId;
    }

    public function openDocument(int $assignmentId)
    {
        $assignment = $this->assignment($assignmentId);

        $receipt = $assignment->receipt()->firstOrCreate([], [
            'opened_at' => now(),
        ]);

        if (blank($receipt->opened_at)) {
            $receipt->forceFill(['opened_at' => now()])->save();
        }

        if ($assignment->status === 'assigned') {
            $assignment->forceFill(['status' => 'opened'])->save();
        }

        $url = $assignment->template?->fileUrl();

        if (! $url) {
            $this->dispatch('notify', type: 'error', message: __('personnel::my_hr.onboarding.messages.file_not_available'));

            return;
        }

        return $this->redirect($url, navigate: false);
    }

    public function acknowledge(int $assignmentId): void
    {
        abort_unless(app(MyHrAccess::class)->canAccess(Auth::user(), 'acknowledge-own-onboarding-documents'), 403);

        $assignment = $this->assignment($assignmentId);

        $receipt = $assignment->receipt()->firstOrCreate([], [
            'opened_at' => now(),
        ]);

        $receipt->forceFill([
            'opened_at' => $receipt->opened_at ?: now(),
            'acknowledged_at' => now(),
            'acknowledged_ip' => request()->ip(),
            'acknowledged_user_agent' => (string) request()->userAgent(),
        ])->save();

        $assignment->forceFill(['status' => 'acknowledged'])->save();

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.onboarding.messages.acknowledged_success'));
    }

    #[Computed]
    public function payload(): array
    {
        return app(MyHrOnboardingReadService::class)->build($this->personnel());
    }

    protected function assignment(int $assignmentId): OnboardingDocumentAssignment
    {
        return OnboardingDocumentAssignment::query()
            ->where('personnel_id', $this->personnelId)
            ->with(['template', 'receipt'])
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
        return view('personnel::livewire.personnel.my-hr.onboarding');
    }
}
