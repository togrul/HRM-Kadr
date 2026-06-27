<?php

namespace Tests\Feature\Vacation;

use App\Models\User;
use App\Modules\Vacation\Livewire\Vacations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VacationsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_is_forbidden_without_show_permission(): void
    {
        // The route group is only web+auth; the component itself is the access gate.
        $this->actingAs(User::factory()->create());

        Livewire::test(Vacations::class)->assertForbidden();
    }

    public function test_listing_is_allowed_with_show_permission(): void
    {
        $this->actingAs($this->userWith('show-vacations'));

        Livewire::test(Vacations::class)->assertOk();
    }

    public function test_search_filter_applies_the_current_filter(): void
    {
        $this->actingAs($this->userWith('show-vacations'));

        Livewire::test(Vacations::class)
            ->set('filter.structure_id', 7)
            ->call('searchFilter')
            ->assertSet('search.structure_id', 7);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }
}
