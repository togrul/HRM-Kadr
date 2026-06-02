<?php

namespace Tests\Feature\Navigation;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StrategicModuleMenuVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_strategic_modules_are_visible_in_menu_for_authorized_user_only(): void
    {
        foreach ([
            'show-audit-logs',
            'show-document-compliance',
            'show-employee-lifecycle',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $menus = collect([
            Menu::query()->create([
                'name' => 'ui::menu.items.audit_logs',
                'icon' => 'shield-icon',
                'color' => 'zinc',
                'order' => 13,
                'is_active' => 1,
                'url' => 'audit.logs',
                'permission_id' => Permission::findByName('show-audit-logs', 'web')->id,
            ]),
            Menu::query()->create([
                'name' => 'ui::menu.items.document_compliance',
                'icon' => 'document-icon',
                'color' => 'zinc',
                'order' => 14,
                'is_active' => 1,
                'url' => 'document-compliance',
                'permission_id' => Permission::findByName('show-document-compliance', 'web')->id,
            ]),
            Menu::query()->create([
                'name' => 'ui::menu.items.employee_lifecycle',
                'icon' => 'cycle-icon',
                'color' => 'zinc',
                'order' => 15,
                'is_active' => 1,
                'url' => 'employee-lifecycle',
                'permission_id' => Permission::findByName('show-employee-lifecycle', 'web')->id,
            ]),
        ]);

        $authorized = User::factory()->create();
        $authorized->givePermissionTo([
            'show-audit-logs',
            'show-document-compliance',
            'show-employee-lifecycle',
        ]);

        $this->actingAs($authorized);
        $authorizedHtml = view('includes.header', ['menus' => $menus])->render();

        $this->assertStringContainsString('Audit jurnalı', $authorizedHtml);
        $this->assertStringContainsString('Sənəd uyğunluğu', $authorizedHtml);
        $this->assertStringContainsString('Əməkdaş həyat dövrü', $authorizedHtml);

        $unauthorized = User::factory()->create();
        $this->actingAs($unauthorized);
        $unauthorizedHtml = view('includes.header', ['menus' => $menus])->render();

        $this->assertStringNotContainsString('Audit jurnalı', $unauthorizedHtml);
        $this->assertStringNotContainsString('Sənəd uyğunluğu', $unauthorizedHtml);
        $this->assertStringNotContainsString('Əməkdaş həyat dövrü', $unauthorizedHtml);
    }
}
