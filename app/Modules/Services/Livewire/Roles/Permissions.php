<?php

namespace App\Modules\Services\Livewire\Roles;

use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

#[On('permissionWasDeleted')]
class Permissions extends Component
{
    public $permission_id;

    public $permission_name;

    protected function rules(): array
    {
        return [
            'permission_name' => [
                'required',
                Rule::unique('permissions', 'name')->ignore($this->permission_id),
            ],
        ];
    }

    public function editPermission($id)
    {
        $permission = Permission::findOrFail($id);
        $this->permission_id = $id;
        $this->permission_name = $permission->name;
    }

    public function store()
    {
        $this->validate();
        $data = ['name' => $this->permission_name];

        if ($this->permission_id) {
            Permission::findOrFail($this->permission_id)->update($data);
        } else {
            Permission::create($data);
        }

        $this->dispatch('permissionUpdated', __('Permission was added successfully!'));
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    public function setDeletePermission($permissionId)
    {
        $this->dispatch('setDeletePermission', $permissionId);
    }

    public function cancel()
    {
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    private function resetInputFields()
    {
        $this->permission_name = '';
        $this->permission_id = null;
    }

    public function render()
    {
        $permissions = Permission::all();

        return view('services::livewire.services.roles.permissions', compact('permissions'));
    }
}
