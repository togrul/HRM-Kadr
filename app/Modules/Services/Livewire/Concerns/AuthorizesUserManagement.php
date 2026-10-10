<?php

namespace App\Modules\Services\Livewire\Concerns;

use App\Services\UserAdministrationGuard;
use Illuminate\Support\Facades\Gate;

/**
 * İstifadəçi hesabları ekranları: `access-settings` (bölməyə giriş) üstəgəl `manage-users`.
 * `boot{Trait}` hər Livewire sorğusunda işləyir — hər əməliyyat yoxlanılır.
 */
trait AuthorizesUserManagement
{
    public function bootAuthorizesUserManagement(): void
    {
        Gate::authorize(UserAdministrationGuard::MANAGE_USERS);
    }
}
