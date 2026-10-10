<?php

use App\Models\Personnel;
use App\Models\User;
use App\Modules\Reports\Application\Services\ComparativeReportService;
use App\Modules\Reports\Application\Services\ReportsOverviewService;
use App\Modules\Reports\Application\Services\ReportsStructureScopeService;
use App\Modules\Reports\Application\Services\StandardReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * Hesabatlar: struktur filtri verilməyəndə nəticə istifadəçinin görünürlüyü ilə məhdudlaşır
 * (əvvəllər bütün təşkilat idi), görünürlükdən kənar structure_id heç nə açmır, «bütün
 * strukturlar» rolu isə bütün təşkilatı görür.
 */
function scopeRepPersonnel(string $tabelNo, int $structureId): Personnel
{
    $addedBy = User::query()->value('id') ?? User::factory()->create()->id;

    return Personnel::query()->create([
        'tabel_no' => $tabelNo,
        'surname' => 'Hesabat',
        'name' => 'Test',
        'patronymic' => 'Test',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'mobile' => '0500000000',
        'nationality_id' => 1,
        'pin' => substr(str_replace('-', '', $tabelNo).'12345', 0, 7),
        'residental_address' => 'Bakı',
        'education_degree_id' => 1,
        'structure_id' => $structureId,
        'position_id' => 1,
        'work_norm_id' => 1,
        'join_work_date' => '2024-01-01',
        'added_by' => $addedBy,
    ]);
}

function scopeRepActive(?int $structureId = null): int
{
    return (int) data_get(app(ReportsOverviewService::class)->build(2026, 3, $structureId), 'kpis.active_personnel_count');
}

beforeEach(function (): void {
    Role::findOrCreate('admin', 'web');
    Permission::findOrCreate('get-notification', 'web');

    DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
    DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
    DB::table('structures')->insertOrIgnore([
        ['id' => 1, 'name' => 'Daxili', 'shortname' => 'D'],
        ['id' => 2, 'name' => 'Kənar', 'shortname' => 'K'],
    ]);
    DB::table('positions')->insertOrIgnore(['id' => 1, 'name' => 'Analitik']);
    DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Full']);

    scopeRepPersonnel('RP-001', 1);
    scopeRepPersonnel('RP-002', 2);
    scopeRepPersonnel('RP-003', 2);
});

it('limits an unfiltered report to the user\'s structures', function (): void {
    $this->actingAs(grantStructures(User::factory()->create(), [1]));

    expect(scopeRepActive())->toBe(1)
        ->and(app(ReportsStructureScopeService::class)->resolveIds(null))->toBe([1]);

    $headcount = app(StandardReportService::class)->build('headcount', ['year' => 2026, 'month' => 3, 'structure_id' => null]);
    expect(collect($headcount['rows'] ?? [])->pluck('personnel_count', 'structure_name')->all())
        ->toBe(['Daxili' => 1]);
});

it('returns nothing for a structure outside the user\'s scope', function (): void {
    $this->actingAs(grantStructures(User::factory()->create(), [1]));

    expect(app(ReportsStructureScopeService::class)->resolveIds(2))->toBe([ReportsStructureScopeService::NOTHING])
        ->and(scopeRepActive(2))->toBe(0)
        ->and(data_get(app(ComparativeReportService::class)->build(2026, 3, 2), 'headcount_years.1.value'))->toBe(0);
});

it('shows nothing to a user whose roles grant no structure', function (): void {
    $this->actingAs(User::factory()->create());

    expect(scopeRepActive())->toBe(0)
        ->and(scopeRepActive(1))->toBe(0);
});

it('lets an all-structures role see the whole organisation', function (): void {
    $this->actingAs(grantAllStructures(User::factory()->create()));

    expect(app(ReportsStructureScopeService::class)->resolveIds(null))->toBe([])
        ->and(scopeRepActive())->toBe(3)
        ->and(scopeRepActive(2))->toBe(2);
});
