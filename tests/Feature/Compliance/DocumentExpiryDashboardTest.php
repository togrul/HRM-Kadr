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

    public function test_panel_filters_render_inside_the_livewire_root(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('show-document-compliance', 'web'));

        // A context panel put in the raw sidebar slot is rendered by the layout, outside the
        // Livewire root, and every wire:click in it dies silently — so assert the teleport.
        Livewire::actingAs($user)
            ->test(DocumentExpiryDashboard::class)
            ->assertSee("\$set('status', 'expired')", false)
            ->assertSee("\$set('type', 'passport')", false)
            ->assertSee(__('compliance::documents.summary.compliance_score'));
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
}
