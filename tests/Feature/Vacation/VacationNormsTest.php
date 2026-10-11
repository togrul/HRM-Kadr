<?php

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Models\PersonnelKinship;
use App\Models\PersonnelLaborActivity;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Structure;
use App\Models\VacationNorm;
use App\Modules\Orders\Infrastructure\Document\Effects\TransferEffect;
use App\Modules\Vacation\Application\Services\VacationNormCatalog;
use App\Modules\Vacation\Application\Services\VacationSettings;
use App\Modules\Vacation\Application\Services\WorkYearCalendar;
use App\Services\Vacation\Entitlement\CivilEntitlementStrategy;
use App\Services\Vacation\OrderLeaveFacts;
use App\Services\Vacation\VacationBalanceService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Əmək məzuniyyəti normaları (ƏM m.114–117, 119, 136) — docs/vacation-legal-basis.md.
 * Hər qrup iş ilinin başlanğıcına görə qiymətləndirilir.
 */

beforeEach(function (): void {
    DB::table('kinships')->insertOrIgnore([
        ['id' => 23, 'name_az' => 'Oğul', 'is_active' => true],
        ['id' => 24, 'name_az' => 'Qız', 'is_active' => true],
        ['id' => 12, 'name_az' => 'Ana', 'is_active' => true],
    ]);
    // Hər iş ili hesablanır (uçotun başlama tarixi boş).
    Setting::query()->where('name', VacationSettings::LEDGER_START)->update(['value' => '']);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function vnPersonnel(array $overrides = []): Personnel
{
    $structure = Structure::query()->create(['name' => 'Keşlə', 'shortname' => 'K']);
    $position = Position::query()->create(['id' => random_int(1000, 999999), 'name' => 'operator']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'VN'.Str::upper(Str::random(6)),
        'surname' => 'Əliyeva',
        'name' => 'Leyla',
        'patronymic' => 'Rauf',
        'birthdate' => '1990-01-01',
        'gender' => 2,
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

function vnChild(Personnel $personnel, string $birthdate, bool $disabled = false, int $kinship = 24): void
{
    PersonnelKinship::query()->create([
        'tabel_no' => $personnel->tabel_no,
        'kinship_id' => $kinship,
        'fullname' => 'Uşaq '.Str::random(4),
        'birthdate' => $birthdate,
        'registered_address' => 'Bakı',
        'residental_address' => 'Bakı',
        'is_disabled' => $disabled,
    ]);
}

function vnPrevious(Personnel $personnel, string $from, string $to): void
{
    PersonnelLaborActivity::query()->create([
        'tabel_no' => $personnel->tabel_no,
        'company_name' => 'Əvvəlki iş',
        'position' => 'mütəxəssis',
        'join_date' => $from,
        'leave_date' => $to,
        'is_current' => false,
    ]);
}

/** The breakdown of the work year in progress on $on. */
function vnBreakdown(Personnel $personnel, string $on): array
{
    return app(VacationBalanceService::class)->entitlementBreakdown($personnel->fresh(), Carbon::parse($on))->toArray();
}

it('seeds the statutory defaults', function (): void {
    expect(VacationNorm::query()->where('is_statutory', true)->count())->toBe(12)
        ->and(VacationNorm::query()->where('group', 'base')->where('scope', 'all')->value('days'))->toBe(21)
        ->and(VacationNorm::query()->where('group', 'seniority')->orderBy('min_value')->pluck('days')->all())->toBe([2, 4, 6]);
});

it('gives 21 days base leave by default and the largest applicable base norm', function (): void {
    $personnel = vnPersonnel();

    expect(vnBreakdown($personnel, '2025-05-01'))->toMatchArray(['base' => 21, 'seniority' => 0, 'total' => 21]);

    // ƏM m.114.3: the position is in the 30-day list.
    VacationNorm::query()->create(['group' => 'base', 'scope' => 'position', 'position_id' => $personnel->position_id, 'days' => 30, 'legal_basis' => 'ƏM m.114.3']);
    // An employment contract granting more (m.145) — the largest wins.
    VacationNorm::query()->create(['group' => 'base', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 33]);

    expect(vnBreakdown($personnel, '2025-05-01')['base'])->toBe(33);
});

it('gives under-18 and disabled employees their own base leave without additions', function (): void {
    // 16–18 at the work year start: 35 days (m.119.1).
    $teen = vnPersonnel(['birthdate' => '2008-01-01', 'join_work_date' => '2025-02-01']);
    expect(vnBreakdown($teen, '2025-06-01'))->toMatchArray(['base' => 35, 'exclusive' => true, 'total' => 35]);

    // Under 16: 42 days.
    $young = vnPersonnel(['birthdate' => '2010-06-01', 'join_work_date' => '2025-02-01']);
    expect(vnBreakdown($young, '2025-06-01')['base'])->toBe(42);

    // Disability: 42 days (m.119.2); no seniority or children leave on top (m.116.3, 117.4).
    DB::table('disabilities')->insertOrIgnore(['id' => 1, 'name' => 'II qrup']);
    $disabled = vnPersonnel(['disability_id' => 1, 'join_work_date' => '2010-01-01']);
    vnChild($disabled, '2015-01-01');
    vnChild($disabled, '2017-01-01');

    expect(vnBreakdown($disabled, '2025-06-01'))->toMatchArray(['base' => 42, 'seniority' => 0, 'children' => 0, 'total' => 42]);
});

it('adds seniority leave by total length of service across employers', function (string $join, ?array $previous, int $days): void {
    $personnel = vnPersonnel(['join_work_date' => $join, 'gender' => 1]);

    if ($previous !== null) {
        vnPrevious($personnel, ...$previous);
    }

    // The work year in progress on 2025-06-01.
    expect(vnBreakdown($personnel, '2025-06-01')['seniority'])->toBe($days);
})->with([
    'under 5 years' => ['2021-01-01', null, 0],
    'exactly 5 years' => ['2020-01-01', null, 2],
    '9 years' => ['2016-01-01', null, 2],
    'exactly 10 years' => ['2015-01-01', null, 4],
    // m.116.1: «on ildən on beş ilədək» düz 15 ildə bitir (Məcəllədə «-dan … -dək» = [X, Y),
    // m.119.1) — 15 il tamam olanda 6 gün (docs/vacation-legal-basis.md, (b-2)).
    'exactly 15 years' => ['2010-01-01', null, 6],
    '14 years' => ['2011-01-01', null, 4],
    '15 years and a day' => ['2009-12-31', ['2009-12-29', '2009-12-30'], 6],
    'previous employer counts' => ['2023-01-01', ['2013-01-01', '2022-12-31'], 4],
    'over 15 years' => ['2009-01-01', null, 6],
]);

it('adds 2 days to a woman with two children under 14 and 5 with three', function (): void {
    $mother = vnPersonnel(['join_work_date' => '2024-01-01']);
    vnChild($mother, '2015-02-01');
    vnChild($mother, '2018-02-01', kinship: 23);

    expect(vnBreakdown($mother, '2025-06-01')['children'])->toBe(2);

    vnChild($mother, '2020-02-01');
    expect(vnBreakdown($mother, '2025-06-01')['children'])->toBe(5);

    // A man is not covered by the default rows…
    $father = vnPersonnel(['gender' => 1, 'join_work_date' => '2024-01-01']);
    vnChild($father, '2015-02-01');
    vnChild($father, '2018-02-01');
    expect(vnBreakdown($father, '2025-06-01')['children'])->toBe(0);

    // …unless he raises the children alone (m.117.2): a row for that employee.
    VacationNorm::query()->create(['group' => 'children', 'scope' => 'personnel', 'tabel_no' => $father->tabel_no, 'condition' => 'children_under_14', 'min_value' => 2, 'days' => 2]);
    expect(vnBreakdown($father, '2025-06-01')['children'])->toBe(2);
});

it('counts a child until the end of the calendar year they turn 14 and a disabled child under 18', function (): void {
    $mother = vnPersonnel(['join_work_date' => '2024-01-01']);
    vnChild($mother, '2011-03-01'); // turns 14 on 2025-03-01
    vnChild($mother, '2016-03-01');

    // Work year 2025-01-01: both under 14. Work year 2026-01-01: the elder turned 14 in 2025.
    expect(vnBreakdown($mother, '2025-06-01')['children'])->toBe(2)
        ->and(vnBreakdown($mother, '2026-06-01')['children'])->toBe(0);

    $parent = vnPersonnel(['join_work_date' => '2024-01-01']);
    vnChild($parent, '2010-01-01', disabled: true);
    expect(vnBreakdown($parent, '2025-06-01')['children'])->toBe(5);
});

it('adds working-conditions leave together with seniority leave', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2014-01-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $personnel->position_id, 'days' => 6, 'legal_basis' => 'ƏM m.115.1']);

    // 2025-01-01: 11 years of service → +4; conditions +6 for the full work year — both added (m.136.2).
    expect(vnBreakdown($personnel, '2025-12-31'))->toMatchArray(['base' => 21, 'seniority' => 4, 'conditions' => 6, 'total' => 31]);
    // NK 95, b.7: still in the conditions — the full days may be given in advance mid-year.
    expect(vnBreakdown($personnel, '2025-06-01')['conditions'])->toBe(6);
});

it('ignores inactive norms', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2014-01-01', 'gender' => 1]);
    VacationNorm::query()->where('group', 'seniority')->update(['is_active' => false]);

    expect(vnBreakdown($personnel, '2025-06-01')['seniority'])->toBe(0);
});

