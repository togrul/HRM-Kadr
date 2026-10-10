<?php

use App\Models\AttendanceCalendar;
use App\Models\AttendanceShift;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceDailyLedgerCalculatorService;
use App\Modules\Attendance\Application\Services\AttendancePuantajReadService;
use App\Modules\Attendance\Application\Services\AttendanceWorkNormService;
use App\Modules\Personnel\Application\Services\WorkingTimeNormService;
use App\Modules\Personnel\Contracts\WorkingTimeNormProvider;
use App\Modules\Personnel\Contracts\WorkingTimeProfile;
use App\Modules\Personnel\Livewire\EditPersonnel;
use App\Modules\Personnel\Services\PersonnelCrudBenchmarkFixtureService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * Qısaldılmış iş vaxtı (ƏM m.91.2): 16 yaşadək 24 saat, 16–18 yaş və 61–100% funksiya
 * pozulmasına görə əlillik 36 saat, digər hallar fərdi norma ilə; gündəlik norma =
 * həftəlik ÷ iş günləri (m.90.3); bayramqabağı 1 saat qısaltma tətbiq edilmir (m.108.1).
 * 2026-cı ilin oktyabrı: 22 iş günü, standart norma 176 saat.
 */

afterEach(fn () => Carbon::setTestNow());

function rwtPersonnel(string $tabelNo, array $attributes = []): Personnel
{
    $user = User::query()->first() ?? User::factory()->create();
    $country = Country::query()->first() ?? Country::query()->create(['id' => 1, 'code' => 'AZ']);
    EducationDegree::query()->firstOrCreate(['id' => 1], ['title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bakalavr']);
    WorkNorm::query()->firstOrCreate(['id' => 1], ['name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Polniy']);
    $structure = Structure::query()->first() ?? Structure::query()->create([
        'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1.10, 'code' => 10, 'level' => 1,
    ]);
    $position = Position::query()->first() ?? Position::query()->create(['id' => 1, 'name' => 'Officer']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => $tabelNo,
        'surname' => 'Norm',
        'name' => 'Test',
        'patronymic' => 'Case',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'mobile' => '994501112233',
        'nationality_id' => $country->id,
        'pin' => 'P'.$tabelNo,
        'residental_address' => 'Main st',
        'education_degree_id' => 1,
        'structure_id' => $structure->id,
        'position_id' => $position->id,
        'work_norm_id' => 1,
        'join_work_date' => '2026-01-01',
        'added_by' => $user->id,
        'is_pending' => false,
        ...$attributes,
    ]));
}

function rwtDisability(int $id, string $name): int
{
    DB::table('disabilities')->insertOrIgnore(['id' => $id, 'name' => $name]);

    return $id;
}

function rwtNorm(string $tabelNo): array
{
    return app(AttendanceWorkNormService::class)->employeeMonthNorm(2026, 10, $tabelNo);
}

it('keeps 176 hours for an adult without a reduced norm', function (): void {
    rwtPersonnel('RWT00');

    expect(rwtNorm('RWT00'))->toMatchArray([
        'workdays' => 22, 'daily_minutes' => 480, 'minutes' => 176 * 60, 'weekly_minutes' => 2400, 'reason' => null,
    ]);
});

it('gives an employee under 16 a 24-hour week: 4.8 h a day, 105.6 h in October 2026', function (): void {
    rwtPersonnel('RWT16', ['birthdate' => '2011-03-01']);

    expect(rwtNorm('RWT16'))->toMatchArray([
        'daily_minutes' => 288, 'minutes' => 22 * 288, 'weekly_minutes' => 24 * 60, 'reason' => 'under_16',
    ]);
    expect(22 * 288 / 60)->toBe(105.6);
});

it('gives a 16–18 year old a 36-hour week: 7.2 h a day, 158.4 h in October 2026', function (): void {
    rwtPersonnel('RWT17', ['birthdate' => '2009-05-01']);

    expect(rwtNorm('RWT17'))->toMatchArray([
        'daily_minutes' => 432, 'minutes' => 9504, 'weekly_minutes' => 36 * 60, 'reason' => 'under_18',
    ]);
    expect(9504 / 60)->toBe(158.4);
});

