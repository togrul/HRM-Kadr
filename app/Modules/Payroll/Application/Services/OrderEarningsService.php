<?php

namespace App\Modules\Payroll\Application\Services;

use App\Modules\Attendance\Contracts\OrderRestDayWork;
use App\Modules\Attendance\Contracts\PayrollRestDayWork;
use App\Modules\Attendance\Contracts\PayrollWorkedDays;
use App\Modules\Compensation\Contracts\SubstitutionRegister;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use Carbon\CarbonImmutable;

/**
 * Earnings that approved orders entitle an employee to for the pay month, read at
 * calculation time from the module that owns each fact (so a recalculation follows the
 * orders and a revoked order's pay disappears on the next calculation). Legal basis and
 * what is verified: docs/payroll-legal-basis.md.
 *
 * - Rest-day / holiday work, ƏM m.164.1 (monthly salaried employees): on top of the salary
 *   at least the hourly POSITION salary (vəzifə maaşı — allowances are not part of the
 *   base) for work done within the monthly working-time norm, at least twice it for work
 *   beyond the norm. Hourly position salary = base salary ÷ the month's norm hours (common
 *   practice, not spelled out in the Code). The work counts as within the norm only as far
 *   as the employee's own norm working days went unworked (vacation, leave — not business
 *   trips, which are working time); otherwise it is beyond the norm. "day_off" days
 *   (m.164.2: another day of rest instead of pay) add no money.
 * - Substitution (əvəzetmə), ƏM m.162: when the substituted colleague's position salary is
 *   higher, the difference between the two salaries (m.162.1); otherwise the extra pay
 *   agreed on the order — a percent of the substitute's own salary or a fixed monthly
 *   amount (m.162.2). An agreed extra above the difference is kept (more favourable).
 *   Due only for days the duties were performed: the monthly figure × the substitute's
 *   working days in the substitution period on which they were not away (vacation, leave,
 *   business trip) ÷ the month's norm working days.
 *
 * Both are taxable and social-insurable earnings and go through the same statutory path
 * as the rest of the payslip. Each line names the records it was built from (`sources`),
 * so locking can tell whether the order facts changed after the calculation.
 */
class OrderEarningsService
{
    public const REST_DAY_WORK = 'rest_day_work';

    public const SUBSTITUTION = 'substitution';

    /** Line codes this service produces. */
    public const CODES = [self::REST_DAY_WORK, self::SUBSTITUTION];

    /** ƏM m.164.1: work beyond the monthly norm — at least twice the hourly position salary. */
    public const BEYOND_NORM_MULTIPLIER = 2;

    /** ƏM m.164.1: work within the monthly norm — at least the hourly position salary. */
    public const WITHIN_NORM_MULTIPLIER = 1;

    public function __construct(
        private readonly PayrollRestDayWork $restDayWork,
        private readonly PayrollWorkedDays $workedDays,
        private readonly SubstitutionRegister $substitutions,
        private readonly CompensationReadRepository $compensation,
    ) {}

    /**
     * @return list<array<string,mixed>>
     */
    public function linesFor(string $tabelNo, float $baseAmount, int $year, int $month): array
    {
        return array_values(array_filter([
            $this->restDayWorkLine($tabelNo, $baseAmount, $year, $month),
            ...$this->substitutionLines($tabelNo, $baseAmount, $year, $month),
        ]));
    }

