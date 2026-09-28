<?php

use App\Models\User;
use App\Modules\PerformanceEvaluation\Livewire\Dashboard;
use App\Modules\PerformanceEvaluation\Livewire\OperationsWorkspace;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function performanceUser(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user->givePermissionTo($permissions);

    return $user;
}

it('opens the evaluations tab with the assign form requested', function (): void {
    $this->actingAs(performanceUser(['show-performance-evaluation', 'manage-performance-evaluation']));

    Livewire::test(Dashboard::class)
        ->call('startAssignment')
        ->assertSet('activeTab', 'evaluations')
        ->assertSet('assignOnOpen', true)
        ->call('switchTab', 'overview')
        ->assertSet('activeTab', 'overview')
        ->assertSet('assignOnOpen', false);
});

it('re-opens the form by event when the evaluations tab is already showing it', function (): void {
    $this->actingAs(performanceUser(['show-performance-evaluation', 'manage-performance-evaluation']));

    Livewire::test(Dashboard::class)
        ->call('startAssignment')
        ->call('startAssignment')
        ->assertDispatched('performance-evaluation:open-assign');
});

it('hides the primary action and refuses it without the manage permission', function (): void {
    $this->actingAs(performanceUser(['show-performance-evaluation']));

    Livewire::test(Dashboard::class)
        ->assertDontSee(__('performance_evaluation::dashboard.panel.assign_form'))
        ->call('startAssignment')
        ->assertForbidden();
});

it('mounts the evaluations workspace with the assign form open on request', function (): void {
    $this->actingAs(performanceUser(['show-performance-evaluation', 'manage-performance-evaluation']));

    Livewire::test(OperationsWorkspace::class, ['tab' => 'evaluations', 'openAssign' => true])
        ->assertSet('showSideMenu', 'form-assign')
        ->assertSet('isSideModalOpen', true);

    Livewire::test(OperationsWorkspace::class, ['tab' => 'evaluations'])
        ->assertSet('showSideMenu', '');
});

it('groups the panel and the small-screen chips under the same titled sections', function (): void {
    $this->actingAs(performanceUser(['show-performance-evaluation']));

    $html = Livewire::test(Dashboard::class)->html();

    foreach (['kpi', 'evaluation', 'talent', 'insight'] as $group) {
        // once in the context panel, once as the chip-row label
        expect(substr_count($html, e(__('performance_evaluation::dashboard.nav_groups.'.$group))))->toBeGreaterThanOrEqual(2);
    }
});
