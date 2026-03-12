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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
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
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first() ?: __('attendance::overtime.messages.validation_failed'));

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('attendance::overtime.messages.approved'));
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
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first() ?: __('attendance::overtime.messages.validation_failed'));

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('attendance::overtime.messages.rejected'));
    }

    public function createManualRequest(AttendanceOvertimeRequestService $service): void
    {
        if (! $this->canCreate) {
            abort(403);
        }

        try {
            $service->create($this->manualRequest, (int) Auth::id());
        } catch (ValidationException $exception) {
            $this->dispatch('notify', type: 'error', message: collect($exception->errors())->flatten()->first() ?: __('attendance::overtime.messages.validation_failed'));

            return;
        }

        $this->dispatch('notify', type: 'success', message: __('attendance::overtime.messages.created'));
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

    #[Computed]
    public function personnelResults(): Collection
    {
        if (! $this->canCreate || mb_strlen(trim($this->personnelSearch)) < 2) {
            return collect();
        }

        /** @var StructurePathService $structurePathService */
        $structurePathService = app(StructurePathService::class);

        return Personnel::query()
            ->where('is_pending', 0)
            ->whereNull('leave_work_date')
            ->when(
                $this->currentStructureIds() !== [],
                fn ($query) => $query->whereIn('structure_id', $this->currentStructureIds())
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

    #[Computed]
    public function items(): LengthAwarePaginator
    {
        /** @var StructurePathService $structurePathService */
        $structurePathService = app(StructurePathService::class);
        $staleAfterDays = max(1, (int) config('attendance.processing.overtime_request_stale_days', 3));

        $items = AttendanceOvertimeRequest::query()
            ->with([
                'personnel:tabel_no,surname,name,patronymic,structure_id',
                'requestedBy:id,name',
                'approvedBy:id,name',
            ])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->fromDate !== '', fn ($query) => $query->whereDate('date', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($query) => $query->whereDate('date', '<=', $this->toDate))
            ->when($this->currentStructureIds() !== [], function ($query): void {
                $structureIds = $this->currentStructureIds();

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
                        'manual' => __('attendance::overtime.badges.manual_request'),
                        'auto_manual_entry' => __('attendance::overtime.badges.from_manual_entry'),
                        default => __('attendance::overtime.badges.from_ledger'),
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
                        ? __('attendance::overtime.badges.manual')
                        : __('attendance::overtime.badges.auto_generated')
                );
                $item->setAttribute(
                    'origin_badge_mode',
                    (string) $item->source === 'manual' ? 'purple' : 'blue'
                );

                return $item;
            })
        );

        return $items;
    }

    #[Computed]
    public function selectedStructureLabel(): ?string
    {
        /** @var AttendanceStructureScopeReadService $structureScopeRead */
        $structureScopeRead = app(AttendanceStructureScopeReadService::class);

        return $structureScopeRead->label($this->selectedStructureId);
    }

    #[Computed]
    public function activeFilters(): array
    {
        $activeFilters = [];

        if ($this->status !== 'all') {
            $activeFilters[] = [
                'label' => __('attendance::overtime.filters.status'),
                'value' => __('attendance::overtime.statuses.'.$this->status),
                'mode' => 'sky',
            ];
        }

        if ($this->fromDate !== '' || $this->toDate !== '') {
            $range = trim(($this->fromDate ?: '...').' - '.($this->toDate ?: '...'));
            $activeFilters[] = [
                'label' => __('attendance::overtime.filters.period'),
                'value' => $range,
                'mode' => 'secondary',
            ];
        }

        if ($this->selectedStructureLabel) {
            $activeFilters[] = [
                'label' => __('attendance::overtime.filters.structure'),
                'value' => $this->selectedStructureLabel,
                'mode' => 'purple',
            ];
        }

        return $activeFilters;
    }

    #[Computed]
    public function emptyStateTitle(): string
    {
        return __('attendance::overtime.empty.title');
    }

    #[Computed]
    public function emptyStateDescription(): string
    {
        $emptyStateDescription = __('attendance::overtime.empty.description_filtered');

        if ($this->activeFilters === []) {
            $emptyStateDescription = __('attendance::overtime.empty.description_default');
        }

        return $emptyStateDescription;
    }

    public function render()
    {
        return view('attendance::livewire.attendance.overtime-board');
    }

    /**
     * @return array<int,int>
     */
    private function currentStructureIds(): array
    {
        return $this->selectedStructureId
            ? $this->getNestedStructure($this->selectedStructureId)
            : [];
    }
}
