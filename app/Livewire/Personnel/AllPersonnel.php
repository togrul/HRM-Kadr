<?php

namespace App\Livewire\Personnel;

use App\Exports\PersonnelExport;
use Carbon\Carbon;
use Livewire\Component;
use App\Models\Personnel;
use App\Models\Structure;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Livewire\Traits\SideModalAction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;

class AllPersonnel extends Component
{
    use WithPagination,SideModalAction,AuthorizesRequests;

    #[Url]
    public $status;

    public array $filters;

    // #[Url]
    public $structure;

    protected function queryString()
    {
        return [
            'structure' => [
                'compact' => ',',
            ],
        ];
    }

    protected $listeners = ['personnelAdded' => '$refresh','selectStructure','filterSelected','personnelWasDeleted' => '$refresh'];

    public function exportExcel()
    {
         $report['data'] = $this->returnData(type:"excel");
         $report['filter'] = $this->filters;
         $name = Carbon::now()->format('d.m.Y H:i');
         
         return Excel::download( new PersonnelExport( $report ), "personnel-{$name}.xlsx"); 
    }


    public function filterSelected(array $filter)
    {
        $this->filters = $filter;
        $this->reset(['structure']);
    }

    public function setDeletePersonnel($personnelId)
    {
        $this->dispatch('setDeletePersonnel',$personnelId);
    }

    public function restoreData($id)
    {
        $personnel = Personnel::withTrashed()->where('tabel_no',$id)->first();
        $personnel->restore();
        $personnel->update([
            'deleted_by' => null
        ]);
        $this->dispatch('personnelAdded',__('Personnel was updated successfully!'));
    }

    public function forceDeleteData($id)
    {
        $model = Personnel::withTrashed()->where('tabel_no',$id)->first();
        $model->forceDelete();
        $this->dispatch('personnelWasDeleted' , __('Personnel was deleted!'));
    }

    public function selectStructure($id)
    {
        $structureModel = Structure::with('subs')->find($id);
        if ($structureModel) {
            $this->structure = $structureModel->getAllNestedIds();
        }
    }
  
    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function resetSelectedFilter()
    {
        $this->filters = [];
        $this->resetPage();
        $this->fillFilter();
        $this->dispatch('filterResetted');
    }

    public function fillFilter()
    {
        $this->status = request()->query('status')
                    ? request()->query('status')
                    : 'current';
    }

    public function mount()
    {
        $this->fillFilter();
    }

    protected function returnData($type = "normal")
    {
        $result = Personnel::with([
            'nationality',
            'previousNationality',
            'idDocuments',
            'educationDegree',
            'education',
            'latestRank.rank',
            'awards',
            'punishments',
            'structure',
            'position',
            'creator',
            'deletedBy'
        ])
            ->when(!empty($this->structure),function($q) {
                $q->whereIn('structure_id', $this->structure);
            })
            ->when($this->status == 'current',function($q)
            {
                return $q->whereNull('leave_work_date');
            })
            ->when($this->status == 'leaves',function($q)
            {
                return $q->whereNotNull('leave_work_date');
            })
            ->when($this->status == 'deleted',function($q)
            {
                $q->onlyTrashed();
            })
            ->filter($this->filters ?? [])
            ->orderBy('position_id')
            ->orderBy('structure_id');

        return $type == "normal" 
            ? $result->paginate(10)->withQueryString()
            : $result->get()->toArray();
    }

    public function render()
    {
        $personnels = $this->returnData();

        return view('livewire.personnel.all-personnel',compact('personnels'));
    }
}
