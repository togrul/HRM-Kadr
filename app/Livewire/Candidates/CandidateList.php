<?php

namespace App\Livewire\Candidates;

use App\Exports\CandidateExport;
use App\Livewire\Traits\SideModalAction;
use App\Models\AppealStatus;
use App\Models\Candidate;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class CandidateList extends Component
{
    use WithPagination,SideModalAction,AuthorizesRequests;

    #[Url]
    public $status;

    protected $listeners = ['candidateAdded' => '$refresh','filterSelected','candidateWasDeleted' => '$refresh'];

    public function exportExcel()
    {
        $report = $this->returnData(type:"excel");
        $name = Carbon::now()->format('d.m.Y H:i');

        return Excel::download( new CandidateExport( $report ), "candidate-{$name}.xlsx");
    }
    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function setDeleteCandidate($candidateId)
    {
        $this->dispatch('setDeleteCandidate',$candidateId);
    }

    public function restoreData($id)
    {
        $candidate = Candidate::withTrashed()->where('id',$id)->first();
        $candidate->restore();
        $candidate->update([
            'deleted_by' => null
        ]);
        $this->dispatch('candidateAdded',__('Candidate was updated successfully!'));
    }

    public function forceDeleteData($id)
    {
        $model = Candidate::withTrashed()->where('id',$id)->first();
        $model->forceDelete();
        $this->dispatch('candidateWasDeleted' , __('Candidate was deleted!'));
    }

    protected function returnData($type = "normal")
    {
        $result = Candidate::with(['structure', 'status','creator','personDidDelete'])
            ->when(is_int($this->status),function($q)
            {
                return $q->where('status_id',$this->status);
            })
            ->when($this->status == 'deleted',function($q)
            {
                $q->onlyTrashed();
            })
            ->orderByDesc('appeal_date');

        return $type == "normal"
            ? $result->paginate(15)->withQueryString()
            : $result->get()->toArray();
    }

    public function mount()
    {
        $this->status = request()->query('status')
            ? request()->query('status')
            : 'all';
    }

    public function render()
    {
        $_appeal_statuses = AppealStatus::where('locale',config('app.locale'))->get();

        $_candidates = $this->returnData();

        return view('livewire.candidates.candidate-list',compact('_appeal_statuses','_candidates'));
    }
}
