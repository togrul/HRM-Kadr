<?php

namespace App\Modules\Attendance\Livewire;

use App\Modules\Attendance\Application\Services\AttendanceAuthorizationService;
use App\Modules\Attendance\Application\Services\AttendanceHistoryReadService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class AttendanceHistoryLog extends Component
{
    use WithPagination;

    public string $type = 'all';

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 15;

    public ?int $subjectId = null;

    public ?int $expandedId = null;

    /**
     * @var array<string,int>
     */
    public array $totals = [];

    /**
     * @var array<string,string>
     */
    public array $typeOptions = [];

    public function mount(
        AttendanceAuthorizationService $authorization,
        int $year,
        int $month,
        string $initialType = 'all',
        ?int $initialSubjectId = null
    ): void {
        $authorization->authorize('attendance.history.view');

        $monthStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $this->dateFrom = $monthStart->toDateString();
        $this->dateTo = $monthStart->copy()->endOfMonth()->toDateString();
        $service = app(AttendanceHistoryReadService::class);
        $this->typeOptions = $service->typeOptions();
        $this->type = array_key_exists($initialType, $this->typeOptions) ? $initialType : 'all';
        $this->subjectId = $initialSubjectId;
        $this->refreshTotals($service);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->refreshTotals(app(AttendanceHistoryReadService::class));
    }

    public function updatedType(): void
    {
        $this->resetPage();
        $this->refreshTotals(app(AttendanceHistoryReadService::class));
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
        $this->refreshTotals(app(AttendanceHistoryReadService::class));
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
        $this->refreshTotals(app(AttendanceHistoryReadService::class));
    }

    public function clearSubjectFilter(): void
    {
        $this->subjectId = null;
        $this->resetPage();
        $this->refreshTotals(app(AttendanceHistoryReadService::class));
    }

    public function toggleRow(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function render()
    {
        /** @var AttendanceHistoryReadService $service */
        $service = app(AttendanceHistoryReadService::class);

        $rows = $service->paginateRows(
            type: $this->type,
            search: trim($this->search),
            dateFrom: $this->dateFrom,
            dateTo: $this->dateTo,
            perPage: $this->perPage,
            subjectId: $this->subjectId
        );

        $rows->setCollection(
            $rows->getCollection()->map(function ($activity) use ($service) {
                $type = $service->resolveType($activity);

                return (object) [
                    'id' => (int) $activity->id,
                    'created_at' => $activity->created_at,
                    'type' => $type,
                    'type_label' => $this->typeOptions[$type] ?? __('attendance::history.types.all'),
                    'event' => (string) $activity->event,
                    'event_label' => $service->eventLabel($activity),
                    'subject_label' => $service->subjectLabel($activity),
                    'causer_name' => (string) ($activity->causer?->name ?: $activity->causer?->email ?: __('attendance::history.labels.system')),
                    'changed_keys' => $service->changedKeys($activity),
                    'before' => $service->normalizedPayload($activity, 'before'),
                    'after' => $service->normalizedPayload($activity, 'after'),
                ];
            })
        );

        return view('attendance::livewire.attendance.history-log', [
            'rows' => $rows,
            'totals' => $this->totals,
            'typeOptions' => $this->typeOptions,
        ]);
    }

    private function refreshTotals(AttendanceHistoryReadService $service): void
    {
        $this->totals = $service->totals(
            type: $this->type,
            search: trim($this->search),
            dateFrom: $this->dateFrom,
            dateTo: $this->dateTo,
            subjectId: $this->subjectId
        );
    }
}
