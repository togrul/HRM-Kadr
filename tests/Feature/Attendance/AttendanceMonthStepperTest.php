<?php

use App\Models\User;
use App\Modules\Attendance\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('steps the attendance period month by month across a year boundary', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('show-attendance', 'web'));
    $this->actingAs($user);

    Livewire::withQueryParams(['year' => 2026, 'month' => 12])
        ->test(Dashboard::class)
        ->call('shiftMonth', 1)
        ->assertSet('year', 2027)
        ->assertSet('month', 1)
        ->call('shiftMonth', -1)
        ->call('shiftMonth', -1)
        ->assertSet('year', 2026)
        ->assertSet('month', 11);
});
