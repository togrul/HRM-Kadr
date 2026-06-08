<?php

namespace Tests\Feature\Services;

use App\Models\Role;
use App\Models\User;
use App\Modules\Services\Livewire\Roles\ManageRoles;
use App\Modules\Services\Livewire\Roles\SetPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RolesDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_dashboard_renders_card_layout_copy(): void
    {
        $role = Role::query()->create([
            'name' => 'Administrator',
            'guard_name' => 'web',
        ]);

        $permission = Permission::findOrCreate('show-candidates', 'web');
        $role->givePermissionTo($permission);

        $user = User::factory()->create(['name' => 'Jane Doe']);
        $user->assignRole($role);

        Livewire::test(ManageRoles::class)
            ->assertSee(__('services::roles.dashboard.title'))
            ->assertSee(__('services::roles.dashboard.subtitle'))
            ->assertSee('Administrator')
            ->assertSee(__('services::roles.actions.create_role'))
            ->assertSee('1 icazə');
    }

    public function test_permission_panel_filters_groups_and_saves_selection(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'QA Permission Manager',
            'guard_name' => 'web',
        ]);

        $candidatePermission = Permission::query()->create([
            'name' => 'show-candidates',
            'guard_name' => 'web',
            'description' => 'Namizədlər moduluna baxış icazəsi verir.',
        ]);

        Permission::query()->create([
            'name' => 'show-personnels',
            'guard_name' => 'web',
            'description' => 'Şəxsi heyət moduluna baxış icazəsi verir.',
        ]);

        Livewire::actingAs($user)
            ->test(SetPermission::class, ['roleModel' => $role->id])
            ->assertSee(__('services::roles.permission_panel.title'))
            ->assertSee('x-data="{ open: false }', false)
            ->assertSee("querySelector('[x-ref=closeBtn]')", false)
            ->set('permissionSearch', 'namized')
            ->assertSee(__('services::permissions.groups.candidates'))
            ->set('permissionList', [$candidatePermission->id])
            ->call('store')
            ->assertDispatched('permissionSet');

        $this->assertTrue($role->fresh()->hasPermissionTo('show-candidates'));
    }

    public function test_permission_panel_sorts_groups_by_translated_label(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'Sorting Auditor',
            'guard_name' => 'web',
        ]);

        foreach (['view-learning-library', 'view-onboarding-library', 'view-own-hierarchy', 'show-audit-logs'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $component = Livewire::actingAs($user)
            ->test(SetPermission::class, ['roleModel' => $role->id]);

        $groupLabels = array_map(
            fn (array $group): string => __($group['translation_key']),
            $component->instance()->permissions
        );

        $normalize = fn (string $value): string => strtr(str($value)->lower()->trim()->toString(), [
            'ə' => 'e',
            'ö' => 'o',
            'ü' => 'u',
            'ı' => 'i',
            'i̇' => 'i',
            'ğ' => 'g',
            'ş' => 's',
            'ç' => 'c',
        ]);

        $sortedLabels = $groupLabels;
        usort($sortedLabels, fn (string $first, string $second): int => strnatcasecmp($normalize($first), $normalize($second)));

        $this->assertSame($sortedLabels, array_values($groupLabels));
    }
}
