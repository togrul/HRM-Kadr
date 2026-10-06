<?php

namespace App\Modules\Services\Livewire\Concerns;

use Illuminate\Support\Facades\Gate;

/**
 * Every settings screen (users, roles, permissions, ranks, menus, settings) is admin-only.
 * Livewire runs `boot{Trait}` on the first render and on every later request, so each
 * action is checked — hiding the menu entry alone protects nothing.
 */
trait AuthorizesSettingsAccess
{
    public function bootAuthorizesSettingsAccess(): void
    {
        Gate::authorize('access-settings');
    }
}
