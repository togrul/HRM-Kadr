<?php

namespace App\Console\Commands;

use App\Models\Personnel;
use App\Services\Vacation\VacationBalanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * İşləyən işçilər üçün bu günədək başlamış iş illərinin məzuniyyət hüququnu yazır (ƏM m.113.3).
 * Əvvəllər təqvim ili üzrə `vacations` sətri yaradırdı; indi hüquq iş ili üzrə
 * (vacation_work_years) saxlanılır, köhnə cədvəl yalnız tarixçə kimi qalır. İstənilən vaxt
 * təkrar işlədilə bilər — mövcud iş illəri təkrarlanmır.
 */
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
    protected $description = 'Write the vacation entitlement of every work year started so far (active employees)';

    public function handle(VacationBalanceService $balances): void
    {
        $now = Carbon::now();
        $count = 0;

        Personnel::with(['latestRank.rank.rankCategory', 'military', 'laborActivities'])
            ->whereNull('leave_work_date')
            ->whereNotNull('join_work_date')
            ->lazyById(200)
            ->each(function (Personnel $personnel) use ($balances, $now, &$count): void {
                $balances->materialize($personnel, $now);
                $count++;
            });

        $this->line("Successfully! ({$count})");
    }
}
