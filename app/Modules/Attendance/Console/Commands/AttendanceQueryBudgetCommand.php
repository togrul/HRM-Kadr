<?php

namespace App\Modules\Attendance\Console\Commands;

use App\Console\Support\AbstractQueryBudgetCommand;
use App\Models\AttendanceDailyLedger;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceDailyMonitorReadService;
use App\Modules\Attendance\Application\Services\AttendanceHistoryReadService;
use App\Modules\Attendance\Application\Services\AttendanceMonthLockService;
use App\Modules\Attendance\Application\Services\AttendanceOverviewService;
use App\Modules\Attendance\Application\Services\AttendancePuantajReadService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AttendanceQueryBudgetCommand extends AbstractQueryBudgetCommand
{
    protected $signature = 'attendance:query-budget
        {--year= : Target year}
        {--month= : Target month}
        {--date= : Target date for daily monitor probe (Y-m-d)}
        {--per-page=20 : Probe pagination size}
        {--allow-empty : Return success when attendance data set is empty}
        {--overview-budget= : Max query count for overview flow}
        {--daily-budget= : Max query count for daily monitor flow}
        {--puantaj-budget= : Max query count for puantaj flow}
        {--history-budget= : Max query count for history log flow}
        {--month-close-budget= : Max query count for month close flow}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for Attendance overview/daily monitor/puantaj/history/month close flows';

    public function handle(
        AttendanceOverviewService $overviewService,
        AttendanceDailyMonitorReadService $dailyMonitorReadService,
        AttendancePuantajReadService $puantajReadService,
        AttendanceHistoryReadService $historyReadService,
        AttendanceMonthLockService $monthLockService
    ): int {
        if (! $this->hasRequiredTables()) {
            $this->error('Attendance core tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $now = Carbon::now();
        $year = is_numeric($this->option('year')) ? (int) $this->option('year') : (int) $now->year;
        $month = is_numeric($this->option('month')) ? (int) $this->option('month') : (int) $now->month;
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->toDateString()
            : Carbon::createFromDate($year, $month, 1)->toDateString();
        $perPage = max(5, (int) $this->option('per-page'));

        $configuredBudgets = [
            'overview_build' => (int) config('attendance.performance.query_budget.overview_build', 20),
            'daily_monitor_load' => (int) config('attendance.performance.query_budget.daily_monitor_load', 25),
            'puantaj_grid_load' => (int) config('attendance.performance.query_budget.puantaj_grid_load', 30),
            'history_log_load' => (int) config('attendance.performance.query_budget.history_log_load', 12),
            'month_close_status_load' => (int) config('attendance.performance.query_budget.month_close_status_load', 10),
        ];
        $budgets = [
            'overview_build' => max(1, (int) ($this->option('overview-budget') ?: $configuredBudgets['overview_build'])),
            'daily_monitor_load' => max(1, (int) ($this->option('daily-budget') ?: $configuredBudgets['daily_monitor_load'])),
            'puantaj_grid_load' => max(1, (int) ($this->option('puantaj-budget') ?: $configuredBudgets['puantaj_grid_load'])),
            'history_log_load' => max(1, (int) ($this->option('history-budget') ?: $configuredBudgets['history_log_load'])),
            'month_close_status_load' => max(1, (int) ($this->option('month-close-budget') ?: $configuredBudgets['month_close_status_load'])),
        ];

        $hasData = DB::table('attendance_daily_ledgers')->exists()
            || DB::table('attendance_manual_entries')->exists()
            || DB::table('attendance_raw_punches')->exists();

        $results = [];
        $seededBenchmarkFixture = false;
        $fixtureTransactionStarted = false;

        try {
            if (! $hasData && (bool) $this->option('allow-empty')) {
                DB::beginTransaction();
                $fixtureTransactionStarted = true;
                $this->seedBenchmarkFixture($date);
                $seededBenchmarkFixture = true;
            }

            $results[] = $this->probe('overview_build', $budgets['overview_build'], function () use ($overviewService, $year, $month): void {
                $overviewService->build($year, $month, null, false);
            });
            $results[] = $this->probe('daily_monitor_load', $budgets['daily_monitor_load'], function () use ($dailyMonitorReadService, $date, $perPage): void {
                $dailyMonitorReadService->paginateRows($date, '', 'all', $perPage);
                $dailyMonitorReadService->totals($date, '', 'all');
            });
            $results[] = $this->probe('puantaj_grid_load', $budgets['puantaj_grid_load'], function () use ($puantajReadService, $year, $month, $perPage): void {
                $from = Carbon::createFromDate($year, $month, 1)->startOfMonth();
                $to = $from->copy()->endOfMonth();

                $page = $puantajReadService->paginatePersonnels('', $perPage, [], $from, $to);
                $tabelNos = $page->getCollection()->pluck('tabel_no')->filter()->values()->all();
                $puantajReadService->loadLedgerMap($tabelNos, $from, $to);
                $puantajReadService->globalCalendarDayTypeByDate($from, $to);
            });
            $results[] = $this->probe('history_log_load', $budgets['history_log_load'], function () use ($historyReadService, $year, $month, $perPage): void {
                $from = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
                $to = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

                $historyReadService->paginateRows('all', '', $from, $to, $perPage);
                $historyReadService->totals('all', '', $from, $to);
            });
            $results[] = $this->probe('month_close_status_load', $budgets['month_close_status_load'], function () use ($monthLockService, $year, $month): void {
                $monthLockService->periodStatus($year, $month);
                $monthLockService->exportStatus($year, $month);
            });

            $summary = [
                'year' => $year,
                'month' => $month,
                'date' => $date,
                'failed_probes' => collect($results)->where('status', 'failed')->count(),
                'over_budget_probes' => collect($results)->where('over_budget', true)->count(),
                'passed_probes' => collect($results)->where('status', 'ok')->where('over_budget', false)->count(),
                'seeded_benchmark_fixture' => $seededBenchmarkFixture,
            ];
        } finally {
            if ($fixtureTransactionStarted) {
                DB::rollBack();
            }
        }

        $payload = [
            'summary' => $summary,
            'results' => $results,
        ];

        if ((bool) $this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['flow', 'status', 'queries', 'budget', 'over_budget', 'elapsed_ms', 'db_time_ms', 'error'],
                collect($results)->map(fn (array $result) => [
                    $result['flow'],
                    $result['status'],
                    $result['queries'],
                    $result['budget'],
                    $result['over_budget'] ? 'yes' : 'no',
                    $result['elapsed_ms'],
                    $result['db_time_ms'],
                    $result['error'] ?? '-',
                ])->all()
            );
            $this->newLine();
            $this->table(
                ['metric', 'value'],
                collect($summary)->map(fn ($value, $metric) => [$metric, (string) $value])->values()->all()
            );
        }

        return ($summary['failed_probes'] === 0 && $summary['over_budget_probes'] === 0)
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function hasRequiredTables(): bool
    {
        foreach ([
            'attendance_daily_ledgers',
            'attendance_manual_entries',
            'attendance_raw_punches',
            'attendance_daily_structure_summaries',
            'attendance_monthly_summaries',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return Schema::connection(config('activitylog.database_connection'))
            ->hasTable(config('activitylog.table_name'));
    }

    private function seedBenchmarkFixture(string $date): void
    {
        $user = User::query()->first() ?? User::query()->create([
            'name' => 'Attendance Benchmark',
            'email' => 'attendance-benchmark@example.test',
            'password' => Hash::make('password'),
        ]);

        $country = Country::query()->first() ?? Country::query()->create([
            'id' => 1,
            'code' => 'AZ',
        ]);

        EducationDegree::query()->firstOrCreate(['id' => 1], [
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bakalavr',
        ]);

        WorkNorm::query()->firstOrCreate(['id' => 1], [
            'name_az' => 'Tam',
            'name_en' => 'Full',
            'name_ru' => 'Polniy',
        ]);

        $structure = Structure::query()->first() ?? Structure::query()->create([
            'name' => 'Benchmark HQ',
            'shortname' => 'BHQ',
            'parent_id' => null,
            'coefficient' => 1.00,
            'code' => 9000,
            'level' => 1,
        ]);

        $position = Position::query()->first() ?? Position::query()->create([
            'id' => 1,
            'name' => 'Benchmark Officer',
        ]);

        $personnel = Personnel::query()->first() ?? Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'QB-FIXTURE-001',
            'surname' => 'Benchmark',
            'name' => 'Attendance',
            'patronymic' => 'Probe',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '994501112233',
            'nationality_id' => $country->id,
            'pin' => 'QB000001',
            'residental_address' => 'Benchmark address',
            'education_degree_id' => 1,
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'work_norm_id' => 1,
            'join_work_date' => $date,
            'added_by' => $user->id,
            'is_pending' => false,
        ]));

        AttendanceDailyLedger::query()->updateOrCreate([
            'tabel_no' => $personnel->tabel_no,
            'date' => $date,
        ], [
            'scheduled_minutes' => 540,
            'worked_minutes' => 510,
            'break_minutes' => 30,
            'overtime_minutes' => 0,
            'late_minutes' => 15,
            'early_leave_minutes' => 0,
            'attendance_status' => 'present',
            'absence_code' => null,
            'source_summary' => 'benchmark',
            'is_locked' => false,
            'meta' => null,
        ]);
    }
}