it('keeps work years on the hire-date anniversary and shifts them by excluded child-care leave', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2024-03-15']);
    $periods = app(VacationBalanceService::class)->periodsFor($personnel, CarbonImmutable::parse('2026-04-01'));

    expect(array_map(fn ($p) => [$p->start->toDateString(), $p->end->toDateString()], $periods))->toBe([
        ['2024-03-15', '2025-03-14'],
        ['2025-03-15', '2026-03-14'],
        ['2026-03-15', '2027-03-14'],
    ]);

    // ƏM m.132.2: partially paid child-care leave (m.127) is not part of the work year.
    $calendar = app(WorkYearCalendar::class);
    $shifted = $calendar->periods(CarbonImmutable::parse('2024-03-15'), CarbonImmutable::parse('2026-04-01'), [
        [CarbonImmutable::parse('2024-06-01'), CarbonImmutable::parse('2024-06-30')],
    ]);

    expect($shifted[0]->end->toDateString())->toBe('2025-04-13')
        ->and($shifted[1]->start->toDateString())->toBe('2025-04-14');
});

it('gives managers and specialists 30 days by their VTİSK category (m.114.3 b)', function (): void {
    $personnel = vnPersonnel();
    $position = Position::query()->findOrFail($personnel->position_id);

    $position->update(['vtisk_category' => 'mutexessis']);
    expect(vnBreakdown($personnel, '2025-05-01')['base'])->toBe(30);

    $position->update(['vtisk_category' => 'rehber']);
    expect(vnBreakdown($personnel, '2025-05-01')['base'])->toBe(30);

    // Texniki icraçı və fəhlə — 21 gün.
    $position->update(['vtisk_category' => 'texniki_icraci']);
    expect(vnBreakdown($personnel, '2025-05-01')['base'])->toBe(21);
});

