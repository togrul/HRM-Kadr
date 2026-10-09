<?php

use App\Models\PersonnelChangePolicy;
use App\Models\User;
use App\Modules\Personnel\Contracts\ManagesPersonnelChangePolicy;
use App\Modules\Personnel\Contracts\PersonnelChangeMode;
use App\Modules\Services\Livewire\Service;
use App\Modules\Services\Livewire\Settings\ChangePolicySettings;
use App\Modules\SidebarStructure\Livewire\Services;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * Admin → Tənzimləmələr → «Dəyişiklik siyasəti»: icazə ilə açılır, rejim dəyişikliyi və
 * ilkinə qaytarma saxlanılır və audit jurnalına düşür; hazırlanmış sorğu yanlış qrup/rejim yaza bilmir.
 */

function changePolicyAdmin(bool $canManage = true): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('access-settings', 'web'));
    if ($canManage) {
        $user->givePermissionTo(Permission::findOrCreate('manage-change-policy', 'web'));
    }
    test()->actingAs($user);

    return $user;
}

it('is closed without the manage-change-policy permission and hidden from the settings menu', function (): void {
    changePolicyAdmin(canManage: false);

    Livewire::test(ChangePolicySettings::class)->assertForbidden();
    expect(collect(Livewire::test(Services::class)->instance()->sections())->pluck('key'))->not->toContain('change-policy');
});

it('lists every group with its mode and source', function (): void {
    changePolicyAdmin();
    app()->setLocale('az');

    expect(collect(Livewire::test(Services::class)->instance()->sections())->pluck('key'))->toContain('change-policy');

    Livewire::withQueryParams(['selectedService' => 'change-policy'])->test(Service::class)
        ->assertSee(__('services::settings.change_policy.title'));

    Livewire::test(ChangePolicySettings::class)
        ->assertSee('Struktur bölmə və vəzifə')
        ->assertSee('Əmək haqqı')
        ->assertSee('Ailə üzvləri')
        ->assertSee(__('services::settings.change_policy.sources.default'))
        ->assertDontSee(__('services::settings.change_policy.sources.customized'))
        ->assertSet('modes.surname', 'order')
        ->assertSet('modes.contact', 'free');
});

it('persists a mode change and writes it to the audit log', function (): void {
    $user = changePolicyAdmin();

    Livewire::test(ChangePolicySettings::class)
        ->set('modes.surname', 'journal')
        ->assertHasNoErrors()
        ->assertDispatched('settingsUpdated')
        ->assertSee(__('services::settings.change_policy.sources.customized'));

    $row = PersonnelChangePolicy::query()->where('field_group', 'surname')->sole();
    $entry = Activity::query()->where('log_name', 'personnel_change_policy')->sole();

    expect($row->mode)->toBe('journal')
        ->and((int) $row->updated_by)->toBe($user->id)
        ->and(app(ManagesPersonnelChangePolicy::class)->modeFor('surname'))->toBe(PersonnelChangeMode::Journal)
        ->and($entry->event)->toBe('updated')
        ->and($entry->properties['old'])->toBe(['mode' => 'order'])
        ->and($entry->properties['attributes'])->toBe(['mode' => 'journal'])
        ->and((int) $entry->causer_id)->toBe($user->id);
});

it('resets a group to its default mode and audits the reset', function (): void {
    changePolicyAdmin();
    app(ManagesPersonnelChangePolicy::class)->setMode('contact', 'order');

    Livewire::test(ChangePolicySettings::class)
        ->assertSet('modes.contact', 'order')
        ->call('resetGroup', 'contact')
        ->assertSet('modes.contact', 'free');

    expect(PersonnelChangePolicy::query()->where('field_group', 'contact')->exists())->toBeFalse()
        ->and(Activity::query()->where('log_name', 'personnel_change_policy')->where('event', 'reset')->count())->toBe(1);
});

it('treats choosing the default mode as a reset', function (): void {
    changePolicyAdmin();
    app(ManagesPersonnelChangePolicy::class)->setMode('surname', 'free');

    Livewire::test(ChangePolicySettings::class)->set('modes.surname', 'order');

    expect(PersonnelChangePolicy::query()->count())->toBe(0);
});

it('rejects a crafted unknown group or mode', function (): void {
    changePolicyAdmin();

    Livewire::test(ChangePolicySettings::class)
        ->set('modes.surname', 'anything')
        ->assertHasErrors(['modes.surname'])
        ->assertSet('modes.surname', 'order')
        ->set('modes.salary_bonus', 'free')
        ->assertHasErrors(['modes.salary_bonus'])
        ->call('resetGroup', 'nope')
        ->assertHasErrors(['modes.nope']);

    expect(PersonnelChangePolicy::query()->count())->toBe(0);
});

it('grants the new permission to Admin and HR Admin on existing installs', function (): void {
    $admin = Role::findOrCreate('Admin', 'web');
    $hrAdmin = Role::findOrCreate('HR Admin', 'web');
    $viewer = Role::findOrCreate('Viewer', 'web');
    DB::table('permissions')->where('name', 'manage-change-policy')->delete();

    $migration = require base_path('app/Modules/Personnel/Database/Migrations/2026_10_10_100100_add_manage_change_policy_permission.php');
    $migration->up();

    expect($admin->fresh()->hasPermissionTo('manage-change-policy'))->toBeTrue()
        ->and($hrAdmin->fresh()->hasPermissionTo('manage-change-policy'))->toBeTrue()
        ->and($viewer->fresh()->hasPermissionTo('manage-change-policy'))->toBeFalse();
});
