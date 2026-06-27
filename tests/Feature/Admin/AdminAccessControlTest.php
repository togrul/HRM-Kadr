<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Admin\Livewire\Countries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_area_is_forbidden_without_access_admin_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/countries')->assertForbidden();
    }

    public function test_admin_area_is_reachable_with_access_admin_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('access-admin', 'web'));
        $this->actingAs($user);

        $this->get('/admin/countries')->assertOk();
    }

    public function test_reference_data_deletion_is_forbidden_without_access_admin(): void
    {
        // Defense-in-depth guard on AdminCrudTrait::delete() — even if a snapshot leaked,
        // the destructive path re-asserts the permission.
        $this->actingAs(User::factory()->create());

        Livewire::test(Countries::class)
            ->call('delete')
            ->assertForbidden();
    }
}
