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

it('keeps the statutory tiers with the 15-year bound exclusive by default (m.116.1)', function (): void {
    expect(vstTier('seniority_5')->only(['min_value', 'max_value', 'min_inclusive', 'days']))
        ->toBe(['min_value' => 5, 'max_value' => 10, 'min_inclusive' => true, 'days' => 2])
        ->and(vstTier('seniority_15')->only(['min_value', 'max_value', 'min_inclusive', 'days']))
        ->toBe(['min_value' => 15, 'max_value' => null, 'min_inclusive' => false, 'days' => 6]);

    // 16-cı iş ili düz 15 il stajla başlayır — qanunun hərfi oxunuşu ilə hələ 4 gün.
    expect(vstSeniority(vstPersonnel('2011-12-01'), '2026-12-15'))->toBe(4)
        // 17-ci iş ili 16 il stajla — 6 gün.
        ->and(vstSeniority(vstPersonnel('2010-12-01'), '2026-12-15'))->toBe(6);
});

it('applies an organisation\'s inclusive 15-year bound only to work years starting from its effective date', function (): void {
    $before = vstPersonnel('2011-06-01'); // 16-cı iş ili 01.06.2026-da (dəyişiklikdən əvvəl) başlayıb
    $after = vstPersonnel('2011-12-01');  // 16-cı iş ili 01.12.2026-da başlayacaq

    expect(vstSeniority($before, '2026-10-11'))->toBe(4);

    $statutory = vstTier('seniority_15');
    $catalog = app(VacationNormCatalog::class);
    $next = $catalog->save('seniority', vstForm($statutory, ['min_inclusive' => true]), $statutory->id);

    // Köhnə versiya dünəndən bağlanır, yeni versiya bu gündən — tarixçə saxlanılır.
    expect($statutory->fresh()->valid_to->toDateString())->toBe('2026-10-10')
        ->and($next->valid_from->toDateString())->toBe('2026-10-11')
        ->and($next->is_statutory)->toBeTrue()
        ->and($next->statutory_key)->toBe('seniority_15')
        ->and($catalog->differsFromLaw($next))->toBeTrue()
        ->and($catalog->describe($next))->toContain('15 il və daha çox');

    expect(vstSeniority($before, '2026-10-11'))->toBe(4)
        ->and(vstSeniority($after, '2026-12-15'))->toBe(6);
});

it('refuses tiers that give fewer days than the law at any length of service', function (array $overrides, string $key): void {
    $statutory = vstTier($key);

    expect(fn () => app(VacationNormCatalog::class)->save('seniority', vstForm($statutory, $overrides), $statutory->id))
        ->toThrow(DomainException::class);

    expect(VacationNorm::query()->where('group', 'seniority')->count())->toBe(3)
        ->and($statutory->fresh()->valid_to)->toBeNull();
})->with([
    'fewer days over 15 years' => [['days' => 5], 'seniority_15'],
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
        ->set('form.min_inclusive', true)
        ->call('store')
        ->assertHasErrors(['form.valid_from'])
        ->call('toggle', $fifteen->id)
        ->assertDispatched('notify', type: 'error');

    expect($fifteen->fresh()->only(['min_inclusive', 'is_active', 'valid_to']))
        ->toBe(['min_inclusive' => false, 'is_active' => true, 'valid_to' => null]);

    Livewire::test(VacationNorms::class)
        ->call('selectGroup', 'seniority')
        ->call('openCrud', $fifteen->id)
        ->set('form.min_inclusive', true)
        ->call('store')
        ->assertHasNoErrors();

    $current = vstTier('seniority_15');
    expect($current->min_inclusive)->toBeTrue()
        ->and(Activity::query()->where('log_name', 'vacation_norm')->where('subject_id', $current->id)->where('event', 'created')->exists())->toBeTrue()
        ->and(Activity::query()->where('log_name', 'vacation_norm')->where('subject_id', $fifteen->id)->where('event', 'updated')->exists())->toBeTrue();

    // Qanuni dəyərə qaytarma: yeni versiya gələcək tarixli olmadığından eyni gün yerində dəyişir.
    Livewire::test(VacationNorms::class)->call('restoreLaw', $current->id)->assertDispatched('notify', type: 'success');
    expect(vstTier('seniority_15')->min_inclusive)->toBeFalse();
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
