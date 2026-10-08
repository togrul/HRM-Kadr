<?php

use App\Data\AbsencePeriod;
use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\User;
use App\Services\Absence\AbsenceOverlapException;
use App\Services\Absence\AbsenceOverlapGuard;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => test()->actingAs(User::factory()->create()));

function absenceDays(string $type, string $from, string $to, ?int $id = null): AbsencePeriod
{
    return AbsencePeriod::days($type, $id, CarbonImmutable::parse($from), CarbonImmutable::parse($to));
}

function vacationFor(string $tabelNo, string $from, string $to, ?string $approval = null): PersonnelVacation
{
    return PersonnelVacation::query()->create([
        'tabel_no' => $tabelNo,
        'vacation_places' => '',
        'duration' => 5,
        'start_date' => $from,
        'end_date' => $to,
        'return_work_date' => CarbonImmutable::parse($to)->addDay()->toDateString(),
        'order_given_by' => 'HR',
        'vacation_days_total' => 0,
        'remaining_days' => 0,
        'approval_status' => $approval,
        'added_by' => 1,
    ]);
}

it('finds a clash across leaves, vacations and business trips and names it in Azerbaijani', function () {
    app()->setLocale('az');
    vacationFor('TB-1', '2026-10-20', '2026-10-29');

    $guard = app(AbsenceOverlapGuard::class);

    expect($guard->violation('TB-1', absenceDays(AbsencePeriod::TYPE_LEAVE, '2026-10-12', '2026-10-20')))
        ->toBe('Bu tarixlərdə əməkdaşın artıq məzuniyyət qeydi var (20.10.2026 – 29.10.2026).')
        ->and($guard->violation('TB-1', absenceDays(AbsencePeriod::TYPE_LEAVE, '2026-10-12', '2026-10-19')))->toBeNull()
        ->and($guard->violation('TB-2', absenceDays(AbsencePeriod::TYPE_LEAVE, '2026-10-20', '2026-10-21')))->toBeNull();

    PersonnelBusinessTrip::query()->create([
        'tabel_no' => 'TB-1', 'location' => 'Gəncə', 'start_date' => '2026-11-01', 'end_date' => '2026-11-03', 'order_given_by' => 'HR',
    ]);

    expect(fn () => $guard->assertNoOverlap('TB-1', absenceDays(AbsencePeriod::TYPE_VACATION, '2026-11-03', '2026-11-10')))
        ->toThrow(AbsenceOverlapException::class, 'ezamiyyət');
});

it('ignores rejected, cancelled and deleted absences and the record being checked itself', function () {
    vacationFor('TB-1', '2026-10-20', '2026-10-29', 'rejected');
    $own = vacationFor('TB-1', '2026-12-01', '2026-12-05');
    $trip = PersonnelBusinessTrip::query()->create([
        'tabel_no' => 'TB-1', 'location' => 'Gəncə', 'start_date' => '2026-11-01', 'end_date' => '2026-11-03', 'order_given_by' => 'HR',
    ]);
    $trip->delete();
    Leave::withoutEvents(fn () => Leave::query()->create([
        'tabel_no' => 'TB-1', 'starts_at' => '2026-10-05', 'ends_at' => '2026-10-06', 'duration_unit' => 'day',
        'status_id' => OrderStatusEnum::CANCELLED->value,
    ]));

    $guard = app(AbsenceOverlapGuard::class);

    expect($guard->conflict('TB-1', absenceDays(AbsencePeriod::TYPE_LEAVE, '2026-10-01', '2026-11-30')))->toBeNull()
        ->and($guard->conflict('TB-1', absenceDays(AbsencePeriod::TYPE_VACATION, '2026-12-02', '2026-12-03', $own->id)))->toBeNull()
        ->and($guard->conflict('TB-1', absenceDays(AbsencePeriod::TYPE_LEAVE, '2026-12-02', '2026-12-02')))->not->toBeNull();
});

it('lets two hour leaves share a day only when their hours do not cross', function () {
    $morning = new AbsencePeriod(AbsencePeriod::TYPE_LEAVE, 1, CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-01'), 'hour', null, '09:00', '11:00');
    $noon = new AbsencePeriod(AbsencePeriod::TYPE_LEAVE, 2, CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-01'), 'hour', null, '11:00', '12:00');
    $crossing = new AbsencePeriod(AbsencePeriod::TYPE_LEAVE, 3, CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-01'), 'hour', null, '10:30', '12:00');
    $fullDay = absenceDays(AbsencePeriod::TYPE_VACATION, '2026-10-01', '2026-10-01');

    expect($morning->overlaps($noon))->toBeFalse()
        ->and($morning->overlaps($crossing))->toBeTrue()
        ->and($noon->overlaps($fullDay))->toBeTrue();
});
