<?php

namespace App\Modules\Payroll\Application\Services;

use App\Modules\Attendance\Contracts\OrderRestDayWork;
use App\Modules\Attendance\Contracts\PayrollRestDayWork;
use App\Modules\Compensation\Contracts\SubstitutionRegister;
use Carbon\CarbonImmutable;

/**
 * Earnings that approved orders entitle an employee to for the pay month, read at
 * calculation time from the module that owns each fact (so a recalculation follows the
 * orders and a revoked order's pay disappears on the next calculation):
 *
 * - rest-day / holiday work (Labour Code: paid at least double, or with another day off): the
 *   days with compensation "double_pay" are paid at twice the hourly rate,
 *   hourly rate = base salary ÷ (the month's norm minutes ÷ 60); "day_off" days are
 *   compensated with rest, not money, and add nothing;
 * - substitution (əvəzetmə): percent × the substituting employee's base salary, or the
 *   fixed monthly amount on rows that hold one, prorated by the calendar days of the
 *   substitution that fall in the month.
 *
 * Both are taxable and social-insurable earnings and go through the same statutory path
 * as the rest of the payslip.
 */
class OrderEarningsService
{
    public const REST_DAY_WORK = 'rest_day_work';

    public const SUBSTITUTION = 'substitution';

    /** Labour Code: work on a rest day / holiday is paid at least at double rate. */
    public const REST_DAY_MULTIPLIER = 2;

    public function __construct(
        private readonly PayrollRestDayWork $restDayWork,
        private readonly SubstitutionRegister $substitutions,
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

        foreach ($this->restDayWork->orderWorkFor([$tabelNo], $year, $month)[$tabelNo] ?? [] as $day) {
            if ($day['compensation'] !== OrderRestDayWork::COMPENSATION_DOUBLE_PAY) {
                continue;
            }

            $paidMinutes += $day['minutes'];
            $days++;
        }

        if ($paidMinutes <= 0 || $baseAmount <= 0) {
            return null;
        }

        $normMinutes = $this->restDayWork->monthNormMinutes($tabelNo, $year, $month);

        if ($normMinutes <= 0) {
            return null;
        }

        $amount = round($baseAmount * $paidMinutes * self::REST_DAY_MULTIPLIER / $normMinutes, 2);

        return $this->line(self::REST_DAY_WORK, __('payroll::dashboard.order_earnings.rest_day_work', [
            'days' => $days,
            'hours' => $this->hours($paidMinutes),
        ]), $amount, 150);
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function substitutionLines(string $tabelNo, float $baseAmount, int $year, int $month): array
    {
        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth()->startOfDay();
        $lines = [];
        $sort = 160;

        foreach ($this->substitutions->overlapping([$tabelNo], $monthStart->toDateString(), $monthEnd->toDateString())[$tabelNo] ?? [] as $row) {
            $percent = (float) ($row['extra_pay_percent'] ?? 0);
            $monthly = $percent > 0 ? $baseAmount * $percent / 100 : (float) ($row['extra_pay_amount'] ?? 0);

            if ($monthly <= 0) {
                continue;
            }

            $from = CarbonImmutable::parse($row['start_date'])->max($monthStart);
            $to = $row['end_date'] !== null ? CarbonImmutable::parse($row['end_date'])->min($monthEnd) : $monthEnd;
            $days = (int) $from->diffInDays($to) + 1;

            if ($days <= 0) {
                continue;
            }

            $amount = round($monthly * $days / $monthStart->daysInMonth, 2);

            if ($amount <= 0) {
                continue;
            }

            $name = filled($row['substituted_name'] ?? null)
                ? __('payroll::dashboard.order_earnings.substitution_for', ['name' => $row['substituted_name'], 'days' => $days])
                : __('payroll::dashboard.order_earnings.substitution', ['days' => $days]);

            $lines[] = $this->line(self::SUBSTITUTION, $name, $amount, $sort++);
        }

        return $lines;
    }

    /**
     * @return array<string,mixed>
     */
    private function line(string $code, string $name, float $amount, int $sort): array
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
        ];
    }

    private function hours(int $minutes): string
    {
        $hours = $minutes / 60;

        return floor($hours) === $hours ? (string) (int) $hours : number_format($hours, 2, ',', '');
    }
}
