<?php

namespace App\Modules\Staff\Livewire;

use App\Modules\Staff\Exports\VacancyExport;
use App\Livewire\Traits\SideModalAction;
use App\Models\Personnel;
use App\Models\StaffSchedule;
use App\Models\Structure;
use App\Services\StructureService;
use App\Traits\NestedStructureTrait;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[On(['staffAdded', 'staffWasDeleted'])]
class Staffs extends Component
{
    use AuthorizesRequests;
    use NestedStructureTrait;
    use SideModalAction;
    use WithPagination;

    public $structure;

    #[Url]
    public $selectedPage;

    #[Locked]
    public array $accessibleStructureIds = [];

    protected array $structureTitleCache = [];
    protected ?array $structureMap = null;

    protected function queryString()
    {
        return [
            'structure' => [
                'compact' => ',',
            ],
        ];
    }

    public function exportExcel()
    {
        $this->authorize('export', StaffSchedule::class);

        $report = $this->returnData(type: 'excel');
        $name = Carbon::now()->format('d.m.Y H:i');

        return Excel::download(new VacancyExport($report), "vakansiyalar-{$name}.xlsx");
    }

    public function showPage($page)
    {
        $this->selectedPage = $page;
    }

    #[On('selectStructure')]
    public function selectStructure(mixed $payload = null): void
    {
        $id = $this->resolveSelectStructureId($payload);

        if ($id === null) {
            return;
        }

        $this->structure = $this->getNestedStructure($id);
        $this->resetPage();
    }

    protected function resolveSelectStructureId(mixed $payload): ?int
    {
        if (is_array($payload)) {
            if (array_key_exists('id', $payload)) {
                $payload = $payload['id'];
            } elseif (! empty($payload) && array_is_list($payload)) {
                $payload = $payload[0];
            }
        }

        if (! is_numeric($payload)) {
            return null;
        }

        $id = (int) $payload;

        return $id > 0 ? $id : null;
    }

    public function setDeleteStaff($staffId)
    {
        $this->dispatch('setDeleteStaff', $staffId);
    }

    public function mount(StructureService $structureService)
    {
        $this->authorize('viewAny', StaffSchedule::class);
        $this->selectedPage = request()->query('selectedPage', 'all');
        $this->accessibleStructureIds = $structureService->getAccessibleStructures();
    }

    protected function returnData($type = 'normal')
    {
        $result = StaffSchedule::with([
            'position',
            'structure:id,parent_id,name',
        ])
            ->when(! empty($this->structure), fn ($q) => $q->whereIn('structure_id', $this->structure))
            ->when(empty($this->structure), fn ($q) => $q->whereIn('structure_id', $this->accessibleStructureIds))
            ->orderBy('structure_id')
            ->get();

        $this->hydrateFilledAndVacant($result);

        if ($this->selectedPage === 'vacancies') {
            $result = $result
                ->filter(fn ($row) => (int) ($row->vacant ?? 0) > 0 && ! empty($row->structure?->parent_id))
                ->values();
        }

        if ($type === 'normal') {
            return $this->selectedPage === 'all'
                ? $this->buildStructureGroups($result)
                : $result;
        }

        return $result->toArray();
    }

