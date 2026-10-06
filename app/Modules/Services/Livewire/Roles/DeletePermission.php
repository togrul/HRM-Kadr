<?php

namespace App\Modules\Services\Livewire\Roles;

use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

class DeletePermission extends Component
{
    use AuthorizesRequests;
    use AuthorizesSettingsAccess;

    #[Locked]
    public ?int $permissionId = null;

    #[On('setDeletePermission')]
    public function setDeletePermission($permissionId): void
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

    public function deletePermission(): void
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

        $this->dispatch('permissionWasDeleted', __('services::roles.messages.permission_deleted'));
    }

    public function render(): View
    {
        return view('services::livewire.services.roles.delete-permission');
    }
}
