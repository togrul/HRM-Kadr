<?php

namespace Tests\Feature\BusinessTrips;

use App\Models\User;
use App\Modules\BusinessTrips\Livewire\BusinessTrips;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BusinessTripsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_is_forbidden_without_show_permission(): void
    {
        // The route group is only web+auth; the component itself is the access gate.
        $this->actingAs(User::factory()->create());

        Livewire::test(BusinessTrips::class)->assertForbidden();
    }

    public function test_listing_is_allowed_with_show_permission(): void
    {
        $this->actingAs($this->userWith('show-business_trips'));

        Livewire::test(BusinessTrips::class)
            ->assertOk()
            ->assertSet('filter.business_trip_status', 'all');
    }

    public function test_search_filter_applies_the_current_filter(): void
    {
        $this->actingAs($this->userWith('show-business_trips'));

        Livewire::test(BusinessTrips::class)
            ->set('filter.business_trip_status', 'active')
            ->call('searchFilter')
            ->assertSet('search.business_trip_status', 'active');
    }

    public function test_reset_filter_restores_defaults(): void
    {
        $this->actingAs($this->userWith('show-business_trips'));

        Livewire::test(BusinessTrips::class)
            ->set('filter.business_trip_status', 'active')
            ->set('filter.structure_id', 5)
            ->call('resetFilter')
            ->assertSet('filter.business_trip_status', 'all')
            ->assertSet('filter.structure_id', null);
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
