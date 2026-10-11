<?php

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Structure;
use App\Models\User;
use App\Models\VacationNorm;
use App\Models\VacationWorkYear;
use App\Modules\Vacation\Application\Services\VacationNormCatalog;
use App\Modules\Vacation\Application\Services\VacationSettings;
use App\Modules\Vacation\Livewire\VacationNorms;
use App\Services\Vacation\VacationBalanceService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

/*
 * Staja görə əlavə məzuniyyət pillələri (ƏM m.116.1) — standart dəyər qanundakıdır, qurum yalnız
 * daha əlverişli dəyişə bilər (m.7.3, 24.2); dəyişiklik qüvvəyə minmə tarixindən sonra başlayan
 * iş illərinə tətbiq olunur (docs/vacation-legal-basis.md, (b)).
 */

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-11 10:00:00');
    Setting::query()->where('name', VacationSettings::LEDGER_START)->update(['value' => '']);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function vstPersonnel(string $join): Personnel
{
    $structure = Structure::query()->create(['name' => 'Keşlə', 'shortname' => 'K']);
    $position = Position::query()->create(['id' => random_int(1000, 999999), 'name' => 'operator']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'VS'.Str::upper(Str::random(6)),
        'surname' => 'Məmmədov',
        'name' => 'Rauf',
        'patronymic' => 'Elçin',
        'birthdate' => '1980-01-01',
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
        'join_work_date' => $join,
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

function vstSeniority(Personnel $personnel, string $on): int
{
    return app(VacationBalanceService::class)->entitlementBreakdown($personnel->fresh(), Carbon::parse($on))->seniority;
}

function vstTier(string $key): VacationNorm
{
    return VacationNorm::query()->where('statutory_key', $key)->whereNull('valid_to')->sole();
}

function vstAdmin(): User
{
    return grantAllStructures(User::factory()->create())->givePermissionTo(Permission::findOrCreate('access-admin', 'web'));
}

/** @return array<string, mixed> */
function vstForm(VacationNorm $norm, array $overrides = []): array
{
    return [
        'scope' => 'all',
        'min_value' => $norm->min_value,
        'max_value' => $norm->max_value,
        'min_inclusive' => $norm->min_inclusive,
        'days' => $norm->days,
        'valid_from' => '2026-10-11',
        'note' => null,
        ...$overrides,
    ];
}

it('counts 6 days once 15 years of service are complete by default (m.116.1)', function (): void {
    expect(vstTier('seniority_5')->only(['min_value', 'max_value', 'min_inclusive', 'days']))
        ->toBe(['min_value' => 5, 'max_value' => 10, 'min_inclusive' => true, 'days' => 2])
        ->and(vstTier('seniority_15')->only(['min_value', 'max_value', 'min_inclusive', 'days']))
        ->toBe(['min_value' => 15, 'max_value' => null, 'min_inclusive' => true, 'days' => 6]);

    // 16-cı iş ili (01.12.2026) düz 15 il stajla başlayır — «on ildən on beş ilədək» pilləsi
    // bitib, 6 gün (docs/vacation-legal-basis.md, (b-2)).
    expect(vstSeniority(vstPersonnel('2011-12-01'), '2026-12-15'))->toBe(6)
        // 15-ci iş ili 14 il stajla — 4 gün.
        ->and(vstSeniority(vstPersonnel('2012-12-01'), '2026-12-15'))->toBe(4)
        // Düz 10 il — 4 gün, düz 5 il — 2 gün.
        ->and(vstSeniority(vstPersonnel('2016-12-01'), '2026-12-15'))->toBe(4)
        ->and(vstSeniority(vstPersonnel('2021-12-01'), '2026-12-15'))->toBe(2);
});

/** Yeni standartı tətbiq edən miqrasiya (2026_10_11_190000). */
function vstFifteenMigration(): object
{
    return require base_path('app/Modules/Vacation/Database/Migrations/2026_10_11_190000_include_full_fifteen_years_in_seniority_leave.php');
}

it('moves a running system to the new 15-year rule only for work years starting from the update day', function (): void {
    // Yeniləmədən əvvəlki vəziyyət: qanuni pillə «15 ildən çox» (15 xaric).
    vstTier('seniority_15')->forceFill(['min_inclusive' => false])->save();

    $before = vstPersonnel('2011-06-01'); // 16-cı iş ili 01.06.2026-da (yeniləmədən əvvəl) başlayıb
    $after = vstPersonnel('2011-12-01');  // 16-cı iş ili 01.12.2026-da başlayacaq

    expect(vstSeniority($before, '2026-10-11'))->toBe(4)
        ->and(vstSeniority($after, '2026-12-15'))->toBe(4);

    $old = vstTier('seniority_15');
    vstFifteenMigration()->up();

    $next = vstTier('seniority_15');
    expect($old->fresh()->valid_to->toDateString())->toBe('2026-10-10')
        ->and($next->id)->not->toBe($old->id)
        ->and($next->only(['min_value', 'max_value', 'min_inclusive', 'days', 'is_statutory', 'legal_basis']))
        ->toBe(['min_value' => 15, 'max_value' => null, 'min_inclusive' => true, 'days' => 6, 'is_statutory' => true, 'legal_basis' => 'ƏM m.116.1'])
        ->and($next->valid_from->toDateString())->toBe('2026-10-11')
        ->and(app(VacationNormCatalog::class)->differsFromLaw($next))->toBeFalse();

    // Artıq başlamış iş ili köhnə versiya ilə qalır, yeniləmədən sonra başlayan — 6 gün.
    expect(vstSeniority($before, '2026-10-11'))->toBe(4)
        ->and(vstSeniority($after, '2026-12-15'))->toBe(6);

    // Geri qaytarma köhnə vəziyyəti bərpa edir.
    vstFifteenMigration()->down();
    expect(VacationNorm::query()->where('statutory_key', 'seniority_15')->count())->toBe(1)
        ->and(vstTier('seniority_15')->only(['id', 'min_inclusive']))->toBe(['id' => $old->id, 'min_inclusive' => false]);
});

it('leaves a 15-year tier the organisation already changed untouched', function (): void {
    vstPersonnel('2011-06-01');
    vstTier('seniority_15')->forceFill(['min_inclusive' => false, 'days' => 8])->save();

    vstFifteenMigration()->up();

    expect(VacationNorm::query()->where('statutory_key', 'seniority_15')->count())->toBe(1)
        ->and(vstTier('seniority_15')->only(['min_inclusive', 'days', 'valid_from']))->toBe(['min_inclusive' => false, 'days' => 8, 'valid_from' => null]);
});

it('changes the 15-year tier in place on a fresh install without employees', function (): void {
    vstTier('seniority_15')->forceFill(['min_inclusive' => false])->save();

    vstFifteenMigration()->up();

    expect(VacationNorm::query()->where('statutory_key', 'seniority_15')->count())->toBe(1)
        ->and(vstTier('seniority_15')->only(['min_inclusive', 'valid_from']))->toBe(['min_inclusive' => true, 'valid_from' => null]);
});

it('refuses tiers that give fewer days than the law at any length of service', function (array $overrides, string $key): void {
    $statutory = vstTier($key);

    expect(fn () => app(VacationNormCatalog::class)->save('seniority', vstForm($statutory, $overrides), $statutory->id))
        ->toThrow(DomainException::class);

    expect(VacationNorm::query()->where('group', 'seniority')->count())->toBe(3)
        ->and($statutory->fresh()->valid_to)->toBeNull();
})->with([
    'fewer days over 15 years' => [['days' => 5], 'seniority_15'],
    'exactly 15 years excluded' => [['min_inclusive' => false], 'seniority_15'],
    'higher 15-year bound' => [['min_value' => 16], 'seniority_15'],
    'higher 5-year bound' => [['min_value' => 6], 'seniority_5'],
    'exactly 5 years excluded' => [['min_inclusive' => false], 'seniority_5'],
    'band shortened below 10 years' => [['max_value' => 9], 'seniority_5'],
]);

it('accepts more favourable values: a lower bound, more days, an added band', function (): void {
    $catalog = app(VacationNormCatalog::class);
    $five = vstTier('seniority_5');

    $lower = $catalog->save('seniority', vstForm($five, ['min_value' => 3, 'days' => 3]), $five->id);
    $extra = $catalog->save('seniority', ['scope' => 'all', 'min_value' => 20, 'max_value' => null, 'min_inclusive' => true, 'days' => 8, 'valid_from' => '2026-11-01']);

    expect($lower->only(['min_value', 'days']))->toBe(['min_value' => 3, 'days' => 3])
        ->and($extra->valid_from->toDateString())->toBe('2026-11-01');

    // 3 il staj ilə 01.12.2026-da başlayan iş ili.
    expect(vstSeniority(vstPersonnel('2023-12-01'), '2026-12-15'))->toBe(3)
        // 20 il staj ilə 01.12.2026-da başlayan iş ili.
        ->and(vstSeniority(vstPersonnel('2006-12-01'), '2026-12-15'))->toBe(8);
});

it('does not change finished or current work years after a tier change', function (): void {
    $personnel = vstPersonnel('2010-03-01');
    $balances = app(VacationBalanceService::class);
    $balances->materialize($personnel->fresh(), Carbon::parse('2026-10-11'));

    $snapshot = fn (): array => VacationWorkYear::query()->where('tabel_no', $personnel->tabel_no)
        ->orderBy('sequence')->get()->mapWithKeys(fn (VacationWorkYear $row): array => [$row->sequence => $row->entitled_days])->all();
    $before = $snapshot();

    // 17-ci iş ili (01.03.2026–28.02.2027) 16 il stajla davam edir: 21 + 6.
    expect($before[17])->toBe(27);

    $fifteen = vstTier('seniority_15');
    app(VacationNormCatalog::class)->save('seniority', vstForm($fifteen, ['days' => 9]), $fifteen->id);

    $balances->recalculate($personnel->fresh());
    $balances->materialize($personnel->fresh(), Carbon::parse('2026-10-11'));

    expect($snapshot())->toBe($before);

    // Növbəti iş ili (01.03.2027) yeni dəyərlə.
    expect(vstSeniority($personnel, '2027-03-15'))->toBe(9);
});

it('refuses an effective date in the past and keeps tiers on through the screen, with an audit trail', function (): void {
    $this->actingAs(vstAdmin());
    $fifteen = vstTier('seniority_15');

    Livewire::test(VacationNorms::class)
        ->call('selectGroup', 'seniority')
        ->assertSee(__('vacation::norms.warnings.seniority_law'))
        ->call('openCrud', $fifteen->id)
        ->assertSet('form.valid_from', '2026-10-11')
        ->set('form.valid_from', '2026-10-01')
        ->set('form.days', 7)
        ->call('store')
        ->assertHasErrors(['form.valid_from'])
        ->call('toggle', $fifteen->id)
        ->assertDispatched('notify', type: 'error');

    expect($fifteen->fresh()->only(['days', 'is_active', 'valid_to']))
        ->toBe(['days' => 6, 'is_active' => true, 'valid_to' => null]);

    Livewire::test(VacationNorms::class)
        ->call('selectGroup', 'seniority')
        ->call('openCrud', $fifteen->id)
        ->set('form.days', 7)
        ->call('store')
        ->assertHasNoErrors();

    $current = vstTier('seniority_15');
    expect($current->days)->toBe(7)
        ->and(Activity::query()->where('log_name', 'vacation_norm')->where('subject_id', $current->id)->where('event', 'created')->exists())->toBeTrue()
        ->and(Activity::query()->where('log_name', 'vacation_norm')->where('subject_id', $fifteen->id)->where('event', 'updated')->exists())->toBeTrue();

    // Qanuni dəyərə qaytarma: yeni versiya gələcək tarixli olmadığından eyni gün yerində dəyişir.
    Livewire::test(VacationNorms::class)->call('restoreLaw', $current->id)->assertDispatched('notify', type: 'success');
    expect(vstTier('seniority_15')->only(['days', 'min_inclusive']))->toBe(['days' => 6, 'min_inclusive' => true]);
});

it('closes an organisation band in force from today instead of deleting it', function (): void {
    $this->actingAs(vstAdmin());
    $band = VacationNorm::query()->create(['group' => 'seniority', 'scope' => 'all', 'min_value' => 3, 'max_value' => 5, 'min_inclusive' => true, 'days' => 1, 'valid_from' => '2026-01-01']);

    Livewire::test(VacationNorms::class)
        ->call('selectGroup', 'seniority')
        ->call('confirmDelete', $band->id)
        ->call('delete');

    expect($band->fresh()->valid_to->toDateString())->toBe('2026-10-10')
        // 01.03.2026-da 3 il stajla başlamış iş ili pilləni saxlayır.
        ->and(vstSeniority(vstPersonnel('2023-03-01'), '2026-10-11'))->toBe(1)
        // 01.12.2026-da başlayan iş ili artıq almır.
        ->and(vstSeniority(vstPersonnel('2023-12-01'), '2026-12-15'))->toBe(0);
});
