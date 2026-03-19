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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

#[On(['leaveTypeUpdated', 'deleted'])]
class LeaveTypes extends Component
{
    use AdminAdminCrudTrait;
    use AccessAuthorizesRequests;
    use AdminCallSwalTrait;
    use LivewireWithPagination;

    public bool $supportsAttendanceCode = false;

    public function rules(): array
    {
        $rules = [
            'form.name' => 'required|string|min:2',
            'form.max_days' => 'required|integer|min:0',
        ];

        if ($this->supportsAttendanceCode) {
            $rules['form.attendance_code'] = [
                'nullable',
                'string',
                'min:2',
                'max:8',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('leave_types', 'attendance_code')->ignore($this->model?->getKey()),
            ];
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name' => __('admin::leave_types.fields.name'),
            'form.attendance_code' => __('admin::leave_types.fields.attendance_code'),
            'form.max_days' => __('admin::leave_types.fields.max_days'),
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

        if (! $this->supportsAttendanceCode) {
            $this->form['attendance_code'] = null;
        } else {
            $this->form['attendance_code'] = $this->normalizeAttendanceCode($this->form['attendance_code'] ?? null);
        }

        $this->isAdded = true;
    }

    public function updatedFormAttendanceCode(mixed $value): void
    {
        if (! $this->supportsAttendanceCode) {
            return;
        }

        $this->form['attendance_code'] = $this->normalizeAttendanceCode($value);
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
        $payload = Arr::only($this->form, ['name', 'max_days', 'requires_document']);

        if ($this->supportsAttendanceCode) {
            $payload['attendance_code'] = $this->normalizeAttendanceCode($this->form['attendance_code'] ?? null);
        }

        $this->model
            ? $this->model->update($payload)
            : LeaveType::create($payload);

        $this->callSuccessSwal();

        $this->dispatch('leaveTypeUpdated');
        $this->closeCrud();
    }

    public function mount()
    {
        $this->supportsAttendanceCode = Schema::hasColumn('leave_types', 'attendance_code');
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
                $leaveType->setAttribute('attendance_code', $this->supportsAttendanceCode
                    ? ($leaveType->getAttributes()['attendance_code'] ?? null)
                    : null);

                return $leaveType;
            })
        );

        return $paginated;
    }

    protected function normalizeAttendanceCode(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return $normalized !== '' ? preg_replace('/\s+/', '', $normalized) : null;
    }
}
