<?php

namespace App\Livewire\Candidates;

use App\Exports\CandidateExport;
use App\Livewire\Traits\SideModalAction;
use App\Models\AppealStatus;
use App\Models\Candidate;
use App\Services\StructureService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[On(['candidateAdded', 'filterSelected', 'candidateWasDeleted'])]
class CandidateList extends Component
{
    use AuthorizesRequests, SideModalAction, WithPagination;

    public array $filter = [];

    public array $search = [];

    #[Url]
    public $status;

    public function exportExcel()
    {
        $report = $this->returnData(type: 'excel');
        $name = Carbon::now()->format('d.m.Y H:i');

        return Excel::download(new CandidateExport($report), "candidate-$name.xlsx");
    }

    public function setStatus($newStatus): void
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function setDeleteCandidate($candidateId): void
    {
        $this->dispatch('setDeleteCandidate', $candidateId);
    }

    public function searchFilter(): void
    {
        $this->applyFilter();
    }

    public function getTableHeaders(): array
    {
        return [
            __('#'),
            __('Fullname'),
            __('Structure'),
            __('Tests'),
            __('Dates'),
            __('Status'),
            'action',
            'action',
        ];
    }

    public function applyFilter(array $filter = []): void
    {
        $this->search = $filter ?: $this->filter;
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->filter = [];
        $this->applyFilter([]);
    }

    public function restoreData($id): void
    {
        $candidate = Candidate::withTrashed()->findOrFail($id);
        $candidate->restore();
        $candidate->update([
            'deleted_by' => null,
        ]);
        $this->dispatch('candidateAdded', __('Candidate was updated successfully!'));
    }

    public function forceDeleteData($id): void
    {
        $model = Candidate::withTrashed()->findOrFail($id);
        $model->forceDelete();
        $this->dispatch('candidateWasDeleted', __('Candidate was deleted!'));
    }

    protected function returnData($type = 'normal')
    {
        $result = Candidate::with(['structure', 'status', 'creator', 'personDidDelete'])
            ->whereIn('structure_id', resolve(StructureService::class)->getAccessibleStructures())
            ->when(is_numeric($this->status), fn($q) => $q->where('status_id', $this->status))
            ->when($this->status === 'deleted', fn($q) => $q->onlyTrashed())
            ->filter($this->search ?? [])
            ->orderByDesc('appeal_date');

        return $type == 'normal'
            ? $result->paginate(15)->withQueryString()
            : $result->cursor();
    }

    public function mount(): void
    {
        $this->authorize('show-candidates');
        $this->status = request()->query('status', 'all');
    }

    public function render()
    {
        $_appeal_statuses = AppealStatus::where('locale', config('app.locale'))->get();

        $_candidates = $this->returnData();

        return view('livewire.candidates.candidate-list', compact('_appeal_statuses', '_candidates'));
    }
}
