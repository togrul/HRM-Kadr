<?php

namespace App\Modules\Leaves\Livewire\Concerns;

use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\LeaveType;
use App\Models\OrderStatus;
use App\Models\Personnel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

trait InteractsWithLeaveForm
{
    use DropdownConstructTrait;

    public string $personnelName = '';
    public string $assignedSearch = '';

    public function updatedLeave($value, $name): void
    {
        $this->recalculateLeaveDuration();
    }

    public function selectPersonnel(string $tabelNo, string $fullname, string $key, ?int $personnelId = null): void
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
                'id' => $personnelId,
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
    public function applicantPersonnelList()
    {
        return $this->searchPersonnelOptions($this->personnelName);
    }

    #[Computed]
    public function assignedPersonnelList()
    {
        return $this->searchPersonnelOptions($this->assignedSearch);
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

    #[Computed]
    public function selectedLeaveTypeMeta(): ?array
    {
        $leaveTypeId = $this->leave->leave_type_id;

        if (! $leaveTypeId) {
            return null;
        }

        /** @var LeaveType|null $leaveType */
        $leaveType = LeaveType::query()
            ->select('id', 'name', 'max_days', 'requires_document')
            ->find((int) $leaveTypeId);

        if (! $leaveType) {
            return null;
        }

        return [
            'id' => (int) $leaveType->id,
            'name' => (string) $leaveType->name,
            'max_days' => max(0, (int) $leaveType->max_days),
            'requires_document' => (bool) $leaveType->requires_document,
        ];
    }

    #[Computed]
    public function leaveDurationNotice(): ?array
    {
        $meta = $this->selectedLeaveTypeMeta;
        $totalDays = (int) ($this->leave->total_days ?? 0);

        if (! $meta || $totalDays <= 0) {
            return null;
        }

        $maxDays = (int) data_get($meta, 'max_days', 0);

        if ($maxDays <= 0 || $totalDays <= $maxDays) {
            return null;
        }

        return [
            'type_name' => (string) data_get($meta, 'name', ''),
            'max_days' => $maxDays,
            'selected_days' => $totalDays,
        ];
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

            $this->leave->total_days = $start->diffInDays($end) + 1;

            return;
        }

        $this->leave->total_days = null;
    }

    protected function searchPersonnelOptions(string $term)
    {
        if (mb_strlen(trim($term)) <= 2) {
            return collect();
        }

        return Personnel::query()
            ->nameLike($term)
            ->active()
            ->whereNull('deleted_at')
            ->limit(20)
            ->get();
    }
}
