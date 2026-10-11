<?php

use App\Modules\Reports\Application\Services\DynamicReportBuilderService;
use App\Modules\Reports\Application\Services\StandardReportCatalogService;
use App\Modules\Reports\Support\ReportPermissions;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Rol matrisi tək mənbədir: kataloqa əlavə olunan hər hesabat və dinamik mənbə bir qrupa
 * bağlanmalıdır — əks halda heç kimə görünməz (fail closed), bu test isə unudulmanı tutur.
 */
it('maps every standard report in the catalog to a report group', function (): void {
    foreach (app(StandardReportCatalogService::class)->all() as $report) {
        expect(ReportPermissions::groupOfStandardReport($report['key']))
            ->not->toBeNull()
            ->and(ReportPermissions::GROUPS)->toHaveKey(ReportPermissions::groupOfStandardReport($report['key']));
    }
});

it('maps every dynamic source to a report group', function (): void {
    foreach (app(DynamicReportBuilderService::class)->sourceOptions() as $source) {
        expect(ReportPermissions::groupOfDynamicSource($source['key']))->not->toBeNull();
    }
});

it('maps every comparison card to a report group', function (): void {
    foreach (ReportPermissions::COMPARISON_GROUPS as $group) {
        expect(ReportPermissions::GROUPS)->toHaveKey($group);
    }
});

it('keeps payroll reports behind the payroll module permission', function (): void {
    expect(ReportPermissions::GROUP_PREREQUISITES[ReportPermissions::GROUP_PAYROLL] ?? [])->toContain('show-payroll');
});

it('lists every module permission exactly once', function (): void {
    $all = ReportPermissions::all();

    expect($all)->toHaveCount(count(array_unique($all)))
        ->and($all)->toContain('show-reports', 'export-reports', 'manage-report-presets', 'schedule-reports', 'show-payroll-reports');
});

it('returns no group for an unknown report or source', function (): void {
    expect(ReportPermissions::groupOfStandardReport('salary-leak'))->toBeNull()
        ->and(ReportPermissions::groupOfDynamicSource('users'))->toBeNull();
});
