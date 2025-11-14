<?php

namespace App\Livewire\StaffSchedule;

use App\Exports\VacancyExport;
use App\Livewire\Traits\SideModalAction;
use App\Models\StaffSchedule;
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
        $report = $this->returnData(type: 'excel');
        $name = Carbon::now()->format('d.m.Y H:i');

        return Excel::download(new VacancyExport($report), "vakansiyalar-{$name}.xlsx");
    }

    public function showPage($page)
    {
        $this->selectedPage = $page;
    }

    #[On('selectStructure')]
    public function selectStructure($id)
    {
        $this->structure = $this->getNestedStructure($id);
        $this->resetPage();
    }

    public function setDeleteStaff($staffId)
    {
        $this->dispatch('setDeleteStaff', $staffId);
    }

    public function mount(StructureService $structureService)
    {
        $this->authorize('show-staff');
        $this->selectedPage = request()->query('selectedPage', 'all');
        $this->accessibleStructureIds = $structureService->getAccessibleStructures();
    }

    protected function returnData($type = 'normal')
    {
        $result = StaffSchedule::with([
            'position',
            'structure' => fn ($q) => $q->withRecursive('parent', false),
        ])
            ->when(! empty($this->structure), fn ($q) => $q->whereIn('structure_id', $this->structure))
            ->when(empty($this->structure), fn ($q) => $q->whereIn('structure_id', $this->accessibleStructureIds))

            ->when($this->selectedPage == 'vacancies', function ($query) {
                $query->where('vacant', '>', 0)
                    ->whereHas('structure', fn ($qq) => $qq->whereNotNull('parent_id'));
            })
            ->orderBy('structure_id')
            ->get();

        if ($type === 'normal') {
            return $this->selectedPage === 'all'
                ? $result->groupBy('structure.name_with_parent')
                : $result;
        }

        return $result->toArray();
    }

    public function render()
    {
        $staffs = $this->returnData();
        
        return view('livewire.staff-schedule.staffs', compact('staffs'));
    }
}
