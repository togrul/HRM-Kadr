<?php

namespace App\Modules\Vacation\Application\Services;

use App\Models\Personnel;
use App\Models\Vacation;
use App\Models\VacationBalanceEntry;
use App\Models\VacationWorkYear;
use App\Services\Vacation\VacationBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Köhnə təqvim ili balanslarını (`vacations`) iş ili uçotuna köçürür — dağıtmadan və geri
 * qaytarıla bilən şəkildə:
 *   - hər işçinin ƏN SON təqvim ili sətri onun cari qalığıdır (köhnə sistemdə əvvəlki illərin qalığı
 *     ya növbəti ilin cəminə əlavə olunurdu, ya da sıfırlanırdı — hər iki halda yalnız son sətir
 *     etibarlıdır). O, uçotun başlama tarixində davam edən iş ilinə «köhnə» (legacy) iş ili kimi
 *     yazılır: hüquq = vacation_days_total, istifadə = cəm − qalıq; planlaşdırılmış ay da keçir;
 *   - işçinin bütün köhnə sətirləri `migrated_at` ilə işarələnir, cədvəl silinmir;
 *   - rollback(): köçürülmüş hərəkətləri və (başqa hərəkəti olmayan) köhnə iş illərini silir,
 *     işarələri götürür.
 */
class LegacyVacationMigrator
{
    public const SOURCE_PREFIX = 'legacy:vacations:';

    public function __construct(
        private readonly VacationBalanceService $balances,
        private readonly VacationSettings $settings,
    ) {}

    /**
     * @return array{employees:int,migrated:int,skipped:int}
     */
    public function migrate(bool $dryRun = false): array
    {
        $result = ['employees' => 0, 'migrated' => 0, 'skipped' => 0];
        $cutover = $this->settings->ledgerStart() ?? CarbonImmutable::today();
        $now = now();

        $tabelNos = Vacation::query()->whereNull('migrated_at')->distinct()->pluck('tabel_no');

        foreach ($tabelNos as $tabelNo) {
            $result['employees']++;
            $rows = Vacation::query()->where('tabel_no', $tabelNo)->whereNull('migrated_at')->orderByDesc('year')->orderByDesc('id')->get();
            $latest = $rows->first();
            $personnel = Personnel::query()->withTrashed()->where('tabel_no', $tabelNo)->first();
            $periods = $personnel ? $this->balances->periodsFor($personnel, $cutover) : [];
            $period = end($periods) ?: null;

            if ($dryRun) {
                $period ? $result['migrated']++ : $result['skipped']++;

                continue;
            }

            DB::transaction(function () use ($rows, $latest, $personnel, $period, $now, &$result): void {
                if ($latest !== null && $personnel !== null && $period !== null) {
                    $this->migrateRow($latest, $personnel, $period);
                    $result['migrated']++;
                } else {
                    $result['skipped']++;
                }

                Vacation::query()->whereKey($rows->modelKeys())->update(['migrated_at' => $now]);
            });
        }

        return $result;
    }

    /**
     * @return array{entries:int,work_years:int,rows:int}
     */
    public function rollback(): array
    {
        $result = ['entries' => 0, 'work_years' => 0, 'rows' => 0];

        DB::transaction(function () use (&$result): void {
            $result['entries'] = VacationBalanceEntry::query()->where('source', 'like', self::SOURCE_PREFIX.'%')->delete();

            VacationWorkYear::query()->where('strategy', VacationWorkYear::STRATEGY_LEGACY)->get()->each(function (VacationWorkYear $row) use (&$result): void {
                if ($row->entries()->exists()) {
                    // Keçiddən sonra bu iş ilindən istifadə olunub: sətir qalır, hüququ yenidən hesablanır.
                    $row->forceFill(['strategy' => 'civil', 'legacy_vacation_id' => null])->save();
                    $personnel = Personnel::query()->withTrashed()->where('tabel_no', $row->tabel_no)->first();

                    if ($personnel) {
                        $this->balances->recalculate($personnel);
                    }

                    return;
                }

                $row->delete();
                $result['work_years']++;
            });

            if (Schema::hasColumn('vacations', 'migrated_at')) {
                $result['rows'] = Vacation::query()->whereNotNull('migrated_at')->update(['migrated_at' => null]);
            }
        });

        return $result;
    }

    private function migrateRow(Vacation $row, Personnel $personnel, WorkYearPeriod $period): void
    {
        $total = max(0, (int) $row->vacation_days_total);
        $remaining = (int) $row->remaining_days;
        $used = max(0, $total - $remaining);

        $workYear = VacationWorkYear::query()->updateOrCreate(
            ['tabel_no' => $personnel->tabel_no, 'kind' => VacationWorkYear::KIND_ANNUAL, 'sequence' => $period->sequence],
            [
                'starts_on' => $period->start->toDateString(),
                'ends_on' => $period->end->toDateString(),
                'entitled_days' => $total,
                'breakdown' => [
                    'base' => $total,
                    'seniority' => 0,
                    'children' => 0,
                    'conditions' => 0,
                    'seniority_years' => null,
                    'exclusive' => false,
                    'strategy' => VacationWorkYear::STRATEGY_LEGACY,
                    'total' => $total,
                    'legacy_year' => (int) $row->year,
                ],
                'strategy' => VacationWorkYear::STRATEGY_LEGACY,
                'legacy_vacation_id' => $row->id,
                'reserved_month' => $row->reserved_date_month,
            ],
        );

        VacationBalanceEntry::query()->where('source', self::SOURCE_PREFIX.$row->id)->delete();

        if ($used > 0) {
            VacationBalanceEntry::query()->create([
                'work_year_id' => $workYear->id,
                'tabel_no' => $personnel->tabel_no,
                'kind' => VacationBalanceEntry::KIND_LEGACY_USAGE,
                'days' => -$used,
                'source' => self::SOURCE_PREFIX.$row->id,
                'note' => __('vacation::norms.legacy_note', ['year' => $row->year, 'total' => $total, 'remaining' => $remaining], config('app.locale')),
            ]);
        }
    }
}
