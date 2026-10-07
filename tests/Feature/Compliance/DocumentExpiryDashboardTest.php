<?php

namespace Tests\Feature\Compliance;

use App\Models\User;
use App\Modules\Compliance\Livewire\DocumentExpiryDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\EmployeeLifecycle\LifecycleDashboardBoundedQueuesTest;
use Tests\TestCase;

class DocumentExpiryDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_compliance_route_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('document-compliance'))
            ->assertForbidden();
    }

    public function test_status_and_type_filters_are_selects_bound_to_the_component(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-document-compliance', 'web'));

        // The selects must live in the component's own output (not the layout's sidebar
        // slot), or their wire:model bindings are unreachable from the UI. Clearing a
        // select sends null, which must fall back to the "all" state ('').
        Livewire::actingAs($user)
            ->test(DocumentExpiryDashboard::class)
            ->assertSeeHtml('compliance-status-filter')
            ->assertSeeHtml('compliance-type-filter')
            ->assertSee(__('compliance::documents.filters.all_statuses'))
            ->assertSee(__('compliance::documents.summary.compliance_score'))
            ->set('status', 'expired')
            ->assertSet('status', 'expired')
            ->set('status', null)
            ->assertSet('status', '')
            ->set('type', 'passport')
            ->assertSet('type', 'passport')
            ->set('type', null)
            ->assertSet('type', '');
    }

    public function test_document_table_is_paginated_and_filters_reset_the_page(): void
    {
        // 12 personnel without documents -> 36 synthesized "missing" rows.
        LifecycleDashboardBoundedQueuesTest::seedLargeFixture(12);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-document-compliance', 'web'));

        $component = Livewire::actingAs($user)->test(DocumentExpiryDashboard::class);

        $rows = $component->viewData('rows');
        $this->assertSame(DocumentExpiryDashboard::PER_PAGE, $rows->count());
        $this->assertGreaterThan(DocumentExpiryDashboard::PER_PAGE, $rows->total());

        $component->call('gotoPage', 2);
        $this->assertSame($rows->total() - DocumentExpiryDashboard::PER_PAGE, $component->viewData('rows')->count());

        $component->set('search', 'Surname1');
        $this->assertSame(1, $component->viewData('rows')->currentPage());
        foreach ($component->viewData('rows') as $row) {
            $this->assertStringContainsString('Surname1', $row['personnel_name']);
        }
    }

    public function test_csv_export_uses_translated_status_labels_not_codes(): void
    {
        LifecycleDashboardBoundedQueuesTest::seedLargeFixture(2);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-document-compliance', 'web'));

        $csv = Livewire::actingAs($user)
            ->test(DocumentExpiryDashboard::class)
            ->call('exportCsv')
            ->effects['download']['content'] ?? '';

        $csv = base64_decode($csv);
        $this->assertStringContainsString(__('compliance::documents.status.missing'), $csv);
        $this->assertStringContainsString(__('compliance::documents.columns.tabel_no'), $csv);
        $this->assertStringNotContainsString(',missing', $csv);
    }
}
