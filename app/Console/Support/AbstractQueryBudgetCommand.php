<?php

namespace App\Console\Support;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Shared harness for the per-module `*:query-budget` commands.
 *
 * Subclasses build their own probe list in handle() and call probe() for each
 * flow; the query-counting, user resolution and budget-resolution mechanics live
 * here instead of being copy-pasted into every command.
 */
abstract class AbstractQueryBudgetCommand extends Command
{
    /**
     * Resolve a budget from a CLI option, falling back to config, then a default.
     */
    protected function budget(string $option, string $configKey, int $default): int
    {
        return max(1, (int) ($this->option($option) ?: config($configKey, $default)));
    }

    /**
     * Run a callback while counting the queries it issues and compare to a budget.
     *
     * @return array{flow:string,status:string,queries:int,budget:int,over_budget:bool,elapsed_ms:float,db_time_ms:float,error:?string}
     */
    protected function probe(string $flow, int $budget, callable $callback): array
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

        return [
            'flow' => $flow,
            'status' => $status,
            'queries' => $queryCount,
            'budget' => $budget,
            'over_budget' => $queryCount > $budget,
            'elapsed_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'db_time_ms' => round((float) collect($queries)->sum(fn ($query) => (float) ($query['time'] ?? 0)), 2),
            'error' => $error,
        ];
    }

    /**
     * Find the first user that holds every given permission.
     */
    protected function resolveUserForPermissions(string ...$permissions): ?User
    {
        return User::query()
            ->orderBy('id')
            ->cursor()
            ->first(fn (User $user): bool => collect($permissions)->every(fn (string $permission) => $user->can($permission)));
    }
}
