<?php

namespace App\Livewire\Roles;

use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class DeleteRole extends Component
{
    public ?Role $role;

    #[On('setDeleteRole')]
    public function setDeleteRole($roleId)
    {
        $this->role = Role::findOrFail($roleId);

        $this->dispatch('deleteRoleWasSet');
    }

    public function deleteRole()
    {
        // $this->authorize('delete',$this->comment);
        Role::destroy($this->role->id);

        $this->role = null;

        $this->dispatch('roleWasDeleted', __('Role was deleted!'));
    }

    public function render()
    {
        return view('livewire.roles.delete-role');
    }
}
