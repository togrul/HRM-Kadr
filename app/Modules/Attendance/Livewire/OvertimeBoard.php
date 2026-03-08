<?php

namespace App\Modules\Attendance\Livewire;

use App\Models\AttendanceOvertimeRequest;
use App\Models\Personnel;
use App\Services\StructurePathService;
use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceOvertimeApprovalService;
use App\Modules\Attendance\Application\Services\AttendanceOvertimeRequestService;
use App\Modules\Attendance\Application\Services\AttendanceStructureScopeReadService;
use App\Traits\NestedStructureTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class OvertimeBoard extends Component
{
    use WithPagination;
    use NestedStructureTrait;

    public string $status = 'pending';

    public string $fromDate = '';

    public string $toDate = '';

    public array $approvedMinutes = [];

    public int $perPage = 20;

    public bool $canApprove = false;

    public bool $canCreate = false;

    public ?int $selectedStructureId = null;

    public string $personnelSearch = '';

    public ?array $selectedPersonnel = null;

    /**
     * @var array{tabel_no:string,date:string,requested_minutes:int,reason:string}
     */
    public array $manualRequest = [
        'tabel_no' => '',
        'date' => '',
        'requested_minutes' => 0,
        'reason' => '',
    ];

    public function mount(int $year, int $month, AttendanceAuthorizationService $authorization): void
    {
        $from = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();
        $this->fromDate = $from->toDateString();
        $this->toDate = $to->toDateString();
        $this->canApprove = $authorization->can('attendance.overtime.approve');
        $this->canCreate = $this->canApprove || $authorization->can('attendance.manual.write');
        $this->manualRequest['date'] = now()->toDateString();

        if (! $authorization->can('attendance.overtime.view')) {
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

    public function updatedSelectedStructureId(): void
    {
        $this->resetPage();
        $this->personnelSearch = '';
        $this->selectedPersonnel = null;
        $this->manualRequest['tabel_no'] = '';
    }

    public function selectPersonnel(string $tabelNo, string $fullname): void
    {
        $this->selectedPersonnel = [
            'tabel_no' => $tabelNo,
            'fullname' => stripslashes($fullname),
        ];
        $this->manualRequest['tabel_no'] = $tabelNo;
        $this->personnelSearch = '';
    }

    public function clearPersonnel(): void
    {
        $this->selectedPersonnel = null;
        $this->manualRequest['tabel_no'] = '';
        $this->personnelSearch = '';
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

    public function createManualRequest(AttendanceOvertimeRequestService $service): void
    {
        if (! $this->canCreate) {
            abort(403);
        }

        try {
            $service->create($this->manualRequest, (int) Auth::id());
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first() ?: __('Validation failed.'));

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('Manual overtime request created.'));
        $this->manualRequest = [
            'tabel_no' => '',
            'date' => now()->toDateString(),
            'requested_minutes' => 0,
            'reason' => '',
        ];
        $this->selectedPersonnel = null;
        $this->personnelSearch = '';
        $this->resetPage();
    }

    public function render()
    {
        $structureIds = $this->selectedStructureId
            ? $this->getNestedStructure($this->selectedStructureId)
            : [];
        /** @var AttendanceStructureScopeReadService $structureScopeRead */
        $structureScopeRead = app(AttendanceStructureScopeReadService::class);
        /** @var StructurePathService $structurePathService */
        $structurePathService = app(StructurePathService::class);
        $staleAfterDays = max(1, (int) config('attendance.processing.overtime_request_stale_days', 3));

        $personnelResults = collect();
        if ($this->canCreate && mb_strlen(trim($this->personnelSearch)) >= 2) {
            $personnelResults = Personnel::query()
                ->where('is_pending', 0)
                ->whereNull('leave_work_date')
                ->when(
                    $structureIds !== [],
                    fn ($query) => $query->whereIn('structure_id', $structureIds)
                )
                ->where(function ($query): void {
                    $term = trim($this->personnelSearch);
                    $query->where('name', 'like', '%'.$term.'%')
                        ->orWhere('surname', 'like', '%'.$term.'%')
                        ->orWhere('patronymic', 'like', '%'.$term.'%')
                        ->orWhere('tabel_no', 'like', '%'.$term.'%');
                })
                ->orderBy('surname')
                ->limit(15)
                ->get()
                ->map(function (Personnel $personnel) use ($structurePathService) {
                    $personnel->setAttribute(
                        'structure_path',
                        $structurePathService->resolve((int) $personnel->structure_id)
                    );
                    $personnel->setAttribute(
                        'fullname',
                        trim($personnel->surname.' '.$personnel->name.' '.$personnel->patronymic)
                    );

                    return $personnel;
                });
        }

        $items = AttendanceOvertimeRequest::query()
            ->with([
                'personnel:tabel_no,surname,name,patronymic,structure_id',
                'requestedBy:id,name',
                'approvedBy:id,name',
            ])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->fromDate !== '', fn ($query) => $query->whereDate('date', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($query) => $query->whereDate('date', '<=', $this->toDate))
            ->when($structureIds !== [], function ($query) use ($structureIds): void {
                $query->whereHas('personnel', fn ($personnelQuery) => $personnelQuery->whereIn('structure_id', $structureIds));
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $items->setCollection(
            $items->getCollection()->map(function (AttendanceOvertimeRequest $item) use ($structurePathService, $staleAfterDays) {
                if ($item->personnel) {
                    $item->personnel->setAttribute(
                        'structure_path',
                        $structurePathService->resolve((int) $item->personnel->structure_id)
                    );
                }

                $item->setAttribute(
                    'request_age_days',
                    (int) $item->created_at?->startOfDay()->diffInDays(now()->startOfDay())
                );
                $item->setAttribute(
                    'is_stale_pending',
                    $item->status === 'pending'
                    && (int) ($item->request_age_days ?? 0) >= $staleAfterDays
                );
                $item->setAttribute(
                    'source_label',
                    match ((string) $item->source) {
                        'manual' => __('Manual request'),
                        'auto_manual_entry' => __('From manual entry'),
                        default => __('From ledger'),
                    }
                );
                $item->setAttribute(
                    'source_badge_mode',
                    match ((string) $item->source) {
                        'manual' => 'sky',
                        'auto_manual_entry' => 'green',
                        default => 'secondary',
                    }
                );
                $item->setAttribute(
                    'origin_label',
                    (string) $item->source === 'manual'
                        ? __('Manual')
                        : __('Auto-generated')
                );
                $item->setAttribute(
                    'origin_badge_mode',
                    (string) $item->source === 'manual' ? 'purple' : 'blue'
                );

                return $item;
            })
        );

        return view('attendance::livewire.attendance.overtime-board', [
            'items' => $items,
            'selectedStructureLabel' => $structureScopeRead->label($this->selectedStructureId),
            'personnelResults' => $personnelResults,
            'staleAfterDays' => $staleAfterDays,
        ]);
    }
}
