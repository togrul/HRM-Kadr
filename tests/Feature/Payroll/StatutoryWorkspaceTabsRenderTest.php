<?php

namespace Tests\Feature\Payroll;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Yeni tablar iş sahələrində açılır: Kompensasiya → «Rejim və sektor», «Vergi güzəştləri»;
 * Maaş → «İcra sənədləri», «Vergi düzəliş hesabatı» (sonuncu yalnız məbləğləri görən istifadəçiyə).
 */
class StatutoryWorkspaceTabsRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_tabs_render_in_both_workspaces(): void
    {
        $user = User::factory()->create();
        grantAllStructures($user);
        foreach (['show-compensation', 'manage-compensation', 'show-payroll', 'manage-payroll', 'export-payroll'] as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->actingAs($user);

        $compensation = Livewire::test(\App\Modules\Compensation\Livewire\Dashboard::class);
        foreach (['regimes' => 'Şirkətin vergi sektoru', 'reliefs' => 'Vergi güzəştləri'] as $tab => $text) {
            $compensation->call('switchTab', $tab)->assertOk()->assertSee($text);
        }

        $payroll = Livewire::test(\App\Modules\Payroll\Livewire\Dashboard::class);
        $payroll->call('switchTab', 'enforcement')->assertOk()->assertSee('İcra sənədləri');
        $payroll->assertDontSee('Vergi düzəliş hesabatı');

        $user->givePermissionTo(Permission::findOrCreate('view-compensation-amounts', 'web'));
        Livewire::test(\App\Modules\Payroll\Livewire\Dashboard::class)
            ->call('switchTab', 'adjustments')
            ->assertOk()
            ->assertSee('Vergi düzəliş hesabatı');
    }
}
