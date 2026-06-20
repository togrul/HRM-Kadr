<?php

namespace App\Modules\Admin\Livewire;

use App\Models\SelfServiceApprovalRoute;
use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['selfServiceApprovalRouteUpdated', 'deleted'])]
class SelfServiceApprovalRoutes extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;

    public function rules(): array
    {
        return [
            'form.request_type' => 'required|in:leave,vacation,business_trip',
            'form.include_primary_approver' => 'boolean',
            'form.include_upper_approver' => 'boolean',
            'form.hr_always_included' => 'boolean',
            'form.is_active' => 'boolean',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.request_type' => __('admin::references.fields.request_type'),
            'form.include_primary_approver' => __('admin::references.fields.include_primary_approver'),
            'form.include_upper_approver' => __('admin::references.fields.include_upper_approver'),
            'form.hr_always_included' => __('admin::references.fields.hr_always_included'),
            'form.is_active' => __('admin::references.fields.is_active'),
        ];
    }

    protected function formDefaults(): array
    {
        return [
            'request_type' => 'leave',
            'include_primary_approver' => true,
            'include_upper_approver' => false,
            'hr_always_included' => true,
            'is_active' => true,
        ];
    }

    public function mount(): void
    {
        $this->isAdded = false;
        $this->form = $this->formDefaults();

        // Deep-link from the HR policy diagnostics page (?edit=<request_type>): open the
        // editor straight onto that policy, pre-filled with its current values.
        $type = request()->query('edit');
        if (is_string($type) && in_array($type, ['leave', 'vacation', 'business_trip'], true)) {
            $existing = SelfServiceApprovalRoute::query()
                ->where('request_type', $type)
                ->latest('id')
                ->first();

            $this->openCrud($existing?->id, $type);
        }
    }

    public function openCrud(?int $id = null, ?string $requestType = null): void
    {
        $this->model = $id
            ? SelfServiceApprovalRoute::query()->findOrFail($id)
            : null;

        $this->form = $this->formDefaults();

        if ($requestType && in_array($requestType, ['leave', 'vacation', 'business_trip'], true)) {
            $this->form['request_type'] = $requestType;
        }

        if ($this->model) {
            $this->form = [
                'request_type' => $this->model->request_type,
                'include_primary_approver' => (bool) $this->model->include_primary_approver,
                'include_upper_approver' => (bool) $this->model->include_upper_approver,
                'hr_always_included' => (bool) $this->model->hr_always_included,
                'is_active' => (bool) $this->model->is_active,
            ];
        }

        $this->isAdded = true;
    }

    public function store(): void
    {
        $this->validate();

        $route = $this->model ?: SelfServiceApprovalRoute::query()
            ->where('request_type', (string) data_get($this->form, 'request_type'))
            ->latest('id')
            ->first();

        $payload = [
            'request_type' => (string) data_get($this->form, 'request_type'),
            'include_primary_approver' => (bool) data_get($this->form, 'include_primary_approver', true),
            'include_upper_approver' => (bool) data_get($this->form, 'include_upper_approver', false),
            'hr_always_included' => (bool) data_get($this->form, 'hr_always_included', true),
            'is_active' => (bool) data_get($this->form, 'is_active', true),
            'personnel_id' => null,
            'structure_id' => null,
            'position_id' => null,
            'approver_personnel_id' => null,
            'fallback_approver_personnel_id' => null,
            'created_by' => auth()->id(),
        ];

        $route
            ? $route->update($payload)
            : SelfServiceApprovalRoute::query()->create($payload);

        $this->callSuccessSwal();
        $this->dispatch('selfServiceApprovalRouteUpdated');
        $this->closeCrud();
    }

    public function requestTypeOptions(): array
    {
        return [
            ['id' => 'leave', 'label' => __('personnel::my_hr.requests.types.leave')],
            ['id' => 'vacation', 'label' => __('personnel::my_hr.requests.types.vacation')],
            ['id' => 'business_trip', 'label' => __('personnel::my_hr.requests.types.business_trip')],
        ];
    }

    public function render()
    {
        $stored = SelfServiceApprovalRoute::query()
            ->latest('id')
            ->get()
            ->unique('request_type')
            ->keyBy('request_type');

        $routes = collect($this->requestTypeOptions())
            ->map(function (array $option) use ($stored) {
                $route = $stored->get($option['id']);

                return (object) [
                    'id' => $route?->id,
                    'request_type' => $option['id'],
                    'include_primary_approver' => $route?->include_primary_approver ?? true,
                    'include_upper_approver' => $route?->include_upper_approver ?? false,
                    'hr_always_included' => $route?->hr_always_included ?? true,
                    'is_active' => $route?->is_active ?? true,
                ];
            })
            ->values();

        return view('admin::livewire.admin.self-service-approval-routes', compact('routes'));
    }
}
