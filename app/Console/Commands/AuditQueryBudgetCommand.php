<?php

namespace App\Console\Commands;

use App\Models\AuditActivity;
use App\Models\User;
use App\Modules\Audit\Livewire\ActivityLogDashboard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Throwable;

class AuditQueryBudgetCommand extends Command
{
    protected $signature = 'audit:query-budget
        {--render-budget=15 : Max query count for audit dashboard render}
        {--allow-empty : Seed a temporary audit row when activity_log is empty}
        {--json : Print report as JSON}';

    protected $description = 'Run query-budget checks for the Audit log dashboard';

    public function handle(): int
    {
        $connection = (string) config('activitylog.database_connection');
        $table = (string) config('activitylog.table_name', 'activity_log');

        if (! Schema::connection($connection)->hasTable($table)) {
            $this->error("Audit table [{$connection}.{$table}] is missing.");

            return self::FAILURE;
        }

        $mainTransaction = false;
        $auditTransaction = false;
        $seededFixture = false;

        try {
            DB::beginTransaction();
            $mainTransaction = true;
            $user = User::query()->create([
                'name' => 'Audit Query Budget',
                'email' => 'audit-query-budget-'.uniqid().'@example.test',
                'password' => bcrypt('password'),
            ]);

            $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));

            if (! AuditActivity::query()->exists()) {
                if (! $this->option('allow-empty')) {
                    $this->error('Audit log is empty. Re-run with --allow-empty for a temporary benchmark row.');

                    return self::FAILURE;
                }

                DB::connection($connection)->beginTransaction();
                $auditTransaction = true;
                AuditActivity::query()->create([
                    'log_name' => 'benchmark',
                    'description' => 'Personnel profile opened',
                    'event' => 'profile_opened',
                    'causer_type' => User::class,
                    'causer_id' => $user->id,
                    'properties' => [
                        'viewed_personnel_fullname' => 'Audit Benchmark',
                        'viewed_personnel_tabel_no' => 'AUDIT-QB',
                        'ip' => '127.0.0.1',
                    ],
                ]);
                $seededFixture = true;
            }

            $result = $this->probe('dashboard_render', (int) $this->option('render-budget'), function () use ($user): void {
                Auth::login($user);

                Livewire::actingAs($user)
                    ->test(ActivityLogDashboard::class)
                    ->assertOk();
            });

            $payload = [
                'summary' => [
                    'connection' => $connection,
                    'table' => $table,
                    'failed_probes' => $result['status'] === 'failed' ? 1 : 0,
                    'over_budget_probes' => $result['over_budget'] ? 1 : 0,
                    'seeded_benchmark_fixture' => $seededFixture,
                ],
                'results' => [$result],
            ];

            if ($this->option('json')) {
                $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->table(
                    ['flow', 'status', 'queries', 'budget', 'over_budget', 'elapsed_ms', 'db_time_ms', 'error'],
                    [[
                        $result['flow'],
                        $result['status'],
                        $result['queries'],
                        $result['budget'],
                        $result['over_budget'] ? 'yes' : 'no',
                        $result['elapsed_ms'],
                        $result['db_time_ms'],
                        $result['error'] ?? '-',
                    ]]
                );
            }

            return ($payload['summary']['failed_probes'] === 0 && $payload['summary']['over_budget_probes'] === 0)
                ? self::SUCCESS
                : self::FAILURE;
        } finally {
            if ($auditTransaction) {
                DB::connection($connection)->rollBack();
            }

            if ($mainTransaction) {
                DB::rollBack();
            }
        }
    }

    private function probe(string $flow, int $budget, callable $callback): array
    {
        $queries = 0;
        $dbTimeMs = 0.0;
        $startedAt = microtime(true);

        DB::flushQueryLog();
        DB::connection(config('activitylog.database_connection'))->flushQueryLog();

        DB::listen(function ($query) use (&$queries, &$dbTimeMs): void {
            $queries++;
            $dbTimeMs += (float) $query->time;
        });

        try {
            $callback();
            $status = 'ok';
            $error = null;
        } catch (Throwable $exception) {
            $status = 'failed';
            $error = $exception->getMessage();
        }

        return [
            'flow' => $flow,
            'status' => $status,
            'queries' => $queries,
            'budget' => $budget,
            'over_budget' => $queries > $budget,
            'elapsed_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'db_time_ms' => round($dbTimeMs, 2),
            'error' => $error,
        ];
    }
}
