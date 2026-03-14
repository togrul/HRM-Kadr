<?php

namespace App\Modules\Attendance\Console\Commands;

use App\Modules\Attendance\Application\Services\AttendanceDailyMonitorReadService;
use App\Modules\Attendance\Application\Services\AttendanceHistoryReadService;
use App\Modules\Attendance\Application\Services\AttendanceMonthLockService;
use App\Modules\Attendance\Application\Services\AttendanceOverviewService;
use App\Modules\Attendance\Application\Services\AttendancePuantajReadService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AttendanceQueryBudgetCommand extends Command
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

        if (! $hasData && (bool) $this->option('allow-empty')) {
            $payload = [
                'summary' => [
                    'failed_probes' => 0,
                    'over_budget_probes' => 0,
                    'passed_probes' => 0,
                    'skipped' => true,
                    'reason' => 'attendance_dataset_empty',
                ],
                'results' => [],
            ];

            if ((bool) $this->option('json')) {
                $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->warn('Attendance dataset is empty. Skipping probes because --allow-empty is enabled.');
            }

            return self::SUCCESS;
        }

        $results = [];
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
        ];

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
            'activity_log',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{
     *   flow:string,
     *   status:string,
     *   queries:int,
     *   budget:int,
     *   over_budget:bool,
     *   elapsed_ms:float,
     *   db_time_ms:float,
     *   error:?string
     * }
     */
    private function probe(string $flow, int $budget, callable $callback): array
    {
        $connection = DB::connection();
        $wasLogging = method_exists($connection, 'logging') ? (bool) $connection->logging() : false;

        $connection->flushQueryLog();
        $connection->enableQueryLog();

        $startedAt = microtime(true);
        $status = 'ok';
        $error = null;

        try {
            $callback();
        } catch (Throwable $throwable) {
            $status = 'failed';
            $error = $throwable->getMessage();
        } finally {
            $queries = $connection->getQueryLog();
            if (! $wasLogging) {
                $connection->disableQueryLog();
            }
        }

        $queryCount = count($queries);
        $dbTimeMs = round((float) collect($queries)->sum(fn ($query) => (float) ($query['time'] ?? 0)), 2);
        $elapsedMs = round((microtime(true) - $startedAt) * 1000, 2);

        return [
            'flow' => $flow,
            'status' => $status,
            'queries' => $queryCount,
            'budget' => $budget,
            'over_budget' => $queryCount > $budget,
            'elapsed_ms' => $elapsedMs,
            'db_time_ms' => $dbTimeMs,
            'error' => $error,
        ];
    }
}
