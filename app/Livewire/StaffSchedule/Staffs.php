<?php

namespace App\Livewire\StaffSchedule;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Structure;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Models\StaffSchedule;
use App\Exports\VacancyExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Livewire\Traits\SideModalAction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Staffs extends Component
{
    use WithPagination,SideModalAction,AuthorizesRequests;

    public $structure;

    #[Url]
    public $selectedPage;

    protected $listeners = ['staffAdded' => '$refresh','selectStructure','staffWasDeleted' => '$refresh'];

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
         $report = $this->returnData(type:"excel");
         $name = Carbon::now()->format('d.m.Y H:i');
         
         return Excel::download( new VacancyExport( $report ), "vakansiyalar-{$name}.xlsx"); 
    }

    public function showPage($page)
    {
        $this->selectedPage = $page;
    }

    public function selectStructure($id)
    {
        $structureModel = Structure::with('subs')->find($id);
        if ($structureModel) {
            $this->structure = $structureModel->getAllNestedIds();
        }
        $this->resetPage();
    }

    public function setDeleteStaff($staffId)
    {
        $this->dispatch('setDeleteStaff',$staffId);
    }

    public function mount()
    {
        $this->selectedPage = request()->query('selectedPage')
                        ? request()->query('selectedPage')
                        : 'all';
    }

    protected function returnData($type = "normal")
    {
        $result = StaffSchedule::with(['structure','position'])
                ->when(!empty($this->structure),function($q) {
                    $q->whereIn('structure_id', $this->structure);
                })
                ->when($this->selectedPage == 'vacancies',function($query){
                    $query->where('vacant','>',0)->whereHas('structure',function($qq){
                        $qq->whereNotNull('parent_id');
                    });
                })
                ->orderBy('structure_id') 
                ->get();

        return $type == 'normal' 
                ? ($this->selectedPage == 'all' ? $result->groupBy('structure.name') : $result)
                : $result->toArray();
    }
    
    public function render()
    {
        $staffs = $this->returnData();

        return view('livewire.staff-schedule.staffs',compact('staffs'));
    }
}
