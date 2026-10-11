<?php

use App\Models\User;
use App\Modules\Reports\Application\Services\ReportsAccessService;
use App\Modules\Reports\Domain\Models\ReportPreset;
use App\Modules\Reports\Policies\ReportPresetPolicy;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Policy qərarları bazasız yoxlanılır: icazələr ReportsAccessService-dən gəlir.
 */
function presetPolicy(bool $canView, bool $canShare): ReportPresetPolicy
{
    $access = Mockery::mock(ReportsAccessService::class);
    $access->shouldReceive('canView')->andReturn($canView);
    $access->shouldReceive('canManageSharedPresets')->andReturn($canShare);

    return new ReportPresetPolicy($access);
}

function presetUser(int $id): User
{
    return (new User)->forceFill(['id' => $id]);
}

function preset(int $ownerId, bool $shared): ReportPreset
{
    return (new ReportPreset)->forceFill(['user_id' => $ownerId, 'is_shared' => $shared, 'tab' => 'standard', 'filters' => []]);
}

it('lets only the owner see and manage a personal preset', function (): void {
    $policy = presetPolicy(canView: true, canShare: false);

    expect($policy->view(presetUser(1), preset(1, false)))->toBeTrue()
        ->and($policy->update(presetUser(1), preset(1, false)))->toBeTrue()
        ->and($policy->delete(presetUser(1), preset(1, false)))->toBeTrue()
        ->and($policy->view(presetUser(2), preset(1, false)))->toBeFalse()
        ->and($policy->update(presetUser(2), preset(1, false)))->toBeFalse()
        ->and($policy->delete(presetUser(2), preset(1, false)))->toBeFalse();
});

it('shows a shared preset to every report user but lets only managers change it', function (): void {
    $viewer = presetPolicy(canView: true, canShare: false);
    $manager = presetPolicy(canView: true, canShare: true);

    expect($viewer->view(presetUser(2), preset(1, true)))->toBeTrue()
        ->and($viewer->update(presetUser(2), preset(1, true)))->toBeFalse()
        ->and($viewer->delete(presetUser(2), preset(1, true)))->toBeFalse()
        ->and($manager->update(presetUser(2), preset(1, true)))->toBeTrue()
        ->and($manager->delete(presetUser(2), preset(1, true)))->toBeTrue();
});

it('does not let an owner who lost the share permission change a shared preset', function (): void {
    $policy = presetPolicy(canView: true, canShare: false);

    expect($policy->update(presetUser(1), preset(1, true)))->toBeFalse()
        ->and($policy->delete(presetUser(1), preset(1, true)))->toBeTrue();
});

it('denies everything without the reports module permission', function (): void {
    $policy = presetPolicy(canView: false, canShare: true);

    expect($policy->viewAny(presetUser(1)))->toBeFalse()
        ->and($policy->create(presetUser(1)))->toBeFalse()
        ->and($policy->view(presetUser(1), preset(1, false)))->toBeFalse()
        ->and($policy->update(presetUser(1), preset(1, false)))->toBeFalse()
        ->and($policy->delete(presetUser(1), preset(1, false)))->toBeFalse();
});

it('requires the manage permission to share', function (): void {
    expect(presetPolicy(true, false)->share(presetUser(1)))->toBeFalse()
        ->and(presetPolicy(true, true)->share(presetUser(1)))->toBeTrue();
});
