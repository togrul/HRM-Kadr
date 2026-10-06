<?php

use App\Models\Setting;
use App\Models\User;
use App\Modules\Services\Livewire\Settings\SettingsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $admin = User::factory()->create();
    $admin->givePermissionTo(Permission::findOrCreate('access-settings', 'web'));
    $this->actingAs($admin);
});

it('rejects a non-numeric value for a numeric setting and keeps the stored one', function (): void {
    $setting = Setting::query()->create(['name' => 'Work coefficient', 'value' => '1.5', 'type' => 'double']);

    Livewire::test(SettingsList::class, ['section' => 'general'])
        ->set('setting.0.value', 'abc')
        ->assertHasErrors(['setting.0.value' => 'numeric']);

    expect($setting->fresh()->getRawOriginal('value'))->toBe('1.5');
});

it('saves a valid value for its type', function (): void {
    $setting = Setting::query()->create(['name' => 'Work coefficient', 'value' => '1.5', 'type' => 'double']);

    Livewire::test(SettingsList::class, ['section' => 'general'])
        ->set('setting.0.value', '2.25')
        ->assertHasNoErrors();

    expect($setting->fresh()->getRawOriginal('value'))->toBe('2.25');
});