it('leaves unpaid leave out of the seniority for the additional leave (m.116.2)', function (): void {
    // 2015-01-01-dən: 2025-01-01-də düz 10 il → +4.
    $personnel = vnPersonnel(['join_work_date' => '2015-01-01', 'gender' => 1]);
    expect(vnBreakdown($personnel, '2025-06-01')['seniority'])->toBe(4);

    OrderWordTemplate::query()->create(['code' => 'odenissiz_test', 'label' => 'Ödənişsiz məzuniyyət', 'effect' => 'unpaid_leave', 'docx_path' => 'x.docx']);
    OrderLog::query()->create([
        'order_no' => 'OM-1', 'given_date' => '2020-02-25', 'given_by' => 'Test', 'given_by_rank' => '',
        'status_id' => OrderStatusEnum::APPROVED->value, 'template_render_mode' => 'docx',
        'template_snapshot' => ['template_code' => 'odenissiz_test', 'fields' => []],
    ]);
    PersonnelVacation::withoutEvents(fn () => PersonnelVacation::query()->create([
        'tabel_no' => $personnel->tabel_no, 'start_date' => '2020-03-01', 'end_date' => '2020-03-30',
        'order_no' => 'OM-1', 'duration' => 30, 'vacation_places' => 'Bakı', 'return_work_date' => '2020-03-31', 'order_date' => '2020-02-25', 'order_given_by' => 'Test', 'vacation_days_total' => 30, 'remaining_days' => 0, 'added_by' => 1,
    ]));

    // 30 gün stajdan çıxılır: 10 ildən azdır → +2.
    expect(vnBreakdown($personnel, '2025-06-01')['seniority'])->toBe(2);
});

it('grants working-conditions leave only after six months in the conditions (m.131.6)', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2025-03-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $personnel->position_id, 'days' => 6, 'legal_basis' => 'ƏM m.115.1']);

    // 2025-03-01 – 2025-08-15: 168 gün < 6 ay.
    expect(vnBreakdown($personnel, '2025-08-15')['conditions'])->toBe(0)
        // Hüquq yaranıb və işçi hələ şəraitdədir — cari iş ili üçün tam (avans, NK 95 b.7).
        ->and(vnBreakdown($personnel, '2025-09-15')['conditions'])->toBe(6);
});

it('counts working-conditions time from the transfer into the position', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $personnel->position_id, 'days' => 12, 'legal_basis' => 'ƏM m.115.1']);

    expect(vnBreakdown($personnel, '2025-12-31')['conditions'])->toBe(12);

    // 2025-07-01-də köçürmə əmri ilə bu vəzifəyə keçib.
    OrderLog::query()->create([
        'order_no' => 'OK-1', 'given_date' => '2025-07-01', 'given_by' => 'Test', 'given_by_rank' => '',
        'status_id' => OrderStatusEnum::APPROVED->value, 'template_render_mode' => 'docx',
        'template_snapshot' => ['template_code' => 'kecirme', 'fields' => [], 'personnel_id' => $personnel->id,
            'effect_state' => ['prev_structure_id' => null, 'prev_position_id' => 1]],
    ]);

    // 2025-07-01 – 2025-12-28: 181 gün < 6 × 30,4 — hüquq hələ yoxdur.
    expect(vnBreakdown($personnel, '2025-12-28')['conditions'])->toBe(0)
        // Şərait ili köçürmə günündən sayılır (NK 95 b.7, 3-cü misal): 2025-07-01 – 2026-06-30.
        // 6 ay tamamdır və işçi hələ şəraitdədir — şərait ili üçün tam 12 gün (avans). Əvvəl
        // ümumi iş ili (2025-01-01 – 2025-12-31) götürülürdü və 6 ay × 12 ÷ 12 = 6 verilirdi.
        ->and(vnBreakdown($personnel, '2025-12-31')['conditions'])->toBe(12);
});

