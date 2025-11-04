<?php

namespace App\Livewire\Outside\Concerns;

use Carbon\Carbon;
use App\Models\LeaveType;
use App\Models\OrderStatus;
use App\Models\Personnel;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use App\Livewire\Traits\DropdownConstructTrait;

trait InteractsWithLeaveForm
{
    use DropdownConstructTrait;

    public string $personnelName = '';
    public string $assignedSearch = '';

    public function updatedLeave($value, $name): void
    {
        $this->recalculateLeaveDuration();
    }

    public function selectPersonnel(string $tabelNo, string $fullname, string $key): void
    {
        if ($key === 'tabel_no') {
            $this->leave->tabel_no = [
                'tabel_no' => $tabelNo,
                'fullname' => $fullname,
            ];

            $this->reset('personnelName', 'assignedSearch');

            return;
        }

        if ($key === 'assigned_to') {
            $this->leave->assigned_to = [
                'tabel_no' => $tabelNo,
                'fullname' => $fullname,
            ];

            $this->reset('personnelName', 'assignedSearch');
        }
    }

    public function removePersonnel(string $key): void
    {
        if ($key === 'tabel_no') {
            $this->leave->tabel_no = null;

            $this->reset('personnelName');

            return;
        }

        if ($key === 'assigned_to') {
            $this->leave->assigned_to = null;

            $this->reset('assignedSearch');
        }
    }

    #[Computed]
    public function personnelList()
    {
        $canSearch = (strlen($this->personnelName) > 2) || (strlen($this->assignedSearch) > 2);

        if (! $canSearch) {
            return collect();
        }

        return Personnel::query()
            ->when($this->personnelName !== '', fn ($q) => $q->nameLike($this->personnelName))
            ->when($this->assignedSearch !== '', fn ($q) => $q->nameLike($this->assignedSearch))
            ->active()
            ->whereNull('deleted_at')
            ->limit(20)
            ->get();
    }

    #[Computed(cache: true)]
    public function leaveTypes(): array
    {
        $selected = $this->leave->leave_type_id;

        $base = LeaveType::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: null,
            searchTerm: '',
            selectedId: $selected,
            limit: 50
        );
    }

    #[Computed(cache: true)]
    public function statuses(): array
    {
        $selected = $this->leave->status_id;

        $base = OrderStatus::query()
            ->select('id', DB::raw('name as label'))
            ->orderBy('id');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: '',
            selectedId: $selected,
            limit: 50
        );
    }

    protected function recalculateLeaveDuration(): void
    {
        if ($this->leave->starts_at && $this->leave->ends_at) {
            $start = Carbon::parse($this->leave->starts_at);
            $end = Carbon::parse($this->leave->ends_at);

            $this->leave->total_days = $start->diffInDays($end);

            return;
        }

        $this->leave->total_days = null;
    }
}
