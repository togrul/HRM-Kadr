<?php

namespace App\Modules\Services\Livewire\Roles;

use App\Livewire\Traits\SideModalAction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

#[On(['permissionSet', 'roleWasDeleted'])]
class ManageRoles extends Component
{
    use AuthorizesRequests,SideModalAction;

    public $role_name;

    public $role_id;

    public $isUpdate;

    protected function rules(): array
    {
        return [
            'role_name' => [
                'required',
                Rule::unique('roles', 'name')->ignore($this->role_id),
            ],
        ];
    }

    public function editRole($id)
    {
        $this->isUpdate = true;
        $this->resetErrorBag();
        $this->getRoleByID($id);
    }

    public function setDeleteRole($roleId)
    {
        $this->dispatch('setDeleteRole', $roleId);
    }

    public function store()
    {
        $this->validate();

        $payload = ['name' => $this->role_name];

        if ($this->role_id) {
            Role::findOrFail($this->role_id)->update($payload);
        } else {
            Role::create($payload);
        }

        $this->dispatch('roleUpdated', __('Role was updated successfully!'));
        $this->cancel();
    }

    public function sendRole($id)
    {
        $this->getRoleByID($id);
        $this->openSidebar();
        $this->dispatch('getRole', $id);
        $this->resetErrorBag();
    }

    private function getRoleByID($id)
    {
        $role = Role::findOrFail($id);
        $this->role_id = $id;
        $this->role_name = $role->name;
    }

    public function cancel()
    {
        $this->isUpdate = false;
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    private function resetInputFields()
    {
        $this->role_name = '';
        $this->role_id = null;
    }

    public function render()
    {
        $roles = Role::all();

        return view('services::livewire.services.roles.manage-roles', compact('roles'));
    }
}