it('adds up the proportional days of each position within a work year (NK 95, b.11)', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    $previous = Position::query()->create(['id' => random_int(1000, 999999), 'name' => 'dehidratlaşdırma aparatçısı']);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $previous->id, 'days' => 12]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $personnel->position_id, 'days' => 6]);

    OrderLog::query()->create([
        'order_no' => 'OK-2', 'given_date' => '2025-03-01', 'given_by' => 'Test', 'given_by_rank' => '',
        'status_id' => OrderStatusEnum::APPROVED->value, 'template_render_mode' => 'docx',
        'template_snapshot' => ['template_code' => 'kecirme', 'fields' => [], 'personnel_id' => $personnel->id,
            'effect_state' => ['prev_structure_id' => null, 'prev_position_id' => $previous->id]],
    ]);

    // 59 gün → 2 ay × 12/12 = 2; 306 gün → 10 ay × 6/12 = 5; cəmi 7.
    expect(vnBreakdown($personnel, '2025-12-31')['conditions'])->toBe(7);
});

it('leaves out a period with less than 90% of the day in the conditions (NK 95, b.12)', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $personnel->position_id, 'days' => 12]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 0,
        'not_in_conditions' => true, 'valid_from' => '2025-01-01', 'valid_to' => '2025-06-30']);

    // Yalnız 2025-07-01 – 2025-12-31: 184 gün → 6 ay → 6.
    expect(vnBreakdown($personnel, '2025-12-31')['conditions'])->toBe(6);
});

it('gives listed work assigned for a period to an unlisted employee (NK 95, b.13)', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 6,
        'valid_from' => '2025-02-01', 'valid_to' => '2025-08-31', 'legal_basis' => 'NK 95 b.13']);

    // 212 gün → 7 ay; 6 × 7 ÷ 12 = 3,5 → 4. Dövr bitib — avans yoxdur.
    expect(vnBreakdown($personnel, '2025-12-31')['conditions'])->toBe(4)
        ->and(vnBreakdown($personnel, '2026-06-01')['conditions'])->toBe(0);
});

it('saves an outside-the-conditions period without days, only for an employee', function (): void {
    $personnel = vnPersonnel();
    $catalog = app(VacationNormCatalog::class);

    $norm = $catalog->save('conditions', [
        'scope' => 'personnel', 'personnel_id' => $personnel->id, 'not_in_conditions' => true,
        'valid_from' => '2025-01-01', 'valid_to' => '2025-03-31', 'days' => null,
    ]);

    expect($norm->days)->toBe(0)
        ->and($norm->not_in_conditions)->toBeTrue()
        ->and($catalog->describe($norm))->toContain('01.01.2025 – 31.03.2025');

    // Vəzifə üzrə sətir «şəraitdən kənar» ola bilməz və ən azı 6 gün tələb edir (m.115.1).
    expect(fn () => $catalog->save('conditions', [
        'scope' => 'position', 'position_id' => $personnel->position_id, 'not_in_conditions' => true, 'days' => 3,
    ]))->toThrow(DomainException::class);
});

/**
 * Təsdiqlənmiş köçürmə əmri (TransferEffect-in snapshot-a yazdığı vəziyyətlə).
 *
 * @param  array<string, mixed>  $state
 */
function vnTransfer(Personnel $personnel, string $orderNo, string $givenDate, array $state): OrderLog
{
    return OrderLog::query()->create([
        'order_no' => $orderNo, 'given_date' => $givenDate, 'given_by' => 'Test', 'given_by_rank' => '',
        'status_id' => OrderStatusEnum::APPROVED->value, 'template_render_mode' => 'docx',
        'template_snapshot' => ['template_code' => 'kecirme', 'fields' => [], 'personnel_id' => $personnel->id,
            'effect_state' => ['prev_structure_id' => null, ...$state]],
    ]);
}

/** @return list<array{0: string, 1: string}> */
function vnConditionsYears(Personnel $personnel, string $until): array
{
    app(CivilEntitlementStrategy::class)->forget();

    return array_map(
        fn ($p): array => [$p->start->toDateString(), $p->end->toDateString()],
        app(CivilEntitlementStrategy::class)->conditionsPeriods($personnel->fresh(), CarbonImmutable::parse($until)),
    );
}

