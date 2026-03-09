<?php

use App\Support\Permissions\PermissionTranslationKey;
use Tests\TestCase;

uses(TestCase::class);

it('resolves services permission group labels from canonical permission names', function () {
    app()->setLocale('az');

    expect(PermissionTranslationKey::groupKeyFromPermission('show-attendance-manual'))
        ->toBe('attendance_manual')
        ->and(PermissionTranslationKey::groupKeyFromPermission('show-order-template-versions'))
        ->toBe('order_template_versions')
        ->and(PermissionTranslationKey::groupKeyFromPermission('show-personnels'))
        ->toBe('personnels');

    expect(__('services::permissions.groups.'.PermissionTranslationKey::groupKeyFromPermission('show-attendance-manual')))
        ->toBe('Davamiyyət - manual giriş');

    expect(__('services::permissions.groups.'.PermissionTranslationKey::groupKeyFromPermission('manage-attendance-settings')))
        ->toBe('Davamiyyət - tənzimləmələr');

    expect(__('services::permissions.groups.'.PermissionTranslationKey::groupKeyFromPermission('show-personnels')))
        ->toBe('Şəxsi heyət');
});

it('resolves services permission method labels from canonical keys', function () {
    app()->setLocale('az');

    expect(PermissionTranslationKey::methodKeyFromPermission('show-attendance'))->toBe('show')
        ->and(PermissionTranslationKey::methodKeyFromPermission('access-admin'))->toBe('access')
        ->and(PermissionTranslationKey::methodKeyFromPermission('confirmation-general'))->toBe('confirmation');

    expect(__('services::permissions.methods.show'))->toBe('Baxış')
        ->and(__('services::permissions.methods.add'))->toBe('Əlavə et')
        ->and(__('services::permissions.methods.edit'))->toBe('Düzəliş')
        ->and(__('services::permissions.methods.delete'))->toBe('Sil')
        ->and(__('services::permissions.methods.approve'))->toBe('Təsdiq et')
        ->and(__('services::permissions.methods.access'))->toBe('Giriş')
        ->and(__('services::permissions.methods.confirmation'))->toBe('Təsdiqləmə');
});