it('switches to the full day on the 18th birthday inside the month', function (): void {
    rwtPersonnel('RWTBD', ['birthdate' => '2008-10-15']);

    // 1–14 October: 10 workdays at 7.2 h; from the 15th: 12 workdays at 8 h.
    expect(rwtNorm('RWTBD')['minutes'])->toBe(10 * 432 + 12 * 480);
});

it('reduces the week for a group I/II (61–100%) disability from its date, not for group III', function (): void {
    rwtPersonnel('RWTD2', ['disability_id' => rwtDisability(2, 'II qrup əlillik'), 'disability_given_date' => '2025-02-01']);
    rwtPersonnel('RWTD3', ['disability_id' => rwtDisability(3, 'III qrup əlillik'), 'disability_given_date' => '2025-02-01']);
    rwtPersonnel('RWTDL', ['disability_id' => rwtDisability(4, '61-80% funksiya pozulması'), 'disability_given_date' => '2026-11-01']);

    expect(rwtNorm('RWTD2'))->toMatchArray(['minutes' => 9504, 'reason' => 'disability'])
        ->and(rwtNorm('RWTD3'))->toMatchArray(['minutes' => 176 * 60, 'reason' => null])
        ->and(rwtNorm('RWTDL'))->toMatchArray(['minutes' => 176 * 60, 'reason' => null]);
});

it('reads the disability degree from the catalogue name', function (string $name, bool $reduces): void {
    expect(WorkingTimeNormService::reducesWorkingTime($name))->toBe($reduces);
})->with([
    ['I qrup', true],
    ['II qrup əlillik', true],
    ['1-ci qrup', true],
    ['2-ci dərəcə', true],
    ['81–100 % pozulma', true],
    ['61-80%', true],
    ['III qrup', false],
    ['3-cü qrup', false],
    ['31-60%', false],
    ['Sağlamlıq imkanları məhdud', false],
]);

it('applies the explicit norm (pregnancy, child under 1.5, harmful work): 36 h → 158.4 h', function (): void {
    rwtPersonnel('RWTOV', ['weekly_hours_norm' => '36', 'working_time_type' => 'reduced']);

    expect(rwtNorm('RWTOV'))->toMatchArray(['daily_minutes' => 432, 'minutes' => 9504, 'reason' => 'override']);
});

it('lets the shortest applicable norm win', function (): void {
    rwtPersonnel('RWTMN', ['birthdate' => '2011-03-01', 'weekly_hours_norm' => '36']);

    expect(rwtNorm('RWTMN'))->toMatchArray(['weekly_minutes' => 24 * 60, 'reason' => 'under_16']);
});

it('divides the week by six on a six-day schedule (ƏM m.90.3: 36 h → 6 h a day)', function (): void {
    rwtPersonnel('RWT6D', ['weekly_hours_norm' => '36', 'work_schedule' => 'six_day']);

    $profile = app(WorkingTimeNormProvider::class)->profiles(['RWT6D'])['RWT6D'];

    expect($profile->dailyMinutesOn(Carbon::parse('2026-10-05')))->toBe(360);
});

it('does not shorten the day before a holiday for reduced working time, but does for a part-time week', function (): void {
    AttendanceCalendar::query()->create([
        'date' => '2026-10-15', 'day_type' => 'holiday', 'name' => 'Bayram',
        'is_paid' => true, 'scope_type' => 'global', 'scope_id' => null,
    ]);
    rwtPersonnel('RWTPH', ['birthdate' => '2009-05-01']);
    rwtPersonnel('RWTPT', ['weekly_hours_norm' => '30', 'working_time_type' => 'partial']);

    // 21 workdays; the reduced week keeps 7.2 h on the 14th (m.108.1)…
    expect(rwtNorm('RWTPH'))->toMatchArray(['workdays' => 21, 'pre_holidays' => 1, 'minutes' => 21 * 432]);
    // …the part-time week (m.94) is shortened by the hour like everyone else.
    expect(rwtNorm('RWTPT'))->toMatchArray(['daily_minutes' => 360, 'minutes' => 21 * 360 - 60]);
});