it('stores the transfer date stated in the order and dates the position change by it (ƏM m.59)', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    $previous = $personnel->position_id;
    $target = Position::query()->create(['id' => random_int(1000, 999999), 'name' => 'qaynaqçı']);

    // Əmr 2025-06-01-də verilib, köçürmə «01.09.2025-ci il tarixdən».
    $order = OrderLog::query()->create([
        'order_no' => 'OK-ED', 'given_date' => '2025-06-01', 'given_by' => 'Test', 'given_by_rank' => '',
        'status_id' => OrderStatusEnum::APPROVED->value, 'template_render_mode' => 'docx',
        'template_snapshot' => ['template_code' => 'kecirme', 'fields' => [], 'personnel_id' => $personnel->id],
    ]);
    app(TransferEffect::class)->apply($order, ['new_position' => $target->id, 'effective_date' => '01.09.2025-ci il'], $personnel);

    expect($order->fresh()->template_snapshot['effect_state'])->toMatchArray(['prev_position_id' => $previous, 'effective_date' => '2025-09-01'])
        ->and($personnel->fresh()->position_id)->toBe($target->id);

    $changes = app(OrderLeaveFacts::class)->positionChanges($personnel->id, $target->id);
    expect(array_map(fn (array $c): array => [$c[0]->toDateString(), $c[1]], $changes))->toBe([['2025-09-01', $previous]]);

    // Rolu olmayan əmr (köhnə şablon): əmrin tarixi.
    $other = vnPersonnel(['join_work_date' => '2020-01-01']);
    $old = OrderLog::query()->create([
        'order_no' => 'OK-OLD', 'given_date' => '2025-06-01', 'given_by' => 'Test', 'given_by_rank' => '',
        'status_id' => OrderStatusEnum::APPROVED->value, 'template_render_mode' => 'docx',
        'template_snapshot' => ['template_code' => 'kecirme', 'fields' => [], 'personnel_id' => $other->id],
    ]);
    app(TransferEffect::class)->apply($old, ['new_position' => $target->id], $other);

    expect($old->fresh()->template_snapshot['effect_state']['effective_date'])->toBe('2025-06-01');
});

it('splits the conditions leave at the transfer effective date, not the order date (NK 95, b.11)', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    $previous = Position::query()->create(['id' => random_int(1000, 999999), 'name' => 'dehidratlaşdırma aparatçısı']);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $previous->id, 'days' => 12]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $personnel->position_id, 'days' => 6]);

    // Əmr 2025-01-15-də, köçürmə 2025-03-01-dən: 59 gün → 2 ay × 12/12 = 2; 306 gün → 10 ay × 6/12 = 5.
    vnTransfer($personnel, 'OK-S1', '2025-01-15', ['prev_position_id' => $previous->id, 'effective_date' => '2025-03-01']);
    expect(vnBreakdown($personnel, '2025-12-31')['conditions'])->toBe(7);

    // Eyni əmr tarixi olmadan (köhnə əmr) əmrin tarixindən bölünərdi: 14 gün → 0 ay; 351 gün → 12 ay × 6/12 = 6.
    OrderLog::query()->where('order_no', 'OK-S1')->delete();
    vnTransfer($personnel, 'OK-S2', '2025-01-15', ['prev_position_id' => $previous->id]);
    expect(vnBreakdown($personnel, '2025-12-31')['conditions'])->toBe(6);
});

it('measures the six months from the transfer effective date into the conditions', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $personnel->position_id, 'days' => 12]);
    // Əmr 2025-06-01, şəraitli vəzifəyə 2025-09-01-dən.
    vnTransfer($personnel, 'OK-6M', '2025-06-01', ['prev_position_id' => 1, 'effective_date' => '2025-09-01']);

    expect(vnConditionsYears($personnel, '2026-03-31'))->toBe([['2025-09-01', '2026-08-31']])
        // 2025-09-01 – 2026-02-27: 180 gün < 6 ay (əmrin tarixindən sayılsaydı 9 ay olardı).
        ->and(vnBreakdown($personnel, '2026-02-27')['conditions'])->toBe(0)
        ->and(vnBreakdown($personnel, '2026-03-31')['conditions'])->toBe(12);
});