    /**
     * @return array<string,mixed>|null
     */
    private function restDayWorkLine(string $tabelNo, float $baseAmount, int $year, int $month): ?array
    {
        $paidMinutes = 0;
        $days = 0;
        $sources = [];

        foreach ($this->restDayWork->orderWorkFor([$tabelNo], $year, $month)[$tabelNo] ?? [] as $day) {
            if ($day['compensation'] !== OrderRestDayWork::COMPENSATION_DOUBLE_PAY) {
                continue;
            }

            $paidMinutes += $day['minutes'];
            $days++;
            $sources[] = self::REST_DAY_WORK.':'.$day['id'].':'.$day['date'].':'.$day['minutes'];
        }

        if ($paidMinutes <= 0 || $baseAmount <= 0) {
            return null;
        }

        $normMinutes = $this->restDayWork->monthNormMinutes($tabelNo, $year, $month);

        if ($normMinutes <= 0) {
            return null;
        }

        // Norm time the employee did not work (vacation, leave) is room "within the norm".
        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $presence = $this->workedDays->workdays($tabelNo, $monthStart->toDateString(), $monthStart->endOfMonth()->toDateString());
        $workdays = count($presence['workdays']);
        $awayDays = count(array_filter($presence['absent'], fn (string $kind): bool => $kind !== PayrollWorkedDays::BUSINESS_TRIP));
        $spareMinutes = $workdays > 0 ? (int) round($normMinutes * $awayDays / $workdays) : 0;

        $withinMinutes = min($paidMinutes, $spareMinutes);
        $beyondMinutes = $paidMinutes - $withinMinutes;

        $amount = round($baseAmount * ($withinMinutes * self::WITHIN_NORM_MULTIPLIER + $beyondMinutes * self::BEYOND_NORM_MULTIPLIER) / $normMinutes, 2);

        if ($withinMinutes > 0) {
            $sources[] = 'within_norm:'.$withinMinutes;
        }

        $name = $withinMinutes > 0
            ? __('payroll::dashboard.order_earnings.rest_day_work_mixed', ['days' => $days, 'hours' => $this->hours($paidMinutes), 'within' => $this->hours($withinMinutes)])
            : __('payroll::dashboard.order_earnings.rest_day_work', ['days' => $days, 'hours' => $this->hours($paidMinutes)]);

        return $this->line(self::REST_DAY_WORK, $name, $amount, 150, $sources);
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function substitutionLines(string $tabelNo, float $baseAmount, int $year, int $month): array
    {
        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth()->startOfDay();
        $rows = $this->substitutions->overlapping([$tabelNo], $monthStart->toDateString(), $monthEnd->toDateString())[$tabelNo] ?? [];

        if ($rows === []) {
            return [];
        }

        $normDays = count($this->workedDays->workdays($tabelNo, $monthStart->toDateString(), $monthEnd->toDateString())['workdays']);
        $substitutedBases = $this->compensation->baseAmountsFor(
            array_values(array_unique(array_filter(array_map(fn (array $row): ?string => $row['substituted_tabel_no'] ?? null, $rows)))),
            $monthEnd->toDateString(),
        );
        $lines = [];
        $sort = 160;

        foreach ($rows as $row) {
            $percent = (float) ($row['extra_pay_percent'] ?? 0);
            $agreed = $percent > 0 ? $baseAmount * $percent / 100 : (float) ($row['extra_pay_amount'] ?? 0);
            $substitutedBase = filled($row['substituted_tabel_no'] ?? null) ? (float) ($substitutedBases[$row['substituted_tabel_no']] ?? 0) : 0.0;
            $difference = max(0.0, $substitutedBase - $baseAmount);
            $monthly = max($difference, $agreed);

            if ($monthly <= 0 || $normDays <= 0) {
                continue;
            }

            $from = CarbonImmutable::parse($row['start_date'])->max($monthStart);
            $to = $row['end_date'] !== null ? CarbonImmutable::parse($row['end_date'])->min($monthEnd) : $monthEnd;
            $presence = $this->workedDays->workdays($tabelNo, $from->toDateString(), $to->toDateString());
            $days = count($presence['workdays']) - count($presence['absent']);

            if ($days <= 0) {
                continue;
            }

            $amount = round($monthly * $days / $normDays, 2);

            if ($amount <= 0) {
                continue;
            }

            $name = filled($row['substituted_name'] ?? null)
                ? __('payroll::dashboard.order_earnings.substitution_for', ['name' => $row['substituted_name'], 'days' => $days])
                : __('payroll::dashboard.order_earnings.substitution', ['days' => $days]);

            $lines[] = $this->line(self::SUBSTITUTION, $name, $amount, $sort++, [implode(':', [
                self::SUBSTITUTION,
                $row['id'],
                $row['start_date'],
                $row['end_date'] ?? '-',
                $percent > 0 ? number_format($percent, 2, '.', '') : '-',
                $percent > 0 ? '-' : number_format((float) ($row['extra_pay_amount'] ?? 0), 2, '.', ''),
                'diff='.number_format($difference, 2, '.', ''),
                'days='.$days,
            ])]);
        }

        return $lines;
    }

    /**
     * What a set of order-derived lines amounts to — code, amount and the records behind
     * them, order-independent — so a calculated payslip can be compared with the facts now.
     *
     * @param  iterable<array<string,mixed>>  $lines
     */
    public static function signature(iterable $lines): string
    {
        $parts = [];

        foreach ($lines as $line) {
            if (! in_array($line['code'] ?? null, self::CODES, true)) {
                continue;
            }

            $sources = (array) ($line['sources'] ?? []);
            sort($sources);
            $parts[] = $line['code'].'|'.number_format((float) $line['amount'], 2, '.', '').'|'.implode(',', $sources);
        }

        sort($parts);

        return hash('sha256', implode("\n", $parts));
    }

    /**
     * @param  list<string>  $sources
     * @return array<string,mixed>
     */
    private function line(string $code, string $name, float $amount, int $sort, array $sources): array
    {
        return [
            'component_id' => null,
            'code' => $code,
            'name' => $name,
            'kind' => 'earning',
            'amount' => $amount,
            'taxable' => true,
            'affects_social' => true,
            'is_statutory' => false,
            'sort' => $sort,
            'sources' => $sources,
        ];
    }

    private function hours(int $minutes): string
    {
        $hours = $minutes / 60;

        return floor($hours) === $hours ? (string) (int) $hours : number_format($hours, 2, ',', '');
    }
}
