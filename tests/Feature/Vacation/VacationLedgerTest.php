<?php

use App\Models\Personnel;
use App\Models\PersonnelRank;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Structure;
use App\Models\Vacation;
use App\Models\VacationBalanceEntry;
use App\Models\VacationNorm;
use App\Models\VacationWorkYear;
use App\Modules\Vacation\Application\Services\LegacyVacationMigrator;
use App\Modules\Vacation\Application\Services\VacationSettings;
use App\Services\Vacation\Entitlement\RankedEntitlementStrategy;
use App\Services\Vacation\VacationBalanceService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * İş ili üzrə balans: birinci il üçün 6 ay qaydası (ƏM m.131.1), istifadənin ən köhnə iş ilindən
 * çıxılması, qalığın sonrakı illərə keçməsi (m.134–135), açılış qalığı, köhnə təqvim ili
 * balanslarının köçürülməsi və rütbəli heyətin dəyişməyən qaydası.
 */

beforeEach(function (): void {
    Setting::query()->where('name', VacationSettings::LEDGER_START)->update(['value' => '']);
    Carbon::setTestNow('2026-10-09 10:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

/**
 * @param  array<string, mixed>  $overrides
 */
function vlPersonnel(array $overrides = []): Personnel
{
    $structure = Structure::query()->create(['name' => 'Keşlə', 'shortname' => 'K']);
    $position = Position::query()->create(['id' => random_int(1000, 999999), 'name' => 'operator']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'VL'.Str::upper(Str::random(6)),
        'surname' => 'Məmmədov',
        'name' => 'Orxan',
        'patronymic' => 'Vüqar',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'email' => Str::lower(Str::random(8)).'@example.com',
        'mobile' => '994501112233',
        'nationality_id' => 1,
        'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
        'residental_address' => 'Main st',
        'education_degree_id' => 1,
        'work_norm_id' => 1,
        'structure_id' => $structure->id,
        'position_id' => $position->id,
        'join_work_date' => '2024-03-15',
        'added_by' => 1,
        'is_pending' => false,
        ...$overrides,
    ]));
}

function vlBalances(): VacationBalanceService
{
    return app(VacationBalanceService::class);
}

/**
 * @return array<int, int> sequence => remaining
 */
function vlRemaining(Personnel $personnel, string $on = '2026-10-09'): array
{
    return collect(vlBalances()->balanceOn($personnel->fresh(), CarbonImmutable::parse($on))['work_years'])
        ->mapWithKeys(fn (array $year): array => [$year['sequence'] => $year['remaining']])
        ->all();
}

it('makes the first work year usable only after six months', function (): void {
    $personnel = vlPersonnel(['join_work_date' => '2026-06-01']);

    $before = vlBalances()->balanceOn($personnel, CarbonImmutable::parse('2026-10-09'));
    expect($before['remaining'])->toBe(0)
        ->and($before['next_available_from'])->toBe('2026-12-01')
        ->and($before['work_years'][0])->toMatchArray(['total' => 21, 'available' => false, 'available_from' => '2026-12-01']);

    $after = vlBalances()->balanceOn($personnel, CarbonImmutable::parse('2026-12-01'));
    expect($after['remaining'])->toBe(21)
        ->and($after['next_available_from'])->toBeNull();
});

it('lets an employee under 18 use the first work year at once', function (): void {
    $personnel = vlPersonnel(['join_work_date' => '2026-06-01', 'birthdate' => '2009-01-01']);

    expect(vlBalances()->balanceOn($personnel, CarbonImmutable::parse('2026-07-01'))['remaining'])->toBe(35);
});

it('carries unused days over to the next work years', function (): void {
    $personnel = vlPersonnel(['join_work_date' => '2024-03-15']);

    // Work years 2024-03-15, 2025-03-15 and 2026-03-15 — nothing used, nothing lost.
    expect(vlRemaining($personnel))->toBe([1 => 21, 2 => 21, 3 => 21])
        ->and(vlBalances()->balanceOn($personnel, CarbonImmutable::parse('2026-10-09'))['remaining'])->toBe(63);
});

it('takes leave from the oldest work year first and spills over', function (): void {
    $personnel = vlPersonnel(['join_work_date' => '2024-03-15']);

    vlBalances()->consume($personnel, 2026, 25, 'order:1', CarbonImmutable::parse('2026-10-12'));

    expect(vlRemaining($personnel))->toBe([1 => 0, 2 => 17, 3 => 21])
        ->and(VacationBalanceEntry::query()->where('source', 'order:1')->sum('days'))->toBe(-25);

    // Reversal removes exactly what the order took.
    vlBalances()->release($personnel, 2026, 25, 'order:1');
    expect(vlRemaining($personnel))->toBe([1 => 21, 2 => 21, 3 => 21]);
});

it('takes leave from the work year the order names first', function (): void {
    $personnel = vlPersonnel(['join_work_date' => '2024-03-15']);

    vlBalances()->consume($personnel, 2026, 10, 'order:2', CarbonImmutable::parse('2026-10-12'), CarbonImmutable::parse('2026-03-15'));

    expect(vlRemaining($personnel))->toBe([1 => 21, 2 => 21, 3 => 11]);
});

it('never draws on a work year that has not started yet', function (): void {
    $personnel = vlPersonnel(['join_work_date' => '2024-03-15']);

    // A leave starting in work year 2 cannot use work year 3 (ƏM m.113.3).
    $balance = vlBalances()->balanceOn($personnel, CarbonImmutable::parse('2025-06-01'));

    expect(collect($balance['work_years'])->pluck('sequence')->all())->toBe([1, 2])
        ->and($balance['remaining'])->toBe(42);
});

it('returns recalled days to the work years the leave was taken from, latest part first', function (): void {
    $personnel = vlPersonnel(['join_work_date' => '2024-03-15']);
    vlBalances()->consume($personnel, 2026, 25, 'order:7', CarbonImmutable::parse('2026-10-12'));

    // Taken: 21 from work year 1, 4 from work year 2. Six come back: 4 to year 2, 2 to year 1.
    vlBalances()->returnDays($personnel, 6, 'order_recall:8', 'order:7', CarbonImmutable::parse('2026-10-12'));

    expect(vlRemaining($personnel))->toBe([1 => 2, 2 => 21, 3 => 21]);

    vlBalances()->reverseSource('order_recall:8');
    expect(vlRemaining($personnel))->toBe([1 => 0, 2 => 17, 3 => 21]);
});

it('only computes work years from the ledger start; earlier ones come from opening balances', function (): void {
    Setting::query()->where('name', VacationSettings::LEDGER_START)->update(['value' => '2026-01-01']);
    $personnel = vlPersonnel(['join_work_date' => '2018-03-15']);

    // Only the work year in progress on the ledger start (2025-03-15) and later ones.
    expect(array_keys(vlRemaining($personnel)))->toBe([8, 9]);

    $entry = vlBalances()->addOpening($personnel, 6, 12, 'köhnə uçot', null);

    expect(vlRemaining($personnel))->toBe([6 => 12, 8 => 23, 9 => 23])
        ->and(VacationWorkYear::query()->where('sequence', 6)->value('strategy'))->toBe('opening');

    // Oldest first: the opening balance goes before the computed years.
    vlBalances()->consume($personnel, 2026, 15, 'order:3', CarbonImmutable::parse('2026-10-12'));
    expect(vlRemaining($personnel))->toBe([6 => 0, 8 => 20, 9 => 23]);

    vlBalances()->reverseSource('order:3');
    vlBalances()->deleteOpening($personnel, $entry->id);

    expect(vlRemaining($personnel))->toBe([8 => 23, 9 => 23])
        ->and(VacationWorkYear::query()->where('sequence', 6)->exists())->toBeFalse();
});

it('refuses an opening balance outside 1–366 days', function (): void {
    $personnel = vlPersonnel();

    expect(fn () => vlBalances()->addOpening($personnel, 1, 0))->toThrow(DomainException::class);
});

it('freezes the entitlement of a finished work year and keeps the current one live', function (): void {
    $personnel = vlPersonnel(['join_work_date' => '2024-03-15']);
    vlBalances()->materialize($personnel, Carbon::now());

    // A collective agreement raises base leave: only the work year in progress follows.
    VacationNorm::query()->where('group', 'base')->where('scope', 'all')->update(['days' => 24]);

    expect(collect(vlBalances()->workYears($personnel))->map(fn ($b) => $b->entitled)->all())->toBe([21, 21, 24]);
});

it('moves legacy calendar-year balances into the work-year ledger and rolls them back', function (): void {
    Setting::query()->where('name', VacationSettings::LEDGER_START)->update(['value' => '2026-10-09']);
    $personnel = vlPersonnel(['join_work_date' => '2020-05-10']);
    $old = Vacation::query()->create(['tabel_no' => $personnel->tabel_no, 'year' => 2025, 'vacation_days_total' => 30, 'remaining_days' => 4]);
    $latest = Vacation::query()->create(['tabel_no' => $personnel->tabel_no, 'year' => 2026, 'vacation_days_total' => 34, 'remaining_days' => 12, 'reserved_date_month' => 7]);

    $result = app(LegacyVacationMigrator::class)->migrate();

    expect($result)->toMatchArray(['employees' => 1, 'migrated' => 1])
        ->and(Vacation::query()->whereNull('migrated_at')->count())->toBe(0);

    $year = VacationWorkYear::query()->sole();
    expect($year->strategy)->toBe('legacy')
        ->and($year->starts_on->toDateString())->toBe('2026-05-10')
        ->and($year->entitled_days)->toBe(34)
        ->and($year->reserved_month)->toBe(7)
        ->and($year->legacy_vacation_id)->toBe($latest->id)
        ->and(vlRemaining($personnel))->toBe([7 => 12]);

    // Idempotent: nothing left to migrate.
    expect(app(LegacyVacationMigrator::class)->migrate()['employees'])->toBe(0);

    app(LegacyVacationMigrator::class)->rollback();

    expect(VacationWorkYear::query()->count())->toBe(0)
        ->and(VacationBalanceEntry::query()->count())->toBe(0)
        ->and(Vacation::query()->whereNotNull('migrated_at')->count())->toBe(0)
        ->and($old->fresh()->remaining_days)->toBe(4);
});

it('the legacy migration and its rollback run as a migration', function (): void {
    $personnel = vlPersonnel(['join_work_date' => '2020-05-10']);
    Vacation::query()->create(['tabel_no' => $personnel->tabel_no, 'year' => 2026, 'vacation_days_total' => 30, 'remaining_days' => 9]);

    $migration = require base_path('app/Modules/Vacation/Database/Migrations/2026_10_09_160400_migrate_legacy_vacation_balances.php');
    $migration->up();

    expect(VacationWorkYear::query()->where('strategy', 'legacy')->count())->toBe(1);

    $migration->down();

    expect(VacationWorkYear::query()->count())->toBe(0)
        ->and(Setting::query()->where('name', VacationSettings::LEDGER_START)->exists())->toBeFalse();
});

it('keeps the ranked-staff entitlement unchanged', function (): void {
    DB::table('rank_categories')->insertOrIgnore([
        ['id' => 30, 'name' => 'Zabit', 'vacation_days_count' => 30, 'contract_duration' => 1, 'vacation_days_per_month' => 2.5],
    ]);
    DB::table('ranks')->insertOrIgnore(['id' => 80, 'name_az' => 'Leytenant', 'is_active' => true, 'rank_category_id' => 30]);

    $officer = vlPersonnel(['join_work_date' => '2026-04-01']);
    PersonnelRank::query()->create(['tabel_no' => $officer->tabel_no, 'rank_id' => 80, 'name' => 'Leytenant', 'given_date' => '2026-04-01']);
    $officer = $officer->fresh();

    expect(vlBalances()->strategyFor($officer))->toBeInstanceOf(RankedEntitlementStrategy::class)
        // Before a year: 2.5 a month (6 months → 15), after: the category's 30 days — as before.
        ->and(vlBalances()->entitlementDays($officer, Carbon::parse('2026-10-09')))->toBe(15)
        ->and(vlBalances()->entitlementDays($officer, Carbon::parse('2027-05-01')))->toBe(30)
        // No six-month wait for ranked staff.
        ->and(vlBalances()->balanceOn($officer, CarbonImmutable::parse('2026-10-09'))['remaining'])->toBe(15);
});
