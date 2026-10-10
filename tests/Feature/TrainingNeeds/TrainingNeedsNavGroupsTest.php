<?php

use App\Models\User;
use App\Modules\TrainingNeeds\Livewire\Dashboard;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('groups the training tabs into titled sections in the panel and the chip row', function (): void {
    $user = User::factory()->create();
    grantAllStructures($user);
    $user->givePermissionTo(Permission::findOrCreate('show-training-needs', 'web'));
    $this->actingAs($user);

    $html = Livewire::test(Dashboard::class)->html();

    foreach (['foundation', 'needs', 'delivery', 'insight'] as $group) {
        // once as the panel section title, once as the small-screen chip-row label
        expect(substr_count($html, e(__('training_needs::dashboard.nav_groups.'.$group))))->toBe(2);
    }
});
