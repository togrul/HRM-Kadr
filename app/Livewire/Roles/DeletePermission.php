<?php

namespace App\Livewire\Roles;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class DeletePermission extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $permissionId = null;

    #[On('setDeletePermission')]
    public function setDeletePermission($permissionId)
    {
        $permission = Permission::query()
            ->select('id')
            ->find($permissionId);

        if (! $permission) {
            $this->permissionId = null;

            return;
        }

        // $this->authorize('manage-settings');

        $this->permissionId = (int) $permission->id;

        $this->dispatch('deletePermissionWasSet');
    }

    public function deletePermission()
    {
        if (! $this->permissionId) {
            return;
        }

        $permission = Permission::query()
            ->select('id')
            ->find($this->permissionId);

        if (! $permission) {
            $this->permissionId = null;

            return;
        }

        // $this->authorize('manage-settings');

        $permission->delete();

        $this->permissionId = null;

        $this->dispatch('permissionWasDeleted', __('Permission was deleted!'));
    }

    public function render()
    {
        return view('livewire.roles.delete-permission');
    }
}