it('keeps a separate work year for the conditions leave from the start in the conditions (NK 95, b.7, example 3)', function (): void {
    // Misal: təchizat şöbəsinə avqustda qəbul, fevralın 1-dən 12 günlük şəraitli işə keçirilib.
    $personnel = vnPersonnel(['join_work_date' => '2024-08-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $personnel->position_id, 'days' => 12]);
    vnTransfer($personnel, 'OK-M3', '2025-01-20', ['prev_position_id' => 1, 'effective_date' => '2025-02-01']);

    $years = collect(app(VacationBalanceService::class)->balanceOn($personnel->fresh(), CarbonImmutable::parse('2025-09-15'))['work_years'])
        ->map(fn (array $y): array => [$y['kind'], $y['start'], $y['end'], $y['entitled'], $y['breakdown']['conditions']])
        ->all();

    // Əsas məzuniyyət üçün iş ili avqustdan avqustadək, əlavə məzuniyyət üçün fevraldan fevraladək.
    // Sentyabrda ikinci iş ili üçün məzuniyyətə gedəndə əlavə məzuniyyət tam verilir.
    expect($years)->toBe([
        ['annual', '2024-08-01', '2025-07-31', 21, 0],
        ['conditions', '2025-02-01', '2026-01-31', 12, 12],
        ['annual', '2025-08-01', '2026-07-31', 21, 0],
    ]);

    // Ümumi iş ili ilə sayılsaydı birinci iş ilində (2025-02-01 – 2025-07-31, 181 gün) hüquq
    // yaranmazdı; şərait ilində isə 6 ay 2025-08-03-də tamam olur.
    expect(vnBreakdown($personnel, '2025-08-01')['conditions'])->toBe(0)
        ->and(vnBreakdown($personnel, '2025-08-05')['conditions'])->toBe(12);

    // Bir əmrlə birlikdə verilir: ən köhnə sətirdən — birinci iş ili, sonra şərait ili.
    app(VacationBalanceService::class)->consume($personnel->fresh(), 2025, 33, 'test:1', CarbonImmutable::parse('2025-09-15'));
    $remaining = collect(app(VacationBalanceService::class)->balanceOn($personnel->fresh(), CarbonImmutable::parse('2025-09-15'))['work_years'])
        ->mapWithKeys(fn (array $y): array => [$y['kind'].':'.$y['sequence'] => $y['remaining']])
        ->all();

    expect($remaining)->toBe(['annual:1' => 0, 'conditions:1' => 0, 'annual:2' => 21]);
});

it('restarts the conditions year on return after a whole conditions year outside the conditions', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 6,
        'valid_from' => '2020-01-01', 'valid_to' => '2021-06-30']);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 6,
        'valid_from' => '2023-03-01']);

    // 2022 şərait ili bütünlüklə şəraitsiz keçib — növbəti şərait ili qayıdış günündən.
    expect(vnConditionsYears($personnel, '2024-06-01'))->toBe([
        ['2020-01-01', '2020-12-31'],
        ['2021-01-01', '2021-12-31'],
        ['2023-03-01', '2024-02-29'],
        ['2024-03-01', '2025-02-28'],
    ]);

    // 6 ay «üst-üstə» (cəmi) sayılır: əvvəlki 18 ay qalır — qayıdışdan bir ay sonra hüquq var.
    expect(vnBreakdown($personnel, '2023-04-01')['conditions'])->toBe(6);
});

it('defers a future transfer until its date and applies it exactly once', function (): void {
    \Illuminate\Support\Carbon::setTestNow('2026-10-10 12:00:00');
    try {
        $person = vnPersonnel(['join_work_date' => '2020-01-01']);
        $previous = $person->position_id;
        $target = Position::query()->create(['name' => 'Future position']);
        $order = OrderLog::query()->create([
            'order_no' => 'FUT-1', 'given_date' => '2026-10-10', 'given_by' => 'Test', 'given_by_rank' => '', 'status_id' => OrderStatusEnum::APPROVED->value,
            'template_render_mode' => 'docx', 'template_snapshot' => ['template_code' => 'kecirme', 'personnel_id' => $person->id],
        ]);
        $effect = app(TransferEffect::class);
        $effect->apply($order, ['new_position' => $target->id, 'effective_date' => '2026-10-12'], $person);
        expect($person->fresh()->position_id)->toBe($previous)
            ->and($effect->applyDue($order))->toBeFalse();
        $changes = app(OrderLeaveFacts::class)->positionChanges($person->id, $previous);
        $facts = new \App\Modules\Personnel\Contracts\LeaveEntitlementFacts(tabelNo: $person->tabel_no, positionId: $previous, positionChanges: $changes);
        expect($facts->positionOn(CarbonImmutable::parse('2026-10-11')))->toBe($previous)
            ->and($facts->positionOn(CarbonImmutable::parse('2026-10-12')))->toBe($target->id);
        \Illuminate\Support\Carbon::setTestNow('2026-10-12 00:10:00');
        $result = app(\App\Modules\Orders\Application\Services\DeferredTransferService::class)->applyDue();
        expect($result)->toBe(['applied' => 1, 'skipped' => 0, 'failed' => 0])
            ->and($person->fresh()->position_id)->toBe($target->id)
            ->and($effect->applyDue($order))->toBeFalse();
        $effect->reverse($order->fresh(), ['new_position' => $target->id], $person->fresh());
        expect($person->fresh()->position_id)->toBe($previous);
    } finally {
        \Illuminate\Support\Carbon::setTestNow();
    }
});

it('cancels an unapplied transfer without undoing a different current position', function (): void {
    \Illuminate\Support\Carbon::setTestNow('2026-10-10 12:00:00');
    try {
        $person = vnPersonnel();
        $target = Position::query()->create(['name' => 'Future']);
        $other = Position::query()->create(['name' => 'Intervening']);
        $order = OrderLog::query()->create([
            'order_no' => 'FUT-2', 'given_date' => '2026-10-10', 'given_by' => 'Test', 'given_by_rank' => '', 'status_id' => OrderStatusEnum::APPROVED->value,
            'template_render_mode' => 'docx', 'template_snapshot' => ['personnel_id' => $person->id],
        ]);
        $effect = app(TransferEffect::class);
        $effect->apply($order, ['new_position' => $target->id, 'effective_date' => '2026-10-20'], $person);
        app(\App\Modules\Personnel\Contracts\GuardsPersonnelChanges::class)->allowForEffect('transfer', fn () => $person->forceFill(['position_id' => $other->id])->save());
        $effect->reverse($order->fresh(), ['new_position' => $target->id], $person->fresh());
        expect($person->fresh()->position_id)->toBe($other->id)
            ->and($order->fresh()->template_snapshot['effect_state']['transfer_pending'])->toBeFalse();
    } finally {
        \Illuminate\Support\Carbon::setTestNow();
    }
});

