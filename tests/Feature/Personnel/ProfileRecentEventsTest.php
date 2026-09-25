<?php

use App\Models\User;
use App\Modules\Personnel\Livewire\Information;
use App\Modules\Personnel\Livewire\PersonnelProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    DB::table('personnels')->insert([
        'tabel_no' => 'T-900', 'surname' => 'Məmmədov', 'name' => 'Elçin', 'patronymic' => 'Rasim oğlu',
        'birthdate' => '1986-07-14', 'mobile' => '0500000001', 'nationality_id' => 1, 'pin' => 'PIN-900',
        'residental_address' => 'Baku', 'education_degree_id' => 1, 'structure_id' => 1, 'position_id' => 1,
        'join_work_date' => '2019-03-12', 'added_by' => 1, 'work_norm_id' => 1, 'is_pending' => 0,
    ]);

    $user = User::factory()->create();
    foreach (['show-personnels', 'edit-personnels', 'update-personnels'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $user->givePermissionTo(['show-personnels', 'edit-personnels', 'update-personnels']);
    $this->actingAs($user);
});

it('offers the latest timeline entries as an array the overview can list', function (): void {
    $personnelId = (int) DB::table('personnels')->value('id');

    $events = Livewire::test(PersonnelProfile::class, ['personnel' => $personnelId])->instance()->recentEvents;

    expect($events)->toBeArray()->and(count($events))->toBeLessThanOrEqual(6);
});

it('opens the information panel straight at the HR 360 tab', function (): void {
    Livewire::test(Information::class, ['personnelModel' => 'T-900', 'startAt' => 'employee-360'])
        ->assertSet('currentStep', 5);

    Livewire::test(Information::class, ['personnelModel' => 'T-900'])
        ->assertSet('currentStep', 0);
});
