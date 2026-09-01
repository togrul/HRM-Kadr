<?php

namespace App\Modules\Integration\Console\Commands;

use App\Modules\Integration\Application\Services\FinanceImportService;
use App\Modules\Integration\Infrastructure\FinanceClient;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Pull back what the finance system owns.
 *
 * Safe to run on a schedule: with no finance connection configured it says so
 * and exits successfully, because a standalone installation is a normal state
 * and not a failure to report every few minutes.
 */
class PullFromFinanceCommand extends Command
{
    protected $signature = 'integration:pull-finance
        {--year= : Year for payslips and the calendar (default: current)}
        {--month= : Month for payslips (default: previous month)}
        {--only=* : Limit to some feeds: payslips, periods, calendar, trips}';

    protected $description = 'Pull payslips, period state, calendar and business trips from the finance system';

    public function handle(FinanceClient $client, FinanceImportService $import): int
    {
        if (! $client->isConfigured()) {
            $this->components->info('No finance connection configured — nothing to pull.');

            return self::SUCCESS;
        }

        $year = (int) ($this->option('year') ?: now()->year);

        // Payroll is normally read after the month closes, so the previous
        // month is the useful default rather than the current one.
        $month = (int) ($this->option('month') ?: now()->subMonth()->month);

        /** @var list<string> $only */
        $only = (array) $this->option('only');
        $wants = fn (string $feed): bool => $only === [] || in_array($feed, $only, true);

        $failed = 0;

        foreach ([
            'periods' => fn (): string => $this->describe($import->periodState($year - 1)),
            'calendar' => fn (): string => $this->describe($import->calendar($year)),
            'trips' => fn (): string => $this->describe($import->businessTrips()),
            'payslips' => fn (): string => $this->describe($import->payslips($year, $month)),
        ] as $feed => $run) {
            if (! $wants($feed)) {
                continue;
            }

            try {
                $this->components->info($feed.': '.$run());
            } catch (RuntimeException $e) {
                $this->components->error($feed.': '.$e->getMessage());
                $failed++;
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @param array<string, int> $stats */
    private function describe(array $stats): string
    {
        return collect($stats)->map(fn (int $v, string $k): string => "{$k}={$v}")->implode(' · ');
    }
}
