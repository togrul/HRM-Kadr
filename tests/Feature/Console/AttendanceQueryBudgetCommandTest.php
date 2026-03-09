<?php

namespace Tests\Feature\Console;

use App\Models\AttendanceDailyLedger;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttendanceQueryBudgetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_skip_when_dataset_is_empty_and_allow_empty_enabled(): void
    {
        $exitCode = Artisan::call('attendance:query-budget', [
            '--allow-empty' => true,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertTrue((bool) data_get($payload, 'summary.skipped'));
        $this->assertSame('attendance_dataset_empty', data_get($payload, 'summary.reason'));
        $this->assertCount(0, data_get($payload, 'results', []));
    }

    public function test_it_reports_overview_daily_and_puantaj_budgets(): void
    {
        $personnel = $this->makePersonnel();

        AttendanceDailyLedger::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-03-05',
            'scheduled_minutes' => 540,
            'worked_minutes' => 510,
            'break_minutes' => 30,
            'overtime_minutes' => 0,
            'late_minutes' => 15,
            'early_leave_minutes' => 0,
            'attendance_status' => 'present',
            'absence_code' => null,
            'source_summary' => 'system',
            'is_locked' => false,
            'meta' => null,
        ]);

        $exitCode = Artisan::call('attendance:query-budget', [
            '--year' => 2026,
            '--month' => 3,
            '--date' => '2026-03-05',
            '--overview-budget' => 200,
            '--daily-budget' => 200,
            '--puantaj-budget' => 200,
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertSame(3, data_get($payload, 'summary.passed_probes'));
        $this->assertCount(3, data_get($payload, 'results'));
        $this->assertSame('overview_build', data_get($payload, 'results.0.flow'));
        $this->assertSame('daily_monitor_load', data_get($payload, 'results.1.flow'));
        $this->assertSame('puantaj_grid_load', data_get($payload, 'results.2.flow'));
    }

    public function test_it_stays_within_tightened_default_query_budgets_for_larger_dataset(): void
    {
        foreach (range(1, 25) as $index) {
            $personnel = $this->makePersonnel([
                'surname' => 'Doe'.$index,
                'name' => 'John'.$index,
            ]);

            AttendanceDailyLedger::query()->create([
                'tabel_no' => $personnel->tabel_no,
                'date' => '2026-03-05',
                'scheduled_minutes' => 540,
                'worked_minutes' => 510,
                'break_minutes' => 30,
                'overtime_minutes' => 0,
                'late_minutes' => 15,
                'early_leave_minutes' => 0,
                'attendance_status' => 'present',
                'absence_code' => null,
                'source_summary' => 'system',
                'is_locked' => false,
                'meta' => null,
            ]);
        }

        $exitCode = Artisan::call('attendance:query-budget', [
            '--year' => 2026,
            '--month' => 3,
            '--date' => '2026-03-05',
            '--json' => true,
        ]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, data_get($payload, 'summary.failed_probes'));
        $this->assertSame(0, data_get($payload, 'summary.over_budget_probes'));
        $this->assertLessThanOrEqual(config('attendance.performance.query_budget.overview_build'), (int) data_get($payload, 'results.0.queries'));
        $this->assertLessThanOrEqual(config('attendance.performance.query_budget.daily_monitor_load'), (int) data_get($payload, 'results.1.queries'));
        $this->assertLessThanOrEqual(config('attendance.performance.query_budget.puantaj_grid_load'), (int) data_get($payload, 'results.2.queries'));
    }

    private function makePersonnel(array $overrides = []): Personnel
    {
        $user = User::query()->first() ?? User::factory()->create();

        $country = Country::query()->first() ?? Country::query()->create([
            'id' => 1,
            'code' => 'AZ',
        ]);

        if (! EducationDegree::query()->whereKey(1)->exists()) {
            EducationDegree::query()->create([
                'id' => 1,
                'title_az' => 'Bakalavr',
                'title_en' => 'Bachelor',
                'title_ru' => 'Bakalavr',
            ]);
        }

        if (! WorkNorm::query()->whereKey(1)->exists()) {
            WorkNorm::query()->create([
                'id' => 1,
                'name_az' => 'Tam',
                'name_en' => 'Full',
                'name_ru' => 'Polniy',
            ]);
        }

        $structure = Structure::query()->first() ?? Structure::query()->create([
            'name' => 'HQ',
            'shortname' => 'HQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 10,
            'level' => 1,
        ]);

        $position = Position::query()->first() ?? Position::query()->create([
            'id' => 1,
            'name' => 'Officer',
        ]);

        $payload = array_merge([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'John',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '994501112233',
            'nationality_id' => $country->id,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => $user->id,
            'is_pending' => false,
        ], $overrides);

        return Personnel::withoutEvents(fn () => Personnel::query()->create($payload));
    }
}
