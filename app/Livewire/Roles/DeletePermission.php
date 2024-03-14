<?php

namespace App\Livewire\Roles;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class DeletePermission extends Component
{
    use AuthorizesRequests;
    public ?Permission $permission;

    #[On('setDeletePermission')]
    public function setDeletePermission($permissionId)
    {
        $this->permission = Permission::findOrFail($permissionId);

        $this->dispatch('deletePermissionWasSet');
    }

    public function deletePermission()
    {
        // $this->authorize('manage-settings');
        Permission::destroy($this->permission->id);

        $this->permission = null;

        $this->dispatch('permissionWasDeleted' , __('Permission was deleted!'));
    }

    public function render()
    {
        return view('livewire.roles.delete-permission');
    }
}
