<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HrPolicyDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_hr_policy_diagnostics_screen(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('access-admin', 'web'));

        $this->actingAs($user)
            ->get(route('admin.hr-policy-diagnostics'))
            ->assertOk()
            ->assertSee(__('admin::references.menu.hr_policy_diagnostics'))
            ->assertSee(__('admin::references.diagnostics.active_pack'));
    }
}
