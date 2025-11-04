<?php

namespace App\Livewire\Outside;

use App\Models\Leave;
use Livewire\Component;
use App\Models\OrderStatus;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Livewire\Traits\SideModalAction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Data\LeaveFilterData;

#[On(['leaveAdded', 'filterSelected', 'leaveWasDeleted', 'leaveApproved', 'leaveRejected'])]
class Leaves extends Component
{
    use AuthorizesRequests, DropdownConstructTrait ,SideModalAction, WithPagination;

    public LeaveFilterData $filter;

    #[Locked]
    public LeaveFilterData $search;

    #[Url]
    public $status;

    public function applyFilter(?array $payload = null): void
    {
        if ($payload !== null) {
            $this->filter->fillFromArray($payload);
        }

        $this->search = LeaveFilterData::fromArray($this->filter->toArray());
        $this->resetPage();
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
        $this->status = $newStatus;
        $this->resetPage();
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
        $model->forceDelete();
        $this->dispatch('leaveWasDeleted', __('Leave was deleted!'));
    }

    public function restoreData($id)
    {
        $model = Leave::withTrashed()->find($id);
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

    #[Computed(cache:true)]
    public function leaveTypes(): array
    {
        $selected = $this->filter->leave_type_id;

        $base = \App\Models\LeaveType::query()
            ->select('id', DB::raw("name as label"))
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
        // $this->authorize('show-candidates');
        $this->status = request()->query('status', 'all');
        $this->filter = LeaveFilterData::make();
        $this->search = LeaveFilterData::make();
    }

    protected function returnData($type = 'normal')
    {
        $result = Leave::with(['personnel.structure','personnel.position','leaveType', 'status', 'latestLog.changedBy'])
            ->when(is_numeric($this->status), fn($q) => $q->where('status_id', $this->status))
            ->when($this->status === 'deleted', fn($q) => $q->onlyTrashed())
            ->filter($this->search)
            ->orderByDesc('created_at');

        return $type == 'normal'
            ? $result->paginate(15)->withQueryString()
            : $result->cursor();
    }

    public function render()
    {
        $permits = $this->returnData();

        $_appeal_statuses = OrderStatus::query()->where('locale', config('app.locale'))->get();

        return view('livewire.outside.leaves', compact('permits', '_appeal_statuses'));
    }

    public function filterSelected(array $filters): void
    {
        $this->filter = LeaveFilterData::fromArray($filters);
        $this->applyFilter();
    }
}
