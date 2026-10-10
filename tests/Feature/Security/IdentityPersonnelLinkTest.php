<?php

use App\Models\UserPersonnelLink;
use App\Modules\PerformanceEvaluation\Livewire\TestWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\UserPersonnelLinks;
use App\Modules\Personnel\Livewire\MyHr\MyHrAccountProvisioning;
use Livewire\Livewire;

require_once __DIR__.'/Support/identity_fixtures.php';

/*
 * İstifadəçi ↔ əməkdaş bağı istifadəçi idarəçiliyidir: yalnız `manage-users` ilə,
 * özünə bağ qurmaq olmaz, hər dəyişiklik jurnala yazılır; şəxsi kabinet hesabı açan HR
 * bu yolla özündən geniş hüquqlu hesabı ələ keçirə bilməz.
 */

it('requires manage-users for the link screen — performance managers alone are refused', function (): void {
    $this->actingAs(identityUser(['manage-performance-evaluation', 'show-performance-evaluation']));

    Livewire::test(UserPersonnelLinks::class)->assertForbidden();
});

it('refuses to link the acting admin to an employee record', function (): void {
    $admin = identityUser(['manage-users']);
    $ceo = identityPersonnel('LK-CEO');
    $this->actingAs($admin);

    Livewire::test(UserPersonnelLinks::class)
        ->set('linkForm.user_id', $admin->id)
        ->set('linkForm.personnel_id', $ceo->id)
        ->call('saveLink')
        ->assertHasErrors('linkForm.user_id');

    $this->assertDatabaseMissing('user_personnel_links', ['user_id' => $admin->id]);
});

it('saves and removes links with an activity-log trail', function (): void {
    $admin = identityUser(['manage-users']);
    $employee = identityUser();
    $person = identityPersonnel('LK-EMP');
    $this->actingAs($admin);

    Livewire::test(UserPersonnelLinks::class)
        ->set('linkForm.user_id', $employee->id)
        ->set('linkForm.personnel_id', $person->id)
        ->call('saveLink')
        ->assertHasNoErrors();

    $link = UserPersonnelLink::query()->where('user_id', $employee->id)->firstOrFail();
    expect((int) $link->personnel_id)->toBe($person->id);

    Livewire::test(UserPersonnelLinks::class)->call('deleteLink', $link->id);

    $this->assertDatabaseMissing('user_personnel_links', ['id' => $link->id]);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'user_personnel_links', 'event' => 'created', 'causer_id' => $admin->id]);
    $this->assertDatabaseHas('activity_log', ['log_name' => 'user_personnel_links', 'event' => 'deleted', 'causer_id' => $admin->id]);
});

it('stops a self-service provisioner from taking over a more privileged account', function (): void {
    $hr = grantAllStructures(identityUser(['manage-my-hr-accounts']));
    $admin = identityUser(['access-settings', 'manage-users', 'manage-payroll'], ['email' => 'boss@example.test']);
    $person = identityPersonnel('LK-BOSS', ['email' => 'boss@example.test']);
    $this->actingAs($hr);

    Livewire::test(MyHrAccountProvisioning::class, ['personnelModel' => $person->id])
        ->set('manualLink.user_id', $admin->id)
        ->call('saveManualLink')
        ->assertHasErrors('manualLink.user_id');

    // Bağ artıq (başqa admin tərəfindən) qurulubsa, «hesab yarat» onun şifrə bərpa linkini vermir.
    identityLink($admin, $person);

    Livewire::test(MyHrAccountProvisioning::class, ['personnelModel' => $person->id])
        ->call('provision')
        ->assertHasErrors('provision')
        ->assertSet('resetUrl', null);

    expect($admin->fresh()->must_reset_password)->toBeFalse();
});

it('stops a self-service provisioner from linking their own account', function (): void {
    $hr = grantAllStructures(identityUser(['manage-my-hr-accounts']));
    $person = identityPersonnel('LK-SELF');
    $this->actingAs($hr);

    Livewire::test(MyHrAccountProvisioning::class, ['personnelModel' => $person->id])
        ->set('manualLink.user_id', $hr->id)
        ->call('saveManualLink')
        ->assertHasErrors('manualLink.user_id');

    $this->assertDatabaseMissing('user_personnel_links', ['user_id' => $hr->id]);
});

it('resolves the test-taker server-side; the old client-settable shortcut is gone', function (): void {
    $user = identityUser();
    $mine = identityPersonnel('LK-TEST');
    identityLink($user, $mine);
    $this->actingAs($user);

    $component = Livewire::test(TestWorkspace::class);

    expect(property_exists($component->instance(), 'resolvedPersonnelId'))->toBeFalse()
        ->and(property_exists($component->instance(), 'resolvedPersonnelLoaded'))->toBeFalse()
        ->and(fn () => $component->set('resolvedPersonnelId', 999))->toThrow(Exception::class)
        ->and((fn (): ?int => $this->currentPersonnelId())->call($component->instance()))->toBe($mine->id);
});
