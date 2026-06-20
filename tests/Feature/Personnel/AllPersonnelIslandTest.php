<?php

namespace Tests\Feature\Personnel;

use App\Models\User;
use App\Modules\Personnel\Livewire\AllPersonnel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AllPersonnelIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_personnel_renders_table_panel_as_lazy_child_component(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-personnels', 'web'));

        $this->actingAs($user);

        $html = Livewire::test(AllPersonnel::class)->html();

        $this->assertTrue(
            str_contains($html, 'FRAGMENT:type=child|name=personnel.table-panel')
            || str_contains($html, 'wire:name="personnel.table-panel"')
        );
        $this->assertStringContainsString('personnel-table-', $html);
    }
}
