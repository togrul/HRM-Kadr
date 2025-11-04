<?php

namespace App\Livewire\Personnel;

use App\Exports\PersonnelExport;
use App\Livewire\Traits\SideModalAction;
use App\Models\Personnel;
use App\Models\Position;
use App\Services\StructureService;
use App\Traits\NestedStructureTrait;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[On(['personnelAdded', 'fileAdded', 'personnelWasDeleted'])]
class AllPersonnel extends Component
{
    use AuthorizesRequests;
    use NestedStructureTrait;
    use SideModalAction;
    use WithPagination;

    #[Url]
    public $status;

    public array $filters;

    // #[Url]
    public $structure;

    #[Url(as: 'position')]
    public $selectedPosition;

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
        $report['data'] = $this->returnData(type: 'excel');
        $report['filter'] = $this->filters;
        $name = Carbon::now()->format('d.m.Y H:i');

        return Excel::download(new PersonnelExport($report), "personnel-$name.xlsx");
    }

    public function printPage($personnel, $headers = null): void
    {
        $headers = [__('#'), __('Tabel'), __('Fullname'), __('Gender'), __('Position'), 'action', 'action', 'action', 'action'];
        redirect()->route('print.page', ['model' => $personnel, 'headers' => $headers]);
    }

    #[On('filterSelected')]
    public function filterSelected(array $filter)
    {
        $this->filters = $filter;
        $this->reset(['structure']);
    }

    public function setDeletePersonnel($personnelId)
    {
        $this->dispatch('setDeletePersonnel', $personnelId);
    }

    public function restoreData($id)
    {
        $personnel = Personnel::withTrashed()->where('tabel_no', $id)->first();
        $personnel->restore();
        $personnel->update([
            'deleted_by' => null,
        ]);
        $this->dispatch('personnelAdded', __('Personnel was updated successfully!'));
    }

    public function forceDeleteData($id)
    {
        $model = Personnel::withTrashed()->where('tabel_no', $id)->first();
        $model->forceDelete();
        $this->dispatch('personnelWasDeleted', __('Personnel was deleted!'));
    }

    #[On('selectStructure')]
    public function selectStructure($id)
    {
        $this->structure = $this->getNestedStructure($id);
    }

    public function getStatusFilters(): array
    {
        return [
            ['key' => 'current', 'label' => __('Active')],
            ['key' => 'leaves', 'label' => __('Resigned')],
            ['key' => 'all', 'label' => __('All')],
            ['key' => 'deleted', 'label' => __('Deleted'), 'permission' => 'access-admin'],
            ['key' => 'pending', 'label' => __('Pending')],
        ];
    }

    public function getTableHeaders(): array
    {
        return [
            __('#'),
            __('Tabel'),
            __('Fullname'),
            __('Structure'),
            __('Date'),
            'action',
        ];
    }

    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function setPosition($new)
    {
        $this->selectedPosition = $new;
        $this->resetPage();
    }

    public function resetFilter()
    {
        $this->reset('selectedPosition');
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

    #[Computed]
    public function personnels()
    {
        return $this->returnData();
    }

    #[Computed]
    public function positions()
    {
        return Position::query()->orderBy('id')->get();
    }

    public function mount()
    {
        $this->authorize('show-personnels');
        $this->fillFilter();
    }

    protected function returnData($type = 'normal')
    {
        $result = Personnel::with([
            'latestRank.rank',
            'structure',
            'hasActiveVacation',
            'hasActiveBusinessTrip',
            'position',
            'creator',
            'deletedBy',
        ])
            ->when(! empty($this->structure), function ($q) {
                $q->whereIn('structure_id', $this->structure);
            })
            ->when(empty($this->structure), fn($q) => $q->whereIn('structure_id', resolve(StructureService::class)->getAccessibleStructures()))
            ->when(! empty($this->selectedPosition), function ($q) {
                $q->where('position_id', $this->selectedPosition);
            })
            ->when($this->status, function ($q) {
                switch ($this->status) {
                    case 'current':
                        $q->whereNull('leave_work_date');
                        break;
                    case 'leaves':
                        $q->whereNotNull('leave_work_date');
                        break;
                    case 'deleted':
                        $q->onlyTrashed();
                        break;
                    case 'pending':
                        $q->where('is_pending', true);
                        break;
                    default:
                        $q->where('is_pending', false);
                }
            })
            ->filter($this->filters ?? [])
            ->orderBy('position_id')
            ->orderBy('structure_id');

        return $type == 'normal'
            ? $result->paginate(10)->withQueryString()
            : $result->cursor();
    }

    public function render()
    {
        return view('livewire.personnel.all-personnel');
    }
}
