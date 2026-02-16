<?php

namespace App\Modules\Staff\Livewire;

use App\Modules\Staff\Exports\VacancyExport;
use App\Livewire\Traits\SideModalAction;
use App\Models\StaffSchedule;
use App\Models\Structure;
use App\Services\StructureService;
use App\Traits\NestedStructureTrait;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
            'structure' => fn ($q) => $q->withRecursive('parent', false),
        ])
            ->withCount([
                'personnels as filled_count' => fn ($q) => $q->whereNull('leave_work_date'),
            ])
            ->when(! empty($this->structure), fn ($q) => $q->whereIn('structure_id', $this->structure))
            ->when(empty($this->structure), fn ($q) => $q->whereIn('structure_id', $this->accessibleStructureIds))

            ->when($this->selectedPage == 'vacancies', function ($query) {
                $query->where('vacant', '>', 0)
                    ->whereHas('structure', fn ($qq) => $qq->whereNotNull('parent_id'));
            })
            ->orderBy('structure_id')
            ->get();

        $result->each(function ($row) {
            $row->filled = (int) ($row->filled_count ?? $row->filled ?? 0);
            $row->vacant = max(0, (int) ($row->total ?? 0) - $row->filled);
        });

        if ($type === 'normal') {
            return $this->selectedPage === 'all'
                ? $this->buildStructureGroups($result)
                : $result;
        }

        return $result->toArray();
    }

    protected function buildStructureGroups($rows)
    {
        return $rows
            ->groupBy('structure_id')
            ->map(function ($groupRows) {
                $first = $groupRows->first();
                $structure = $first?->structure;

                return [
                    'title' => $this->resolveStructureTitle($structure),
                    'structure_id' => $first?->structure_id,
                    'has_parent' => ! empty($structure?->parent_id),
                    'total_sum' => $groupRows->sum('total'),
                    'total_filled' => $groupRows->sum('filled'),
                    'total_vacant' => $groupRows->sum('vacant'),
                    'items' => $groupRows,
                ];
            })
            ->values();
    }

    protected function resolveStructureTitle(?Structure $structure): string
    {
        if (! $structure) {
            return '';
        }

        $cacheKey = (int) $structure->id;

        if (array_key_exists($cacheKey, $this->structureTitleCache)) {
            return $this->structureTitleCache[$cacheKey];
        }

        return $this->structureTitleCache[$cacheKey] = (string) $structure->name_with_parent;
    }

    public function render()
    {
        $staffs = $this->returnData();
        
        return view('staff::livewire.staff-schedule.staffs', compact('staffs'));
    }
}
