<?php

namespace App\Modules\Services\Livewire\Roles;

use App\Modules\Services\Livewire\Concerns\AuthorizesRoleManagement;
use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use App\Services\UserAdministrationGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class DeleteRole extends Component
{
    use AuthorizesRequests;
    use AuthorizesRoleManagement;
    use AuthorizesSettingsAccess;

    #[Locked]
    public ?int $roleId = null;

    #[On('setDeleteRole')]
    public function setDeleteRole($roleId): void
    {
        $role = Role::query()
            ->select('id', 'name')
            ->find($roleId);

        if (! $role) {
            $this->roleId = null;

            return;
        }

        if ($this->refuse($role)) {
            return;
        }

        $this->roleId = (int) $role->id;

        $this->dispatch('deleteRoleWasSet');
    }

    public function deleteRole(): void
    {
        if (! $this->roleId) {
            return;
        }

        $role = Role::query()
            ->select('id', 'name')
            ->find($this->roleId);

        if (! $role) {
            $this->roleId = null;

            return;
        }

        if ($this->refuse($role)) {
            $this->roleId = null;

            return;
        }

        $role->delete();

        $this->roleId = null;

        $this->dispatch('roleWasDeleted', __('services::roles.messages.role_deleted'));
    }

    /**
     * The admin role and any role still assigned to users stay; the reason is shown as a toast.
     */
    private function refuse(Role $role): bool
    {
        $reason = match (true) {
            self::isAdminRole($role->name) => __('services::roles.messages.admin_role_protected'),
            app(UserAdministrationGuard::class)->isSystemRole($role->name) => __('services::roles.messages.system_role_protected'),
            $role->users()->exists() => __('services::roles.messages.role_has_users'),
            default => null,
        };

        if ($reason === null) {
            return false;
        }

        $this->dispatch('notify', type: 'error', message: $reason);

        return true;
    }

    public static function isAdminRole(string $name): bool
    {
        return strcasecmp(trim($name), 'admin') === 0;
    }

    public function render(): View
    {
        return view('services::livewire.services.roles.delete-role');
    }
}
