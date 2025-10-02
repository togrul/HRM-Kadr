<?php

namespace App\Console\Commands;

use App\Enums\RankCategoryEnum;
use App\Models\Personnel;
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
        $workDurationInYears = $personnel->join_work_date->diffInYears($now);
        $workDurationInMonths = $personnel->join_work_date->diffInMonths($now);

        $rankCategory = $personnel->latestRank?->rank?->rankCategory;
        $vacationDays = $rankCategory
            ? $this->getRankedCategory($personnel, $rankCategory, $workDurationInYears, $workDurationInMonths)
            : $this->getNonRankedCategory($workDurationInYears, $workDurationInMonths);

        if (! $vacationReset) {
            $vacationDays += $personnel->yearlyVacation->sum('remaining_days');
        }

        $personnel->yearlyVacation()->firstOrCreate(
            ['year' => $now->year],
            ['reserved_date_month' => null, 'vacation_days_total' => $vacationDays, 'remaining_days' => $vacationDays, 'year' => $now->year]
        );
    }

    private function getRankedCategory($personnel, $rankCategory, int $workDurationInYears, int $workDurationInMonths)
    {
        return $workDurationInYears > 0
            ? $this->getVacationDaysByCategory($personnel, $rankCategory, $workDurationInYears)
            : $rankCategory->vacation_days_per_month * $workDurationInMonths;
    }

    private function getVacationDaysByCategory($personnel, $rankCategory, int $workDurationInYears)
    {
        // cavus veya gizirdirse herbi xidmet ve herbi is staji toplam 20 ilden coxdursa 40 gunluk olur.
        if (in_array($rankCategory->id, [RankCategoryEnum::CAVUS->value, RankCategoryEnum::GIZIR->value])) {
            $militaryDuration = $this->calculateMilitaryServiceDuration($personnel);
            return ($workDurationInYears + $militaryDuration >= 20) ? 40 : $rankCategory->vacation_days_count;
        }
        return $rankCategory->vacation_days_count;
    }

    private function calculateMilitaryServiceDuration($personnel): int
    {
        $militaryDuration = $personnel->military->sum(fn($military) => $military?->start_date->diffInYears($military?->end_date) ?? 0);
        $specialServiceDuration = $personnel->laborActivities->where('is_special_service', true)->where('is_current', false)
            ->sum(fn($special) => $special->join_date->diffInYears($special->leave_date));

        return $militaryDuration + $specialServiceDuration;
    }

    private function getNonRankedCategory(int $workDurationInYears, int $workDurationInMonths): int
    {
        return $workDurationInYears < 1 ? $workDurationInMonths * 2.5 : 30;
    }
}
