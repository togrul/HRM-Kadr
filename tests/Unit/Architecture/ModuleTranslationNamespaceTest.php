<?php

use App\Support\Translations\ModuleTranslation;
use Tests\TestCase;

uses(TestCase::class);

it('loads enabled module translations under canonical namespaces', function () {
    app()->setLocale('az');

    expect(ModuleTranslation::namespaceFromSlug('business-trips'))->toBe('business_trips')
        ->and(__('admin::languages.actions.add'))->toBe('Dil əlavə et')
        ->and(__('admin::leave_types.actions.add'))->toBe('Növ əlavə et')
        ->and(__('attendance::dashboard.title'))->toBe('Davamiyyət izləmə')
        ->and(__('attendance::month_close.actions.close_month'))->toBe('Ayı bağla')
        ->and(__('orders::templates_list.actions.add_template'))->toBe('Şablon əlavə et')
        ->and(__('services::permissions.groups.attendance_manual'))->toBe('Davamiyyət - manual giriş');
});
