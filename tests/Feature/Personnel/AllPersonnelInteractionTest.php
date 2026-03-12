<?php

namespace Tests\Feature\Personnel;

use App\Models\User;
use App\Modules\Personnel\Livewire\AllPersonnel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AllPersonnelInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_personnel_can_open_add_personnel_side_menu(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            Permission::findOrCreate('show-personnels', 'web'),
            Permission::findOrCreate('add-personnels', 'web'),
        ]);

        $this->actingAs($user);

        Livewire::test(AllPersonnel::class)
            ->call('openSideMenu', 'add-personnel')
            ->assertSet('showSideMenu', 'add-personnel')
            ->assertSet('isSideModalOpen', true);
    }

    public function test_all_personnel_can_mount_filter_detail_flow(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-personnels', 'web'));

        $this->actingAs($user);

        Livewire::test(AllPersonnel::class)
            ->call('openFilter')
            ->assertSet('filterDetailMounted', true)
            ->assertSet('pendingFilterOpen', true)
            ->call('handleFilterDetailReady')
            ->assertSet('pendingFilterOpen', false);
    }
}