it('registers the deferred transfer command every five minutes with overlap protection', function (): void {
    $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
    $event = collect($events)->first(fn ($event): bool => str_contains((string) $event->command, 'orders:apply-due-transfers'));
    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/5 * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
    $this->artisan('orders:apply-due-transfers')->assertSuccessful();
});

it('applies overdue transfers by effective date rather than approval sequence', function (): void {
    \Illuminate\Support\Carbon::setTestNow('2026-10-10 12:00:00');
    try {
        $person = vnPersonnel();
        $later = Position::query()->create(['name' => 'Later']);
        $earlier = Position::query()->create(['name' => 'Earlier']);
        $orders = [];
        foreach ([['2026-10-17', $later], ['2026-10-15', $earlier]] as $index => [$date, $target]) {
            $order = OrderLog::query()->create([
                'order_no' => 'SEQ-'.$index, 'given_date' => '2026-10-10', 'given_by' => 'Test', 'given_by_rank' => '',
                'status_id' => OrderStatusEnum::APPROVED->value, 'template_render_mode' => 'docx',
                'template_snapshot' => ['personnel_id' => $person->id],
            ]);
            app(TransferEffect::class)->apply($order, ['new_position' => $target->id, 'effective_date' => $date], $person->fresh());
            $orders[] = $order;
        }
        \Illuminate\Support\Carbon::setTestNow('2026-10-20 12:00:00');
        $service = app(\App\Modules\Orders\Application\Services\DeferredTransferService::class);
        expect($service->applyDue()['applied'])->toBe(2)
            ->and($person->fresh()->position_id)->toBe($later->id)
            ->and($orders[0]->fresh()->template_snapshot['effect_state']['prev_position_id'])->toBe($earlier->id)
            ->and($service->applyDue()['applied'])->toBe(0);
    } finally {
        \Illuminate\Support\Carbon::setTestNow();
    }
});

/*
 * Şərait ilində fasilələr (NK 95, b.6–b.11; ƏM m.131.6) — hal-hal təhlil:
 * docs/vacation-legal-basis.md, (c-2).
 */

/**
 * Şərait illəri və hər birinin $asOf tarixinə əlavə məzuniyyət günləri.
 *
 * @return list<array{0: string, 1: string, 2: int}>
 */
function vnConditionsDays(Personnel $personnel, string $asOf): array
{
    $strategy = app(CivilEntitlementStrategy::class);
    $strategy->forget();
    $on = CarbonImmutable::parse($asOf);

    return array_map(
        fn ($p): array => [$p->start->toDateString(), $p->end->toDateString(), $strategy->conditionsEntitlement($personnel->fresh(), $p, $on)->conditions],
        $strategy->conditionsPeriods($personnel->fresh(), $on),
    );
}

/** Təsdiqlənmiş əmrlə verilmiş məzuniyyət (şablon kodu ilə: uşağa qulluq, ödənişsiz və s.). */
function vnLeave(Personnel $personnel, string $templateCode, string $from, string $to): void
{
    $orderNo = 'OL-'.Str::upper(Str::random(5));
    OrderLog::query()->create([
        'order_no' => $orderNo, 'given_date' => $from, 'given_by' => 'Test', 'given_by_rank' => '',
        'status_id' => OrderStatusEnum::APPROVED->value, 'template_render_mode' => 'docx',
        'template_snapshot' => ['template_code' => $templateCode, 'fields' => []],
    ]);
    PersonnelVacation::withoutEvents(fn () => PersonnelVacation::query()->create([
        'tabel_no' => $personnel->tabel_no, 'start_date' => $from, 'end_date' => $to,
        'order_no' => $orderNo, 'duration' => 1, 'vacation_places' => 'Bakı', 'return_work_date' => $to, 'order_date' => $from,
        'order_given_by' => 'Test', 'vacation_days_total' => 1, 'remaining_days' => 0, 'added_by' => 1,
    ]));
}

it('gives a transferred-out worker the months of the work year in the conditions (NK 95, b.7, example 1)', function (): void {
    // Misal 1: 12 günlük sex ustası 01.03-də qəbul, növbəti ilin 01.07-də mühəndisliyə keçirilib.
    $personnel = vnPersonnel(['join_work_date' => '2023-03-01', 'gender' => 1]);
    $master = Position::query()->create(['id' => random_int(1000, 999999), 'name' => 'sex ustası']);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $master->id, 'days' => 12]);
    vnTransfer($personnel, 'OK-U1', '2024-06-25', ['prev_position_id' => $master->id, 'effective_date' => '2024-07-01']);

    // 1-ci il tam (12); 2-ci il 01.03–30.06.2024 = 122 gün → 4 ay → 4 gün. Misal mart ayını
    // (məzuniyyətdə keçib) saymır və 3 gün yazır; b.8.2 iş yeri və əmək haqqı saxlanılan
    // məzuniyyət dövrünü staja daxil edir — sistem normativ mətni götürür (işçinin xeyrinə).
    // 3-cü il bütünlüklə şəraitsizdir — göstərilmir.
    expect(vnConditionsDays($personnel, '2025-06-01'))->toBe([
        ['2023-03-01', '2024-02-29', 12],
        ['2024-03-01', '2025-02-28', 4],
    ]);
});

