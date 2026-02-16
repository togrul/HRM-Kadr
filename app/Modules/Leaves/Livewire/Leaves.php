<?php

namespace App\Modules\Leaves\Livewire;

use App\Data\LeaveFilterData;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Livewire\Traits\SideModalAction;
use App\Models\Leave;
use App\Models\OrderStatus;
use App\Models\Structure;
use App\Modules\Leaves\Exports\LeaveExport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[On(['leaveAdded', 'filterSelected', 'leaveWasDeleted', 'leaveApproved', 'leaveRejected'])]
class Leaves extends Component
{
    use AuthorizesRequests, DropdownConstructTrait ,SideModalAction, WithPagination;

    public LeaveFilterData $filter;

    #[Locked]
    public LeaveFilterData $search;

    #[Url]
    public $status;

    protected ?array $statsCache = null;
    protected array $structurePathCache = [];

    public function applyFilter(?array $payload = null): void
    {
        if ($payload !== null) {
            $this->filter->fillFromArray($payload);
        }

        $this->search = LeaveFilterData::fromArray($this->filter->toArray());
        $this->resetPage();
        $this->statsCache = null;
    }

    public function resetFilter(): void
    {
        $this->filter = LeaveFilterData::make();
        $this->applyFilter();
    }

    public function searchFilter(): void
    {
        $this->applyFilter();
    }

    public function setStatus($newStatus): void
    {
        $this->authorize('viewAny', \App\Models\Leave::class);
        $this->status = $newStatus;
        $this->resetPage();
        $this->statsCache = null;
    }

    public function exportExcel()
    {
        $this->authorize('export', \App\Models\Leave::class);

        $rows = $this->returnData('cursor');
        $name = now()->format('d.m.Y H:i');

        return Excel::download(new LeaveExport($rows), "leaves-{$name}.xlsx");
    }

    public function updatedFilter($value, $key): void
    {
        $field = str_starts_with($key, 'filter.') ? substr($key, 7) : $key;

        if (! property_exists($this->filter, $field)) {
            return;
        }

        $this->filter->fillFromArray([$field => $value]);

        if (in_array($field, ['starts_at', 'ends_at'], true) && ($value === '' || $value === null)) {
            $this->filter->fillFromArray([$field => null]);
        }

        if ($field === 'starts_at') {
            $endsAt = $this->filter->ends_at;
            if ($endsAt && $this->filter->starts_at && $this->filter->starts_at > $endsAt) {
                $this->filter->ends_at = null;
            }
        }

        if ($field === 'ends_at') {
            $startsAt = $this->filter->starts_at;
            if ($startsAt && $this->filter->ends_at && $this->filter->ends_at < $startsAt) {
                $this->filter->starts_at = null;
            }
        }
    }

    public function setDeleteLeave($leaveId)
    {
        $this->dispatch('setDeleteLeave', $leaveId);
    }

    public function forceDeleteData($id)
    {
        $model = Leave::withTrashed()->find($id);
        $this->authorize('delete', $model);
        $model->forceDelete();
        $this->dispatch('leaveWasDeleted', __('Leave was deleted!'));
    }

    public function restoreData($id)
    {
        $model = Leave::withTrashed()->find($id);
        $this->authorize('restore', $model);
        $model->restore();
        $this->dispatch('leaveAdded', __('Leave was updated successfully!'));
    }

    public function getTableHeaders(): array
    {
        return [
            '#',
            __('Fullname'),
            __('Type'),
            __('Dates'),
            __('Reason'),
            __('Status'),
            __('File'),
            'action',
            'action',
            // 'action'
        ];
    }

    #[Computed(cache: true)]
    public function leaveTypes(): array
    {
        $selected = $this->filter->leave_type_id;

        $base = \App\Models\LeaveType::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: '',
            searchTerm: '',
            selectedId: $selected,
            limit: 80
        );
    }

    public function mount(): void
    {
        $this->authorize('viewAny', \App\Models\Leave::class);
        $this->status = request()->query('status', 'all');
        $this->filter = LeaveFilterData::make();
        $this->search = LeaveFilterData::make();
    }

    protected function returnData($type = 'normal')
    {
        $base = Leave::query()
            ->when(is_numeric($this->status), fn ($q) => $q->where('status_id', $this->status))
            ->when($this->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->filter($this->search);

        // Liste (eager load + paginate)
        $result = $base->clone()
            ->with([
                'personnel' => fn ($q) => $q
                    ->withStructureTree()   // burada parent zincirini preload eder
                    ->with([
                        'position:id,name',
                        'latestDisposal' => fn ($q) => $q->select(
                            'personnel_disposals.id',
                            'personnel_disposals.tabel_no',
                            'personnel_disposals.disposal_date',
                            'personnel_disposals.disposal_end_date'
                        ),
                        'currentWork:id,tabel_no,join_date,leave_date,is_current,position',
                    ]),
                'leaveType',
                'status',
                'latestLog.changedBy',
            ])
            ->orderByDesc('created_at');

        return match ($type) {
            'stats' => $this->computeStats($base),
            default => $this->finalizePagination($result, $type),
        };
    }

    protected function computeStats($base): array
    {
        if ($this->statsCache !== null) {
            return $this->statsCache;
        }

        return $this->statsCache = $base->clone()
            ->join('leave_types as t', 't.id', '=', 'leaves.leave_type_id')
            ->select(
                't.name as name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(DATEDIFF(ends_at, starts_at) + 1) as total_days')
            )
            ->groupBy('t.name')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->name => [
                    'total_days' => (int) $row->total_days,
                    'count' => (int) $row->count,
                ],
            ])
            ->toArray();
    }

    protected function finalizePagination($query, $type)
    {
        if ($type === 'cursor') {
            return $query->cursor();
        }

        $paginated = $query->paginate(15)->withQueryString();

        return $this->decoratePagination($paginated);
    }

    protected function decoratePagination(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $start = ($paginated->currentPage() - 1) * $paginated->perPage();

        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (Leave $leave, int $index) use ($start) {
                $leave->row_no = $start + $index + 1;
                $leave->personnel_structure_path = $this->resolveStructurePath($leave->personnel?->structure);

                return $leave;
            })
        );

        return $paginated;
    }

    protected function resolveStructurePath(?Structure $structure): string
    {
        if (! $structure) {
            return '';
        }

        $cacheKey = (int) $structure->id;

        if (array_key_exists($cacheKey, $this->structurePathCache)) {
            return $this->structurePathCache[$cacheKey];
        }

        $segments = [];
        $cursor = $structure;

        while ($cursor) {
            if (is_null($cursor->parent_id)) {
                break;
            }

            $segments[] = (string) $cursor->name;

            if (! $cursor->relationLoaded('parent')) {
                break;
            }

            $cursor = $cursor->parent;
        }

        return $this->structurePathCache[$cacheKey] = implode(' ', array_reverse($segments));
    }

    public function render()
    {
        $permits = $this->returnData();
        $_appeal_statuses = $this->appealStatuses();

        $stats = $this->returnData('stats');

        return view('leaves::livewire.leaves.leaves', compact('permits', '_appeal_statuses', 'stats'));
    }

    public function filterSelected(array $filters): void
    {
        $this->filter = LeaveFilterData::fromArray($filters);
        $this->applyFilter();
    }

    #[Computed(cache: true, persist: true)]
    public function appealStatuses()
    {
        $locale = config('app.locale');

        return Cache::remember(
            "leaves:statuses:{$locale}",
            now()->addMinutes(10),
            fn () => OrderStatus::query()
                ->where('locale', $locale)
                ->get()
        );
    }
}
