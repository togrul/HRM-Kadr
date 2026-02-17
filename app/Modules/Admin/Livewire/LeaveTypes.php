<?php

namespace App\Modules\Admin\Livewire;

use Livewire\Component;
use App\Models\LeaveType;
use Illuminate\Support\Arr;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\WithPagination as LivewireWithPagination;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait as AdminCallSwalTrait;
use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait as AdminAdminCrudTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests as AccessAuthorizesRequests;

#[On(['leaveTypeUpdated', 'deleted'])]
class LeaveTypes extends Component
{
    use AdminAdminCrudTrait;
    use AccessAuthorizesRequests;
    use AdminCallSwalTrait;
    use LivewireWithPagination;

    public function rules(): array
    {
        return [
            'form.name' => 'required|string|min:2',
            'form.max_days' => 'required|integer|min:0',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name' => __('Name'),
            'form.max_days' => __('Max days'),
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? LeaveType::query()->findOrFail($id)
            : null;

        if ($this->model) {
            $this->form = $this->model->toArray();
            $this->form['requires_document'] = $this->form['requires_document'] ? true : false;
        } else {
            $this->form = [];
        }

        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = LeaveType::findOrFail($id);

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function store(): void
    {
        $this->validate();

        $this->form['requires_document'] = $this->form['requires_document'] ?? false;
        $this->model
            ? $this->model->update(Arr::only($this->form, ['name', 'max_days', 'requires_document']))
            : LeaveType::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('leaveTypeUpdated');
        $this->closeCrud();
    }

    public function mount()
    {
        $this->isAdded = false;
    }

    public function render()
    {
        $leave_types = LeaveType::query()
            ->paginate(20);

        $leave_types = $this->decorateLeaveTypes($leave_types);

        return view('admin::livewire.admin.leave-types', compact('leave_types'));
    }

    protected function decorateLeaveTypes(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (LeaveType $leaveType) {
                $leaveType->requires_document_label = (bool) $leaveType->requires_document;

                return $leaveType;
            })
        );

        return $paginated;
    }
}
