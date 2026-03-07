<?php

namespace App\Modules\Attendance\Livewire;

use App\Models\AttendanceException;
use App\Models\Structure;
use App\Modules\Attendance\Application\Services\AttendanceAuditLogger;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Traits\NestedStructureTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ExceptionsInbox extends Component
{
    use WithPagination;
    use NestedStructureTrait;

    public int $year;

    public int $month;

    public string $status = 'open';

    public string $type = 'all';

    public string $fromDate = '';

    public string $toDate = '';

    public int $perPage = 20;

    public bool $canResolve = false;

    public ?int $selectedStructureId = null;

    public function mount(int $year, int $month, AttendanceAuthorizationService $authorization): void
    {
        if (! $authorization->can('attendance.view')) {
            abort(403);
        }

        $this->canResolve = $authorization->can('attendance.exceptions.resolve');
        $this->year = $year;
        $this->month = $month;

        $from = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $this->fromDate = $from->toDateString();
        $this->toDate = $to->toDateString();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
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

    public function updatedSelectedStructureId(): void
    {
        $this->resetPage();
    }

    public function markResolved(int $exceptionId): void
    {
        if (! $this->canResolve) {
            abort(403);
        }

        $exception = AttendanceException::query()->find($exceptionId);
        if (! $exception) {
            return;
        }

        $before = $exception->only(['status', 'resolution_note', 'resolved_by', 'resolved_at']);
        $exception->update([
            'status' => 'resolved',
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
            'resolution_note' => $exception->resolution_note ?: __('Resolved from exceptions inbox.'),
        ]);

        app(AttendanceAuditLogger::class)->log(
            event: 'exception.resolved',
            description: __('Attendance exception resolved from inbox.'),
            subject: $exception,
            properties: [
                'tabel_no' => $exception->tabel_no,
                'date' => $exception->date?->toDateString(),
                'type' => $exception->type,
                'before' => $before,
                'after' => $exception->only(['status', 'resolution_note', 'resolved_by', 'resolved_at']),
            ]
        );

        $this->dispatch('notify', type: 'success', message: __('Exception resolved.'));
    }

    public function reopen(int $exceptionId): void
    {
        if (! $this->canResolve) {
            abort(403);
        }

        $exception = AttendanceException::query()->find($exceptionId);
        if (! $exception) {
            return;
        }

        $before = $exception->only(['status', 'resolution_note', 'resolved_by', 'resolved_at']);
        $exception->update([
            'status' => 'open',
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution_note' => null,
        ]);

        app(AttendanceAuditLogger::class)->log(
            event: 'exception.reopened',
            description: __('Attendance exception reopened from inbox.'),
            subject: $exception,
            properties: [
                'tabel_no' => $exception->tabel_no,
                'date' => $exception->date?->toDateString(),
                'type' => $exception->type,
                'before' => $before,
                'after' => $exception->only(['status', 'resolution_note', 'resolved_by', 'resolved_at']),
            ]
        );

        $this->dispatch('notify', type: 'success', message: __('Exception reopened.'));
    }

    public function render()
    {
        $structureIds = $this->selectedStructureId
            ? $this->getNestedStructure($this->selectedStructureId)
            : [];

        $items = AttendanceException::query()
            ->with(['personnel:tabel_no,surname,name,patronymic,structure_id', 'personnel.structure:id,name,parent_id', 'resolvedBy:id,name'])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->type !== 'all', fn ($query) => $query->where('type', $this->type))
            ->when($this->fromDate !== '', fn ($query) => $query->whereDate('date', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($query) => $query->whereDate('date', '<=', $this->toDate))
            ->when($structureIds !== [], function ($query) use ($structureIds): void {
                $query->whereHas('personnel', fn ($personnelQuery) => $personnelQuery->whereIn('structure_id', $structureIds));
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('attendance::livewire.attendance.exceptions-inbox', [
            'items' => $items,
            'selectedStructureLabel' => $this->selectedStructureId
                ? Structure::query()->whereKey($this->selectedStructureId)->value('name')
                : null,
        ]);
    }
}
