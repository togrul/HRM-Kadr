<?php

namespace App\Livewire\Roles;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use App\Livewire\Traits\SideModalAction;


#[On(['permissionSet','roleWasDeleted'])]
class ManageRoles extends Component
{
    use SideModalAction,AuthorizesRequests;

    public $role_name;
    public $role_id;
    public $isUpdate;

    protected $rules = [
        'role_name' => 'required|unique:roles,name',
    ];

    public function editRole($id)
    {
        $this->isUpdate=true;
        $this->resetErrorBag();
        $this->getRoleByID($id);
    }

    public function setDeleteRole($roleId)
    {
        $this->dispatch('setDeleteRole',$roleId);
    }

    public function store()
    {
        $this->validate();
        $data = array(
            'name' => $this->role_name
        );
        Role::updateOrCreate(['id' => $this->role_id],$data);
        $this->dispatch('roleUpdated' , __('Role was updated successfully!'));
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
        $this->role_name=$role->name;
    }

    public function cancel()
    {
        $this->isUpdate = false;
        $this->resetInputFields();
        $this->resetErrorBag();
    }

    private function resetInputFields(){
       $this->role_name = '';
       $this->role_id=null;
    }

    public function render()
    {
        $roles = Role::all();
        return view('livewire.roles.manage-roles',compact('roles'));
    }
}
