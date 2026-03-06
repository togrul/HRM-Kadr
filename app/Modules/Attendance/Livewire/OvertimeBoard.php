<?php

namespace App\Modules\Attendance\Livewire;

use App\Models\AttendanceOvertimeRequest;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceOvertimeApprovalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class OvertimeBoard extends Component
{
    use WithPagination;

    public string $status = 'pending';

    public string $fromDate = '';

    public string $toDate = '';

    public array $approvedMinutes = [];

    public int $perPage = 20;

    public bool $canApprove = false;

    public function mount(int $year, int $month, AttendanceAuthorizationService $authorization): void
    {
        $from = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $this->fromDate = $from->toDateString();
        $this->toDate = $to->toDateString();
        $this->canApprove = $authorization->can('attendance.overtime.approve');

        if (! $authorization->can('attendance.view')) {
            abort(403);
        }
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function approve(int $requestId, AttendanceOvertimeApprovalService $service): void
    {
        if (! $this->canApprove) {
            abort(403);
        }

        $request = AttendanceOvertimeRequest::query()->find($requestId);
        if (! $request) {
            return;
        }

        try {
            $minutes = $this->approvedMinutes[$requestId] ?? null;
            $minutes = $minutes !== null ? (int) $minutes : null;
            $service->approve($request, (int) Auth::id(), $minutes);
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first() ?: __('Validation failed.'));

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('Overtime request approved.'));
    }

    public function reject(int $requestId, AttendanceOvertimeApprovalService $service): void
    {
        if (! $this->canApprove) {
            abort(403);
        }

        $request = AttendanceOvertimeRequest::query()->find($requestId);
        if (! $request) {
            return;
        }

        try {
            $service->reject($request, (int) Auth::id());
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first() ?: __('Validation failed.'));

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('Overtime request rejected.'));
    }

    public function render()
    {
        $items = AttendanceOvertimeRequest::query()
            ->with([
                'personnel:tabel_no,surname,name,patronymic',
                'requestedBy:id,name',
                'approvedBy:id,name',
            ])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->fromDate !== '', fn ($query) => $query->whereDate('date', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($query) => $query->whereDate('date', '<=', $this->toDate))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('attendance::livewire.attendance.overtime-board', [
            'items' => $items,
        ]);
    }
}
