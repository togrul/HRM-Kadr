<?php

namespace App\Modules\Services\Livewire\Concerns;

use App\Services\UserAdministrationGuard;
use Illuminate\Support\Facades\Gate;

/**
 * Rol və icazə ekranları: `access-settings` (bölməyə giriş) üstəgəl `manage-roles`.
 * `boot{Trait}` hər Livewire sorğusunda işləyir — hər əməliyyat yoxlanılır.
 */
trait AuthorizesRoleManagement
{
    public function bootAuthorizesRoleManagement(): void
    {
        Gate::authorize(UserAdministrationGuard::MANAGE_ROLES);
    }
}
