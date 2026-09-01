<?php

namespace Tests\Feature\Integration;

use App\Models\AttendanceCalendar;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\FinancePayslip;
use App\Models\FinancePeriodState;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceMonthLockService;
use App\Modules\Integration\Application\Services\FinanceImportService;
use App\Modules\Integration\Infrastructure\FinanceClient;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The return channel: what the finance system owns, brought back here.
 *
 * Each of these closes a hole that would otherwise be invisible:
 *
 * - without payslips an employee cannot see what they were paid, because their
 *   self-service is here and the calculation is there;
 * - without period state a month already paid from could be reopened here;
 * - without the calendar the two disagree about holidays and every norm drifts;
 * - without business trips the days never reach attendance, so the trip looks
 *   filed but changes nothing.
 */
class FinanceImportTest extends TestCase
{
    use RefreshDatabase;

    private Personnel $person;

    private bool $faked = false;

    /** @var array<string, list<array<string, mixed>>> */
    private array $feeds = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'integration.finance.base_url' => 'https://arbay.test',
            'integration.finance.token' => 'arb_token',
        ]);

        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');
        Country::query()->create(['id' => 1, 'code' => 'AZ']);
        EducationDegree::query()->create(['id' => 1, 'title_az' => 'B', 'title_en' => 'B', 'title_ru' => 'B']);
        WorkNorm::query()->create(['id' => 1, 'name_az' => 'T', 'name_en' => 'F', 'name_ru' => 'P']);
        Structure::query()->create([
            'id' => 500, 'name' => 'IT', 'shortname' => 'IT',
            'parent_id' => null, 'coefficient' => 1, 'code' => 500, 'level' => 1,
        ]);
        Position::query()->create(['id' => 500, 'name' => 'P', 'approval_rank' => 0, 'is_approval_target' => true]);

        $this->person = Personnel::query()->create([
            'tabel_no' => 'TB-1', 'surname' => 'Əliyev', 'name' => 'Elçin', 'patronymic' => 'R',
            'birthdate' => '1990-01-01', 'gender' => 1, 'mobile' => '994',
            'nationality_id' => 1, 'pin' => 'P1', 'residental_address' => 'Bakı',
            'education_degree_id' => 1, 'structure_id' => 500, 'position_id' => 500,
            'work_norm_id' => 1, 'join_work_date' => '2020-01-01',
            'added_by' => User::factory()->create()->id, 'is_pending' => false,
        ]);
    }

    /** An unconfigured installation is a normal state, not a failure. */
    public function test_an_unconfigured_connection_is_not_an_error(): void
    {
        config(['integration.finance.base_url' => '']);

        $this->assertFalse(app(FinanceClient::class)->isConfigured());

        $this->artisan('integration:pull-finance')->assertSuccessful();
    }

    /** Payslips arrive as totals and are keyed to our own staff number. */
    public function test_payslips_are_mirrored(): void
    {
        $this->fake(['payslips' => [[
            'sequence' => 1,
            'employee_external_id' => (string) $this->person->id,
            'employee_name' => 'Əliyev Elçin',
            'gross' => 1800, 'total_deductions' => 450, 'net' => 1350, 'currency' => 'AZN',
        ]]]);

        $stats = app(FinanceImportService::class)->payslips(2026, 7);

        $this->assertSame(1, $stats['imported']);

        $slip = FinancePayslip::query()->firstOrFail();

        $this->assertSame('TB-1', $slip->tabel_no);
        $this->assertEqualsWithDelta(1350.0, (float) $slip->net, 0.001);
    }

    /** A person we do not know is skipped rather than failing the run. */
    public function test_an_unknown_person_is_skipped(): void
    {
        $this->fake(['payslips' => [[
            'sequence' => 1, 'employee_external_id' => '99999',
            'employee_name' => 'Naməlum', 'gross' => 1, 'total_deductions' => 0, 'net' => 1,
        ]]]);

        $stats = app(FinanceImportService::class)->payslips(2026, 7);

        $this->assertSame(0, $stats['imported']);
        $this->assertSame(1, $stats['skipped']);
    }

    /** A closed period refuses the unlock — the authoritative signal. */
    public function test_a_closed_period_blocks_the_month_unlock(): void
    {
        $this->fake(['periods' => [
            ['year' => 2026, 'month' => 7, 'closed' => true, 'closed_at' => '2026-08-05T00:00:00+04:00'],
            ['year' => 2026, 'month' => 8, 'closed' => false, 'closed_at' => null],
        ]]);

        app(FinanceImportService::class)->periodState();

        $this->assertTrue(FinancePeriodState::isClosed(2026, 7));
        $this->assertFalse(FinancePeriodState::isClosed(2026, 8));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/closed its accounting period/i');

        app(AttendanceMonthLockService::class)->unlockMonth(2026, 7);
    }

    /** An open period is untouched. */
    public function test_an_open_period_still_unlocks(): void
    {
        $this->fake(['periods' => [['year' => 2026, 'month' => 8, 'closed' => false]]]);
        app(FinanceImportService::class)->periodState();

        $stats = app(AttendanceMonthLockService::class)->unlockMonth(2026, 8);

        $this->assertIsArray($stats);
    }

    /**
     * A holiday removed on their side disappears here too.
     *
     * Otherwise the calendar fingerprints would never match again and every
     * attendance package would be refused from then on.
     */
    public function test_the_calendar_mirrors_removals(): void
    {
        AttendanceCalendar::query()->create([
            'date' => '2026-07-15', 'day_type' => 'holiday', 'name' => 'Köhnə',
            'is_paid' => true, 'scope_type' => 'global', 'scope_id' => null,
        ]);

        $this->fake(['calendar' => [
            ['date' => '2026-01-01', 'name' => 'Yeni il', 'day_type' => 'holiday', 'kind' => 'state'],
        ]]);

        $stats = app(FinanceImportService::class)->calendar(2026);

        $this->assertSame(1, $stats['imported']);
        $this->assertSame(1, $stats['removed']);
        $this->assertSame(1, AttendanceCalendar::query()->where('scope_type', 'global')->count());
    }

    /**
     * The weekend calendar survives an import.
     *
     * The finance system publishes public holidays and knows nothing about
     * which days this organisation rests on. A sweep by date alone would take
     * the weekends with it and every monthly norm would collapse.
     */
    public function test_the_weekend_calendar_is_not_swept_away(): void
    {
        AttendanceCalendar::query()->create([
            'date' => '2026-07-04', 'day_type' => 'weekend', 'name' => 'İstirahət',
            'is_paid' => false, 'scope_type' => 'global', 'scope_id' => null,
        ]);

        $this->fake(['calendar' => [
            ['date' => '2026-01-01', 'name' => 'Yeni il', 'day_type' => 'holiday', 'kind' => 'state'],
        ]]);

        $stats = app(FinanceImportService::class)->calendar(2026);

        $this->assertSame(0, $stats['removed']);
        $this->assertSame(1, AttendanceCalendar::query()->where('day_type', 'weekend')->count());
    }

    /**
     * A business trip becomes a trip record here.
     *
     * That is what makes the days show as `business_trip` in the ledger — and
     * therefore what sends them back on the next attendance package. The finance
     * side never writes our attendance directly.
     */
    public function test_a_business_trip_is_recorded(): void
    {
        $this->fake(['trips' => [[
            'sequence' => 1, 'external_id' => '5', 'no' => 'EZ-1',
            'employee_external_id' => (string) $this->person->id,
            'status' => 'ordered',
            'date_from' => '2026-07-06', 'date_to' => '2026-07-10',
            'destination' => 'Bakı', 'purpose' => 'Danışıqlar',
        ]]]);

        $stats = app(FinanceImportService::class)->businessTrips();

        $this->assertSame(1, $stats['applied']);

        $trip = PersonnelBusinessTrip::query()->firstOrFail();

        $this->assertSame('TB-1', $trip->tabel_no);
        $this->assertSame('EZ-1', $trip->order_no);
        $this->assertSame('Bakı', $trip->location);
    }

    /** A cancelled trip removes the record — the employee was never away. */
    public function test_a_cancelled_trip_is_removed(): void
    {
        $this->fake(['trips' => [[
            'sequence' => 1, 'external_id' => '5', 'no' => 'EZ-1',
            'employee_external_id' => (string) $this->person->id, 'status' => 'ordered',
            'date_from' => '2026-07-06', 'date_to' => '2026-07-10',
            'destination' => 'Bakı', 'purpose' => 'Danışıqlar',
        ]]]);
        app(FinanceImportService::class)->businessTrips();

        $this->fake(['trips' => [[
            'sequence' => 2, 'external_id' => '5', 'no' => 'EZ-1',
            'employee_external_id' => (string) $this->person->id, 'status' => 'cancelled',
            'date_from' => '2026-07-06', 'date_to' => '2026-07-10',
        ]]]);

        $stats = app(FinanceImportService::class)->businessTrips();

        $this->assertSame(1, $stats['cancelled']);
        $this->assertSame(0, PersonnelBusinessTrip::query()->count());
    }

    /** A refused token says what to do about it. */
    public function test_a_refused_token_explains_itself(): void
    {
        Http::fake(['arbay.test/*' => Http::response(['message' => 'no'], 403)]);

        $this->expectExceptionMessageMatches('/Issue a new one there/i');

        app(FinanceImportService::class)->periodState();
    }

    /**
     * @param  array{payslips?: list<array<string, mixed>>, periods?: list<array<string, mixed>>, calendar?: list<array<string, mixed>>, trips?: list<array<string, mixed>>}  $data
     */
    private function fake(array $data): void
    {
        $this->feeds = $data;

        // A repeated `Http::fake()` APPENDS to the stub list rather than
        // replacing it, and the first match wins — so a second call would never
        // be seen. The data is held in a field and read at request time instead.
        if ($this->faked) {
            return;
        }

        $this->faked = true;

        $paged = fn (string $key) => fn () => Http::response(['data' => [
            'items' => $this->feeds[$key] ?? [],
            'last_sequence' => count($this->feeds[$key] ?? []),
            'has_more' => false,
        ]]);

        $plain = fn (string $key) => fn () => Http::response([
            'data' => ['items' => $this->feeds[$key] ?? []],
        ]);

        Http::fake([
            'arbay.test/api/v1/hr/payslips*' => $paged('payslips'),
            'arbay.test/api/v1/hr/period-state*' => $plain('periods'),
            'arbay.test/api/v1/hr/calendar*' => $plain('calendar'),
            'arbay.test/api/v1/hr/events/business_trip*' => $paged('trips'),
        ]);
    }
}
