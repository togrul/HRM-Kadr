<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Models\EmployeeRequestChangeRequest;
use App\Models\Leave;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Modules\Personnel\Application\Services\MyHr\MyHrRequestReviewReadService;
use App\Modules\Personnel\Application\Services\MyHr\MyHrRequestReviewService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SelfServiceRequestReviews extends Component
{
    public string $typeFilter = 'all';

    public string $scopeFilter = 'mine';

    public string $search = '';

    /** @var array<string,string> */
    public array $notes = [];

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->can('review-self-service-requests')
            || auth()->user()?->can('review-all-self-service-requests'),
            403
        );

        if (! auth()->user()?->can('review-all-self-service-requests')) {
            $this->scopeFilter = 'mine';
        }
    }

    #[Computed]
    public function payload(): array
    {
        return app(MyHrRequestReviewReadService::class)->build([
            'type' => $this->typeFilter,
            'scope' => $this->scopeFilter,
            'search' => $this->search,
        ], auth()->user());
    }

    public function approve(string $type, int $recordId): void
    {
        $service = app(MyHrRequestReviewService::class);
        $reviewer = auth()->user();
        $note = trim((string) ($this->notes[$this->noteKey($type, $recordId)] ?? ''));

        match ($type) {
            'leave' => $this->approveLeave($service, $reviewer, $recordId, $note ?: null),
            'vacation' => $this->approveVacation($service, $reviewer, $recordId, $note ?: null),
            'business_trip' => $this->approveBusinessTrip($service, $reviewer, $recordId, $note ?: null),
            'correction' => $this->approveCorrection($service, $reviewer, $recordId, $note ?: null),
            default => abort(404),
        };

        unset($this->notes[$this->noteKey($type, $recordId)]);
        unset($this->payload);

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.review.messages.approved'));
    }

    public function reject(string $type, int $recordId): void
    {
        $service = app(MyHrRequestReviewService::class);
        $reviewer = auth()->user();
        $note = trim((string) ($this->notes[$this->noteKey($type, $recordId)] ?? ''));

        match ($type) {
            'leave' => $this->rejectLeave($service, $reviewer, $recordId, $note ?: null),
            'vacation' => $this->rejectVacation($service, $reviewer, $recordId, $note ?: null),
            'business_trip' => $this->rejectBusinessTrip($service, $reviewer, $recordId, $note ?: null),
            'correction' => $this->rejectCorrection($service, $reviewer, $recordId, $note ?: null),
            default => abort(404),
        };

        unset($this->notes[$this->noteKey($type, $recordId)]);
        unset($this->payload);

        $this->dispatch('notify', type: 'success', message: __('personnel::my_hr.review.messages.rejected'));
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.self-service-request-reviews');
    }

    private function noteKey(string $type, int $recordId): string
    {
        return $type.'_'.$recordId;
    }

    private function approveLeave(MyHrRequestReviewService $service, $reviewer, int $recordId, ?string $note): void
    {
        $record = Leave::query()->findOrFail($recordId);
        abort_unless($service->canReviewLeave($record, $reviewer), 403);
        $service->approveLeave($record, $reviewer, $note);
    }

    private function approveVacation(MyHrRequestReviewService $service, $reviewer, int $recordId, ?string $note): void
    {
        $record = PersonnelVacation::query()->findOrFail($recordId);
        abort_unless($service->canReviewVacation($record, $reviewer), 403);
        $service->approveVacation($record, $reviewer, $note);
    }

    private function approveBusinessTrip(MyHrRequestReviewService $service, $reviewer, int $recordId, ?string $note): void
    {
        $record = PersonnelBusinessTrip::query()->findOrFail($recordId);
        abort_unless($service->canReviewBusinessTrip($record, $reviewer), 403);
        $service->approveBusinessTrip($record, $reviewer, $note);
    }

    private function approveCorrection(MyHrRequestReviewService $service, $reviewer, int $recordId, ?string $note): void
    {
        $record = EmployeeRequestChangeRequest::query()->findOrFail($recordId);
        abort_unless($service->canReviewCorrection($record, $reviewer), 403);
        $service->approveCorrection($record, $reviewer, $note);
    }

    private function rejectLeave(MyHrRequestReviewService $service, $reviewer, int $recordId, ?string $note): void
    {
        $record = Leave::query()->findOrFail($recordId);
        abort_unless($service->canReviewLeave($record, $reviewer), 403);
        $service->rejectLeave($record, $reviewer, $note);
    }

    private function rejectVacation(MyHrRequestReviewService $service, $reviewer, int $recordId, ?string $note): void
    {
        $record = PersonnelVacation::query()->findOrFail($recordId);
        abort_unless($service->canReviewVacation($record, $reviewer), 403);
        $service->rejectVacation($record, $reviewer, $note);
    }

    private function rejectBusinessTrip(MyHrRequestReviewService $service, $reviewer, int $recordId, ?string $note): void
    {
        $record = PersonnelBusinessTrip::query()->findOrFail($recordId);
        abort_unless($service->canReviewBusinessTrip($record, $reviewer), 403);
        $service->rejectBusinessTrip($record, $reviewer, $note);
    }

    private function rejectCorrection(MyHrRequestReviewService $service, $reviewer, int $recordId, ?string $note): void
    {
        $record = EmployeeRequestChangeRequest::query()->findOrFail($recordId);
        abort_unless($service->canReviewCorrection($record, $reviewer), 403);
        $service->rejectCorrection($record, $reviewer, $note);
    }
}
