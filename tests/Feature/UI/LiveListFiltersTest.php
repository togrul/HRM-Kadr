<?php

use App\Models\User;
use App\Modules\BusinessTrips\Livewire\BusinessTrips;
use App\Modules\Candidates\Livewire\CandidateList;
use App\Modules\Leaves\Livewire\Leaves;
use App\Modules\Orders\Livewire\AllOrders;
use App\Modules\Vacation\Livewire\Vacations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * Every list toolbar filters as you type — no "Search" button — and offers
 * "Clear filters" only while something is actually filtered.
 */
it('applies a toolbar filter on change and offers a reset only while filtered', function (string $component, string $permission, string $field, string $applied): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    $this->actingAs($user);

    $list = Livewire::test($component)
        ->assertSet('hasActiveFilters', false)
        ->assertDontSee(__('ui::common.actions.reset_filters'))
        ->set($field, 'Məmmədov')
        ->assertSet($applied, 'Məmmədov')
        ->assertSet('hasActiveFilters', true)
        ->assertSee(__('ui::common.actions.reset_filters'));

    $list->call('resetFilter')
        ->assertSet('hasActiveFilters', false)
        ->assertDontSee(__('ui::common.actions.reset_filters'));
})->with([
    'vacations' => [Vacations::class, 'show-vacations', 'filter.fullname', 'search.fullname'],
    'leaves' => [Leaves::class, 'show-leaves', 'filter.fullname', 'search.fullname'],
    'business trips' => [BusinessTrips::class, 'show-business_trips', 'filter.fullname', 'search.fullname'],
    'candidates' => [CandidateList::class, 'show-candidates', 'filter.fullname', 'search.fullname'],
    'orders' => [AllOrders::class, 'show-orders', 'search.order_no', 'search.order_no'],
]);
