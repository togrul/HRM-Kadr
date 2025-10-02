<?php

namespace App\Livewire\Outside;

use Carbon\Carbon;
use App\Models\Leave;
use Livewire\Component;
use App\Models\OrderStatus;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Enums\OrderStatusEnum;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Livewire\Traits\SideModalAction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[On(['leaveAdded', 'filterSelected', 'leaveWasDeleted', 'leaveApproved', 'leaveRejected'])]
class Leaves extends Component
{
    use AuthorizesRequests, SideModalAction, WithPagination;

    public array $filter = [];

    public array $search = [];

    #[Url]
    public $status;

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

    public function searchFilter(): void
    {
        $this->applyFilter();
    }

    public function setStatus($newStatus): void
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    private function setPermitStatus(int $id, OrderStatusEnum $toStatus, string $successEvent, string $successMsg): void
    {
        DB::transaction(function () use ($id, $toStatus, $successEvent, $successMsg) {
            $userId = auth()->id();
            $now    = now();

            // Yekun statuslar: artıq APPROVED və ya CANCELLED-disə dəyişməyə icazə vermirik
            $finalStatuses = [
                OrderStatusEnum::APPROVED->value,
                OrderStatusEnum::CANCELLED->value,
            ];

            // Yalnız yekunlaşmamış sorğuda statusu dəyiş (atomik şərtli update)
            $updates = ['status_id' => $toStatus->value];

            if ($toStatus === OrderStatusEnum::APPROVED) {
                $updates['approved_at'] = $now;
                $updates['approved_by'] = $userId;
            }

            $affected = Leave::whereKey($id)
                ->whereNotIn('status_id', $finalStatuses)
                ->update($updates);

            if ($affected !== 1) {
                // artıq kim isə yekunlaşdırıb
                throw new \RuntimeException('This leave request is already finalized.');
            }

            // Log üçün modeli götür (istəsən select yalnız lazım sütunlar)
            $leave = Leave::findOrFail($id);

            $leave->logs()->create([
                'status_id'  => $toStatus->value,
                'changed_by' => $userId,
                'comment'    => '',
                'changed_at' => $now,
            ]);

            // Livewire/Event toast
            $this->dispatch($successEvent, $successMsg);
        });
    }

    public function approvePermit(int $id): void
    {
        $this->setPermitStatus(
            $id,
            OrderStatusEnum::APPROVED,
            'leaveApproved',
            __('Leave was approved successfully!')
        );
    }

    public function rejectPermit(int $id): void
    {
        $this->setPermitStatus(
            $id,
            OrderStatusEnum::CANCELLED,
            'leaveRejected',
            __('Leave was rejected successfully!')
        );
    }

    public function getTableHeaders(): array
    {
        return [
            __('#'),
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

    public function mount(): void
    {
        // $this->authorize('show-candidates');
        $this->status = request()->query('status', 'all');
    }

    protected function returnData($type = 'normal')
    {
        $result = Leave::with(['personnel.structure','personnel.position','leaveType', 'status', 'latestLog.changedBy'])
            ->when(is_numeric($this->status), fn($q) => $q->where('status_id', $this->status))
            ->when($this->status === 'deleted', fn($q) => $q->onlyTrashed())
            // ->filter($this->search ?? [])
            ->orderByDesc('created_at');

        return $type == 'normal'
            ? $result->paginate(15)->withQueryString()
            : $result->get()->toArray();
    }

    public function render()
    {
        $permits = $this->returnData();

        $_appeal_statuses = OrderStatus::where('locale', config('app.locale'))->get();

        return view('livewire.outside.leaves', compact('permits', '_appeal_statuses'));
    }
}
