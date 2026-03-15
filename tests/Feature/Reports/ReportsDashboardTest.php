<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Modules\Reports\Livewire\Comparisons;
use App\Modules\Reports\Livewire\DynamicBuilder;
use App\Modules\Reports\Livewire\Overview;
use App\Modules\Reports\Livewire\StandardReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_reports_route(): void
    {
        $user = User::factory()->create();
        $this->grantReportsPermissions($user);

        $this->actingAs($user)
            ->get(route('reports'))
            ->assertOk()
            ->assertSee(__('reports::dashboard.title'));
    }

    public function test_overview_component_renders_core_cards(): void
    {
        $user = User::factory()->create();
        $this->grantReportsPermissions($user);

        $this->actingAs($user);

        Livewire::test(Overview::class)
            ->assertSee(__('reports::dashboard.overview.cards.active_personnel'))
            ->assertSee(__('reports::dashboard.overview.cards.top_structures'));
    }

    public function test_standard_reports_component_renders_catalog_and_table_view(): void
    {
        $user = User::factory()->create();
        $this->grantReportsPermissions($user);

        $this->actingAs($user);

        Livewire::test(StandardReports::class)
            ->assertSee(__('reports::dashboard.standard.title'))
            ->assertSee(__('reports::dashboard.cards.table_view'));
    }

    public function test_dynamic_builder_component_renders_controls(): void
    {
        $user = User::factory()->create();
        $this->grantReportsPermissions($user);

        $this->actingAs($user);

        Livewire::test(DynamicBuilder::class)
            ->assertSee(__('reports::dashboard.dynamic.title'))
            ->assertSee(__('reports::dashboard.fields.source'))
            ->assertSee(__('reports::dashboard.fields.metric'));
    }

    public function test_dynamic_builder_accepts_preset_filters_from_parent_route_context(): void
    {
        $user = User::factory()->create();
        $this->grantReportsPermissions($user);

        $this->actingAs($user);

        Livewire::test(DynamicBuilder::class, [
            'source' => 'personnel',
            'groupBy' => 'gender',
            'metric' => 'count',
            'year' => 2026,
            'month' => 3,
        ])
            ->assertSet('source', 'personnel')
            ->assertSet('groupBy', 'gender')
            ->assertSet('metric', 'count')
            ->assertSet('year', 2026)
            ->assertSet('month', 3);
    }

    public function test_comparisons_component_renders_comparison_cards(): void
    {
        $user = User::factory()->create();
        $this->grantReportsPermissions($user);

        $this->actingAs($user);

        Livewire::test(Comparisons::class)
            ->assertSee(__('reports::dashboard.comparisons.cards.headcount_yoy'))
            ->assertSee(__('reports::dashboard.comparisons.cards.attendance_mom'));
    }

    public function test_reports_route_requires_view_permission(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('show-reports', 'web');

        $this->actingAs($user)
            ->get(route('reports'))
            ->assertForbidden();
    }

    protected function grantReportsPermissions(User $user): void
    {
        Permission::findOrCreate('show-reports', 'web');
        Permission::findOrCreate('export-reports', 'web');

        $user->givePermissionTo(['show-reports', 'export-reports']);
    }
}
