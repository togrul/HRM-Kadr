<?php

namespace App\Livewire\Roles;

use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

#[On('permissionWasDeleted')]
class Permissions extends Component
{
    public $permission_id;

    public $permission_name;

    protected $rules = [
        'permission_name' => 'required|unique:permissions,name',
    ];

    public function editPermission($id)
    {
        $permission = Permission::findOrFail($id);
        $this->permission_id = $id;
        $this->permission_name = $permission->name;
    }

    public function store()
    {
        $this->validate();
        $data = [
            'name' => $this->permission_name,
        ];
        Permission::updateOrCreate(['id' => $this->permission_id], $data);
        $this->dispatch('permissionUpdated', __('Permission was added successfully!'));
        $this->resetInputFields();
        $this->resetErrorBag();

        return back();
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

        return view('livewire.roles.permissions', compact('permissions'));
    }
}