it('plans the reduced hours on past timesheet days without a fact', function (): void {
    Carbon::setTestNow('2026-10-08 10:00:00');
    $reduced = rwtPersonnel('RWTTS', ['birthdate' => '2009-05-01']);
    $standard = rwtPersonnel('RWTST');

    $defaults = app(AttendancePuantajReadService::class)->loadScheduleDefaults(
        [$reduced, $standard], Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'), [],
    );

    expect($defaults['RWTTS']['2026-10-05'])->toMatchArray(['attendance_status' => 'planned', 'scheduled_minutes' => 432, 'worked_minutes' => 432])
        ->and($defaults['RWTST']['2026-10-05']['scheduled_minutes'])->toBe(480)
        ->and(collect($defaults['RWTTS'])->sum('scheduled_minutes'))->toBe(5 * 432);
});

it('caps the ledger plan at the reduced norm and moves the shift end with it', function (): void {
    $shift = AttendanceShift::query()->create([
        'name' => 'Standart', 'start_time' => '09:00', 'end_time' => '18:00', 'break_minutes' => 60,
        'is_night_shift' => false, 'in_flex_before_minutes' => 0, 'in_flex_after_minutes' => 0,
        'out_flex_before_minutes' => 0, 'out_flex_after_minutes' => 0, 'is_active' => true,
    ]);
    $profile = new WorkingTimeProfile(overrideWeeklyMinutes: 36 * 60);
    $pairing = fn (string $in, string $out): array => [
        'worked_minutes' => 432, 'break_minutes' => 0, 'unmatched' => 0,
        'first_in_at' => $in, 'last_out_at' => $out, 'pairs' => [],
    ];
    $calculator = app(AttendanceDailyLedgerCalculatorService::class);

    $regular = $calculator->calculate(date: Carbon::parse('2026-10-13'), pairing: $pairing('2026-10-13 09:00:00', '2026-10-13 17:12:00'), shift: $shift, workingTime: $profile);
    $preHoliday = $calculator->calculate(date: Carbon::parse('2026-10-14'), pairing: $pairing('2026-10-14 09:00:00', '2026-10-14 17:12:00'), shift: $shift, isPreHoliday: true, workingTime: $profile);
    $standard = $calculator->calculate(date: Carbon::parse('2026-10-14'), pairing: $pairing('2026-10-14 09:00:00', '2026-10-14 17:12:00'), shift: $shift, isPreHoliday: true);

    expect($regular['scheduled_minutes'])->toBe(432)
        ->and($regular['early_leave_minutes'])->toBe(0)
        ->and($preHoliday['scheduled_minutes'])->toBe(432)
        ->and($preHoliday['meta']['pre_holiday'])->toBeFalse()
        ->and($standard['scheduled_minutes'])->toBe(420);
});

it('saves the weekly norm from the employee form and rejects more than 40 hours', function (): void {
    Role::findOrCreate('admin', 'web');
    Permission::findOrCreate('get-notification', 'web');
    $user = User::factory()->create();
    foreach (['add-personnels', 'edit-personnels'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    grantAllStructures($user);
    $personnel = app(PersonnelCrudBenchmarkFixtureService::class)->ensureEditablePersonnel($user);
    Livewire::actingAs($user);

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->set('personalForm.personnel.weekly_hours_norm', '41')
        ->call('store')
        ->assertHasErrors(['personalForm.personnel.weekly_hours_norm' => 'max']);

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->assertSee(__('personnel::common.labels.weekly_hours_norm'))
        ->set('personalForm.personnel.weekly_hours_norm', '36')
        ->call('store')
        ->assertHasNoErrors();

    expect((float) $personnel->fresh()->weekly_hours_norm)->toBe(36.0);

    Livewire::test(EditPersonnel::class, ['personnelModel' => $personnel->getKey()])
        ->set('personalForm.personnel.weekly_hours_norm', '')
        ->call('store')
        ->assertHasNoErrors();

    expect($personnel->fresh()->weekly_hours_norm)->toBeNull();
});
