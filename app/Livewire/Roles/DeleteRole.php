<?php

namespace App\Livewire\Roles;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class DeleteRole extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $roleId = null;

    #[On('setDeleteRole')]
    public function setDeleteRole($roleId)
    {
        $role = Role::query()
            ->select('id')
            ->find($roleId);

        if (! $role) {
            $this->roleId = null;

            return;
        }

        // $this->authorize('delete', $role);

        $this->roleId = (int) $role->id;

        $this->dispatch('deleteRoleWasSet');
    }

    public function deleteRole()
    {
        if (! $this->roleId) {
            return;
        }

        $role = Role::query()
            ->select('id')
            ->find($this->roleId);

        if (! $role) {
            $this->roleId = null;

            return;
        }

        // $this->authorize('delete', $role);

        $role->delete();

        $this->roleId = null;

        $this->dispatch('roleWasDeleted', __('Role was deleted!'));
    }

    public function render()
    {
        return view('livewire.roles.delete-role');
    }
}
