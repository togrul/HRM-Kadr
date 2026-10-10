<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceCalendar;
use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Role;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceDailyLedgerCalculatorService;
use App\Modules\Attendance\Application\Services\AttendanceOverviewService;
use App\Modules\Attendance\Application\Services\AttendanceWorkNormService;
use App\Modules\Attendance\Livewire\PuantajGrid;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * İş vaxtı norması (ƏM: həftədə 40 saat → 5 günlük həftədə gündə 8 saat, bayramqabağı 1 saat az)
 * və puantajda faktiki qeydi olmayan keçmiş günlərin qrafik üzrə defoltu.
 */
class AttendanceWorkNormAndPuantajDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_october_2026_on_a_standard_five_day_week_is_176_hours_without_any_shift_configured(): void
    {
        $norm = app(AttendanceWorkNormService::class)->monthNorm(2026, 10);

        $this->assertSame(22, $norm['workdays']);
        $this->assertSame(480, $norm['daily_minutes']);
        $this->assertSame(176 * 60, $norm['minutes']);
    }

    public function test_the_default_shift_lunch_break_is_subtracted_from_the_daily_norm(): void
    {
        $this->configureDefaultShift('09:00', '18:00', 60);

        $norm = app(AttendanceWorkNormService::class)->monthNorm(2026, 10);

        $this->assertSame(480, $norm['daily_minutes']);
        $this->assertSame(176 * 60, $norm['minutes']);
    }

    public function test_the_workday_before_a_holiday_is_shortened_by_one_hour(): void
    {
        AttendanceCalendar::query()->create([
            'date' => '2026-10-15', 'day_type' => 'holiday', 'name' => 'Bayram',
            'is_paid' => true, 'scope_type' => 'global', 'scope_id' => null,
        ]);

        $norm = app(AttendanceWorkNormService::class)->monthNorm(2026, 10);

        $this->assertSame(21, $norm['workdays']);
        $this->assertSame(1, $norm['pre_holidays']);
        $this->assertSame((20 * 8 + 7) * 60, $norm['minutes']);
    }

    public function test_a_holiday_on_the_first_day_of_next_month_shortens_the_last_workday(): void
    {
        AttendanceCalendar::query()->create([
            'date' => '2026-12-01', 'day_type' => 'holiday', 'name' => 'Bayram',
            'is_paid' => true, 'scope_type' => 'global', 'scope_id' => null,
        ]);

        $norm = app(AttendanceWorkNormService::class)->monthNorm(2026, 11);

        $this->assertSame(1, $norm['pre_holidays']);
    }

    public function test_overview_planned_hours_fall_back_to_the_8_hour_norm(): void
    {
        $overview = app(AttendanceOverviewService::class)->build(2026, 10, null, false);

        $this->assertSame(22, $overview['workdays']);
        $this->assertSame(176 * 60, $overview['scheduled_minutes']);
        $this->assertSame(480, $overview['daily_norm_minutes']);
    }

    public function test_ledger_scheduled_minutes_are_shortened_on_a_pre_holiday_day(): void
    {
        $shift = $this->makeShift('09:00', '18:00', 60);
        $calculator = app(AttendanceDailyLedgerCalculatorService::class);
        $pairing = ['worked_minutes' => 0, 'break_minutes' => 0, 'unmatched' => 0, 'first_in_at' => null, 'last_out_at' => null, 'pairs' => []];

        $regular = $calculator->calculate(date: Carbon::parse('2026-10-13'), pairing: $pairing, shift: $shift);
        $preHoliday = $calculator->calculate(date: Carbon::parse('2026-10-14'), pairing: $pairing, shift: $shift, isPreHoliday: true);

        $this->assertSame(480, $regular['scheduled_minutes']);
        $this->assertSame(420, $preHoliday['scheduled_minutes']);
    }

    public function test_past_workdays_without_a_fact_show_planned_hours_while_facts_absences_and_future_days_win(): void
    {
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->actingAs($this->authorizedUser());
        $personnel = $this->makePersonnel('TBNORM1');

        AttendanceDailyLedger::query()->create([
            'tabel_no' => $personnel->tabel_no, 'date' => '2026-10-01', 'scheduled_minutes' => 480,
            'worked_minutes' => 300, 'break_minutes' => 0, 'overtime_minutes' => 0, 'late_minutes' => 0,
            'early_leave_minutes' => 0, 'attendance_status' => 'present', 'absence_code' => null,
            'source_summary' => 'system', 'is_locked' => false, 'meta' => [],
        ]);

        DB::table('personnel_vacations')->insert([
            'tabel_no' => $personnel->tabel_no, 'vacation_places' => 'Bakı', 'duration' => 2,
            'start_date' => '2026-10-05', 'end_date' => '2026-10-06', 'return_work_date' => '2026-10-07',
            'order_given_by' => 'HR', 'added_by' => User::query()->value('id'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $component = Livewire::test(PuantajGrid::class, ['year' => 2026, 'month' => 10]);
        $rows = $component->viewData('rows');
        $cells = $rows[0]['cells'];

        $this->assertSame('5', $cells[1]['display']);
        $this->assertSame('present', $cells[1]['status']);

        $this->assertSame('planned', $cells[2]['status']);
        $this->assertSame('8', $cells[2]['display']);
        $this->assertStringContainsString('italic', $cells[2]['cell_classes']);

        $this->assertSame('none', $cells[3]['status'], 'Weekend without a fact stays blank.');
        $this->assertSame('vacation', $cells[5]['status']);
        $this->assertSame('vacation', $cells[6]['status']);
        $this->assertSame('planned', $cells[7]['status']);
        $this->assertSame('none', $cells[8]['status'], 'Today is not finished yet.');
        $this->assertSame('none', $cells[20]['status'], 'Future days stay blank.');

        // 5 saat fakt + 2 × 8 saat plan = 21 saat; 3 gün.
        $this->assertSame('21', $rows[0]['total_hours']);
        $this->assertSame(3, $rows[0]['total_days']);

        $component->assertSee(__('attendance::puantaj.legend.descriptions.full_day', ['hours' => '8']));
        $this->assertDatabaseCount('attendance_daily_ledgers', 1);
    }

    public function test_planned_default_follows_the_assigned_shift_and_ignores_days_before_hiring(): void
    {
        Carbon::setTestNow('2026-10-08 10:00:00');
        $this->actingAs($this->authorizedUser());
        $this->configureDefaultShift('09:00', '17:00', 60);
        $personnel = $this->makePersonnel('TBNORM2', '2026-10-05');

        $rows = Livewire::test(PuantajGrid::class, ['year' => 2026, 'month' => 10])->viewData('rows');
        $cells = $rows[0]['cells'];

        $this->assertSame('none', $cells[2]['status']);
        $this->assertSame('planned', $cells[5]['status']);
        $this->assertSame('7', $cells[5]['display']);
        $this->assertSame($personnel->tabel_no, $rows[0]['personnel']->tabel_no);
    }

    private function configureDefaultShift(string $start, string $end, int $breakMinutes): AttendanceShift
    {
        $shift = $this->makeShift($start, $end, $breakMinutes);

        AttendanceSetting::query()->create([
            'scope_type' => 'global',
            'scope_id' => null,
            'timezone' => 'Asia/Baku',
            'default_shift_id' => $shift->id,
            'late_grace_minutes' => 0,
            'early_leave_grace_minutes' => 0,
            'rounding_policy' => 'none',
            'rounding_step_minutes' => 5,
            'overtime_policy' => 'by_approval',
            'is_active' => true,
        ]);

        return $shift;
    }

    private function makeShift(string $start, string $end, int $breakMinutes): AttendanceShift
    {
        return AttendanceShift::query()->create([
            'name' => 'Standart',
            'start_time' => $start,
            'end_time' => $end,
            'break_minutes' => $breakMinutes,
            'is_night_shift' => false,
            'in_flex_before_minutes' => 0,
            'in_flex_after_minutes' => 0,
            'out_flex_before_minutes' => 0,
            'out_flex_after_minutes' => 0,
            'is_active' => true,
        ]);
    }

    private function makePersonnel(string $tabelNo, string $joinDate = '2026-01-01'): Personnel
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
            'join_work_date' => $joinDate,
            'added_by' => $user->id,
            'is_pending' => false,
        ]));
    }

    private function authorizedUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'Puantaj Norm User', 'guard_name' => 'web']);
        $role->syncPermissions([Permission::findOrCreate('show-attendance', 'web')]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return grantAllStructures($user);
    }
}