it('adds up interrupted conditions time within one conditions year once six months are reached (m.131.6 «üst-üstə»)', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 12, 'valid_from' => '2025-01-01']);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 0,
        'not_in_conditions' => true, 'valid_from' => '2025-04-01', 'valid_to' => '2025-06-30']);

    // 90 gün (yanvar–mart) + 92 gün (iyul–sentyabr) = 182 < 6 × 30,4 — hüquq hələ yoxdur.
    expect(vnConditionsDays($personnel, '2025-09-30'))->toBe([['2025-01-01', '2025-12-31', 0]])
        // 183-cü gün: hüquq yaranır, fasilədən əvvəlki aylar da sayılır; işçi şəraitdədir — ilin
        // qalanı avansla: 90 + 184 = 274 gün → 9 ay → 12 × 9 ÷ 12 = 9 gün (b.7, b.10).
        ->and(vnConditionsDays($personnel, '2025-10-01'))->toBe([['2025-01-01', '2025-12-31', 9]]);
});

it('keeps a short conditions year without days when six months are reached only in the next year', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 12,
        'valid_from' => '2024-01-01', 'valid_to' => '2024-04-30']);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 12, 'valid_from' => '2025-03-01']);

    // 2024: 121 gün (4 ay) — il bitəndə hüquq yox idi. 2025: zəncir davam edir (ildə şəraitli gün
    // var), 01.03–31.12 = 306 gün → 10 ay → 10 gün. 2024-ün 4 ayı sonradan əlavə olunmur — bax
    // docs/vacation-legal-basis.md, (c-2), qalıq risk.
    expect(vnConditionsDays($personnel, '2025-12-31'))->toBe([
        ['2024-01-01', '2024-12-31', 0],
        ['2025-01-01', '2025-12-31', 10],
    ]);
});

it('continues the conditions year chain when the break is shorter than a whole conditions year', function (): void {
    $personnel = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 12,
        'valid_from' => '2020-01-01', 'valid_to' => '2021-03-31']);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'personnel', 'tabel_no' => $personnel->tabel_no, 'days' => 12, 'valid_from' => '2022-02-01']);

    // 2021: 3 ay → 3 gün; 2022 şərait ili ildönümündən davam edir: 01.02–31.12 = 334 gün → 11 ay → 11.
    expect(vnConditionsDays($personnel, '2022-12-31'))->toBe([
        ['2020-01-01', '2020-12-31', 12],
        ['2021-01-01', '2021-12-31', 3],
        ['2022-01-01', '2022-12-31', 11],
    ]);
});

it('extends the conditions year by child-care leave and breaks the chain after a whole year of unpaid leave (NK 95, b.8; ƏM m.132.2)', function (): void {
    // Uşağa qulluq məzuniyyəti (m.127) staja daxil deyil və iş ilini uzadır — şərait ili də uzanır.
    $mother = vnPersonnel(['join_work_date' => '2020-01-01']);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $mother->position_id, 'days' => 12]);
    vnLeave($mother, 'usaga_qulluq_mezuniyyeti', '2021-03-01', '2022-02-28');

    // 2-ci şərait ili 365 gün uzanır; içində 730 − 365 = 365 gün → 12 ay → 12 gün.
    expect(vnConditionsDays($mother, '2023-06-01'))->toBe([
        ['2020-01-01', '2020-12-31', 12],
        ['2021-01-01', '2022-12-31', 12],
        ['2023-01-01', '2023-12-31', 12],
    ]);

    // Ödənişsiz məzuniyyət b.8-in siyahısında yoxdur: sayılmır və ili uzatmır. Bütöv şərait ili
    // (2021) ödənişsiz keçibsə növbəti şərait ili qayıdış günündən başlayır.
    OrderWordTemplate::query()->create(['code' => 'odenissiz_sert', 'label' => 'Ödənişsiz məzuniyyət', 'effect' => 'unpaid_leave', 'docx_path' => 'x.docx']);
    $worker = vnPersonnel(['join_work_date' => '2020-01-01', 'gender' => 1]);
    VacationNorm::query()->create(['group' => 'conditions', 'scope' => 'position', 'position_id' => $worker->position_id, 'days' => 12]);
    vnLeave($worker, 'odenissiz_sert', '2021-01-01', '2022-01-31');

    expect(vnConditionsDays($worker, '2022-12-31'))->toBe([
        ['2020-01-01', '2020-12-31', 12],
        ['2022-02-01', '2023-01-31', 12],
    ]);
});
