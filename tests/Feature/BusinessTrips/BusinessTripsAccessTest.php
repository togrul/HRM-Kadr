<?php

namespace Tests\Feature\BusinessTrips;

use App\Models\PersonnelBusinessTrip;
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

    public function test_empty_list_explains_trips_come_from_orders(): void
    {
        $this->actingAs($this->userWith('show-business_trips', 'show-orders'));

        Livewire::test(BusinessTrips::class)
            ->assertSee(__('business_trips::common.hints.from_orders'))
            ->assertSee(__('business_trips::common.actions.go_to_orders'))
            ->assertSee(route('orders'));

        $this->actingAs($this->userWith('show-business_trips'));

        Livewire::test(BusinessTrips::class)
            ->assertSee(__('business_trips::common.hints.from_orders'))
            ->assertDontSee(__('business_trips::common.actions.go_to_orders'));
    }

    public function test_export_and_print_need_the_export_permission(): void
    {
        $this->actingAs($this->userWith('show-business_trips'));

        Livewire::test(BusinessTrips::class)->call('exportExcel')->assertForbidden();

        \Maatwebsite\Excel\Facades\Excel::fake();
        $this->actingAs($this->userWith('show-business_trips', 'export-business_trips'));

        Livewire::test(BusinessTrips::class)->call('exportExcel')->assertOk();
    }

    public function test_order_type_filter_reads_the_order_column(): void
    {
        // order_type_id sits on order_logs; filtering through order.orderType asked
        // order_types for a column it does not have.
        $sql = PersonnelBusinessTrip::query()->filter(['order_type_id' => 7])->toSql();

        $this->assertStringContainsString('"order_logs"', $sql);
        $this->assertStringNotContainsString('"order_types"', $sql);
        PersonnelBusinessTrip::query()->filter(['order_type_id' => 7])->get();
    }

    public function test_on_trip_bucket_only_counts_trips_running_today(): void
    {
        $today = now()->toDateString();
        $sql = fn (string $status): array => PersonnelBusinessTrip::query()->filter(['business_trip_status' => $status])->getQuery()->wheres;

        // "Ezamiyyətdə" = started and not yet over — an upcoming trip is not counted.
        $this->assertSame(
            [['start_date', '<=', $today], ['end_date', '>=', $today]],
            collect($sql('in_business_trip'))->where('type', 'Basic')->map(fn ($w) => [$w['column'], $w['operator'], $w['value']])->values()->all()
        );
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