    protected function hydrateFilledAndVacant(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $structureIds = $rows
            ->pluck('structure_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $nestedIdsByStructure = $this->buildNestedIdsByStructure($structureIds);
        $relevantStructureIds = collect($nestedIdsByStructure)
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $activeByStructure = Personnel::query()
            ->active()
            ->whereIn('structure_id', $relevantStructureIds)
            ->select('structure_id', DB::raw('count(*) as aggregate'))
            ->groupBy('structure_id')
            ->pluck('aggregate', 'structure_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $activeByStructurePosition = Personnel::query()
            ->active()
            ->whereIn('structure_id', $relevantStructureIds)
            ->whereNotNull('position_id')
            ->select('structure_id', 'position_id', DB::raw('count(*) as aggregate'))
            ->groupBy('structure_id', 'position_id')
            ->get()
            ->reduce(function (array $carry, $row) {
                $structureId = (int) $row->structure_id;
                $positionId = (int) $row->position_id;
                $carry[$structureId][$positionId] = (int) $row->aggregate;

                return $carry;
            }, []);

        $rows->each(function ($row) use ($activeByStructure, $activeByStructurePosition, $nestedIdsByStructure) {
            $structureId = (int) ($row->structure_id ?? 0);
            $positionId = (int) ($row->position_id ?? 0);
            $hasParent = ! empty($row->structure?->parent_id);

            if ($positionId > 0 && $hasParent) {
                $filled = (int) ($activeByStructurePosition[$structureId][$positionId] ?? 0);
            } else {
                $filled = 0;
                foreach ($nestedIdsByStructure[$structureId] ?? [$structureId] as $nestedId) {
                    $filled += (int) ($activeByStructure[(int) $nestedId] ?? 0);
                }
            }

            $row->filled = $filled;
            $row->vacant = max(0, (int) ($row->total ?? 0) - $filled);
        });
    }

    protected function buildNestedIdsByStructure(array $structureIds): array
    {
        if (empty($structureIds)) {
            return [];
        }

        $childrenByParent = [];
        foreach ($this->resolveStructureMap() as $id => $meta) {
            $parentId = (int) ($meta['parent_id'] ?? 0);
            $childrenByParent[$parentId][] = (int) $id;
        }

        $memo = [];
        $collectNestedIds = function (int $id) use (&$collectNestedIds, &$memo, $childrenByParent): array {
            if (isset($memo[$id])) {
                return $memo[$id];
            }

            $ids = [$id];
            foreach ($childrenByParent[$id] ?? [] as $childId) {
                $ids = array_merge($ids, $collectNestedIds((int) $childId));
            }

            return $memo[$id] = array_values(array_unique($ids));
        };

        $nestedIdsByStructure = [];
        foreach ($structureIds as $structureId) {
            $structureId = (int) $structureId;
            if ($structureId <= 0) {
                continue;
            }

            $nestedIdsByStructure[$structureId] = $collectNestedIds($structureId);
        }

        return $nestedIdsByStructure;
    }

    protected function buildStructureGroups($rows)
    {
        return $rows
            ->groupBy('structure_id')
            ->map(function ($groupRows) {
                $first = $groupRows->first();
                $structure = $first?->structure;
                $structureId = (int) ($first?->structure_id ?? 0);
                $structureMap = $this->resolveStructureMap();
                $parentId = $structure?->parent_id ?? ($structureMap[$structureId]['parent_id'] ?? null);

                return [
                    'title' => $this->resolveStructureTitle($structureId),
                    'structure_id' => $structureId,
                    'has_parent' => ! empty($parentId),
                    'total_sum' => $groupRows->sum('total'),
                    'total_filled' => $groupRows->sum('filled'),
                    'total_vacant' => $groupRows->sum('vacant'),
                    'items' => $groupRows,
                ];
            })
            ->values();
    }

    protected function resolveStructureTitle(int $structureId): string
    {
        if ($structureId <= 0) {
            return '';
        }

        $cacheKey = $structureId;

        if (array_key_exists($cacheKey, $this->structureTitleCache)) {
            return $this->structureTitleCache[$cacheKey];
        }

        $structureMap = $this->resolveStructureMap();
        $cursor = $structureId;
        $segments = [];

        while ($cursor > 0 && isset($structureMap[$cursor])) {
            $meta = $structureMap[$cursor];

            // Hide only structures whose own parent_id is null (root node).
            if ($meta['parent_id'] !== null) {
                $segments[] = (string) $meta['name'];
            }

            $cursor = (int) ($meta['parent_id'] ?? 0);
        }

        if (empty($segments)) {
            return $this->structureTitleCache[$cacheKey] = '';
        }

        return $this->structureTitleCache[$cacheKey] = implode(' / ', array_reverse($segments));
    }

    protected function resolveStructureMap(): array
    {
        if ($this->structureMap !== null) {
            return $this->structureMap;
        }

        $this->structureMap = Structure::query()
            ->select('id', 'parent_id', 'name')
            ->get()
            ->reduce(function (array $carry, Structure $structure) {
                $carry[(int) $structure->id] = [
                    'parent_id' => $structure->parent_id ? (int) $structure->parent_id : null,
                    'name' => (string) $structure->name,
                ];

                return $carry;
            }, []);

        return $this->structureMap;
    }

    public function render()
    {
        $staffs = $this->returnData();
        
        return view('staff::livewire.staff-schedule.staffs', compact('staffs'));
    }
}
