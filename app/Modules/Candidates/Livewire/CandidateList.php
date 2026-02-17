<?php

namespace App\Modules\Candidates\Livewire;

use App\Concerns\LoadsAppealStatuses;
use App\Modules\Candidates\Exports\CandidateExport;
use App\Livewire\Traits\SideModalAction;
use App\Models\Candidate;
use App\Services\StructureService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[On(['candidateAdded', 'filterSelected', 'candidateWasDeleted'])]
class CandidateList extends Component
{
    use AuthorizesRequests;
    use LoadsAppealStatuses;
    use SideModalAction;
    use WithPagination;

    public array $filter = [];

    public array $search = [];

    #[Url]
    public $status;

    protected array $accessibleStructureIds = [];

    protected const TEST_SCORE_COLOR_MAP = [
        0 => 'slate',
        1 => 'gray',
        2 => 'rose',
        3 => 'orange',
        4 => 'blue',
        5 => 'green',
    ];

    public function exportExcel()
    {
        $this->authorize('export', Candidate::class);

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
            ->when(
                ! empty($this->accessibleStructureIds),
                fn ($query) => $query->whereIn('structure_id', $this->accessibleStructureIds)
            )
            ->when(is_numeric($this->status), fn ($q) => $q->where('status_id', $this->status))
            ->when($this->status === 'deleted', fn ($q) => $q->onlyTrashed())
            ->filter($this->search ?? [])
            ->orderByDesc('appeal_date');

        return $type == 'normal'
            ? $this->decoratePagination($result->paginate(15)->withQueryString())
            : $result->cursor();
    }

    protected function decoratePagination(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $start = ($paginated->currentPage() - 1) * $paginated->perPage();

        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (Candidate $candidate, int $index) use ($start) {
                $candidate->row_no = $start + $index + 1;
                $candidate->knowledge_test_color = self::TEST_SCORE_COLOR_MAP[(int) ($candidate->knowledge_test ?? 0)] ?? 'slate';
                $candidate->physical_fitness_exam_color = self::TEST_SCORE_COLOR_MAP[(int) ($candidate->physical_fitness_exam ?? 0)] ?? 'slate';

                return $candidate;
            })
        );

        return $paginated;
    }

    public function mount(StructureService $structureService): void
    {
        $this->authorize('viewAny', Candidate::class);
        $this->status = request()->query('status', 'all');
        $this->accessibleStructureIds = $structureService->getAccessibleStructures();
    }

    public function render()
    {
        $_appeal_statuses = $this->appealStatuses();

        $_candidates = $this->returnData();

        return view('candidates::livewire.candidates.candidate-list', compact('_appeal_statuses', '_candidates'));
    }
}
