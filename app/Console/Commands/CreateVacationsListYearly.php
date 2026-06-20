<?php

namespace App\Console\Commands;

use App\Models\Personnel;
use App\Services\Vacation\VacationBalanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateVacationsListYearly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:vacations-list-yearly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a list of personnels yearly vacations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $vacationReset = (bool) cache('settings')['Vacation will reset?'];
        Personnel::with([
            'latestRank.rank.rankCategory',
            'yearlyVacation',
            'military',
            'laborActivities',
        ])
            ->whereNull('leave_work_date')
            ->get()
            ->each(fn ($personnel) => $this->processPersonnel($personnel, $now, $vacationReset));

        $this->line('Successfully!');
    }

    private function processPersonnel($personnel, Carbon $now, bool $vacationReset): void
    {
        // Entitlement rules live in VacationBalanceService (shared with the order-flow
        // balance check), so both paths compute the same number of days.
        $vacationDays = app(VacationBalanceService::class)->entitlementDays($personnel, $now);

        if (! $vacationReset) {
            $vacationDays += $personnel->yearlyVacation->sum('remaining_days');
        }

        $personnel->yearlyVacation()->firstOrCreate(
            ['year' => $now->year],
            ['reserved_date_month' => null, 'vacation_days_total' => $vacationDays, 'remaining_days' => $vacationDays, 'year' => $now->year]
        );
    }
}
