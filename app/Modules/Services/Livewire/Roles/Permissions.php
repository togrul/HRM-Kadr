<?php

namespace App\Modules\Services\Livewire\Roles;

use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

#[On('permissionWasDeleted')]
class Permissions extends Component
{
    use WithPagination, WithoutUrlPagination;

    public $permission_id;

    public $permission_name;

    public string $permission_description = '';

    public string $search = '';

    public int $perPage = 25;

    public bool $showPermissionModal = false;

    protected function rules(): array
    {
        return [
            'permission_name' => [
                'required',
                Rule::unique('permissions', 'name')->ignore($this->permission_id),
            ],
            'permission_description' => ['required', 'string', 'min:12'],
        ];
    }

    public function createPermission(): void
    {
        $this->resetInputFields();
        $this->resetErrorBag();
        $this->showPermissionModal = true;
    }

    public function editPermission($id): void
    {
        $permission = Permission::findOrFail($id);
        $this->permission_id = $id;
        $this->permission_name = $permission->name;
        $this->permission_description = (string) ($permission->description ?? '');
        $this->resetErrorBag();
        $this->showPermissionModal = true;
    }

    public function closePermissionModal(): void
    {
        $this->showPermissionModal = false;
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    public function store(): void
    {
        $this->validate();
        $data = [
            'name' => $this->permission_name,
            'description' => $this->permission_description,
        ];

        if ($this->permission_id) {
            $permission = Permission::findOrFail($this->permission_id);
            $permission->forceFill($data)->save();
        } else {
            $permission = new Permission;
            $permission->forceFill($data + ['guard_name' => 'web'])->save();
        }

        $this->dispatch('permissionUpdated', __('Permission was added successfully!'));
        $this->showPermissionModal = false;
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    public function setDeletePermission($permissionId)
    {
        $this->dispatch('setDeletePermission', $permissionId);
    }

    public function cancel(): void
    {
        $this->closePermissionModal();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    private function resetInputFields()
    {
        $this->permission_name = '';
        $this->permission_description = '';
        $this->permission_id = null;
    }

    public function render()
    {
        $permissions = Permission::query()
            ->select('id', 'name', 'description')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($innerQuery): void {
                    $innerQuery
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('services::livewire.services.roles.permissions', compact('permissions'));
    }
}
