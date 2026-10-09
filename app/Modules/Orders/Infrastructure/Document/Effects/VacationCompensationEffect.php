<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Payroll\Domain\Contracts\PayrollOneOffEarnings;
use App\Services\Vacation\VacationBalanceService;
use App\Support\Language\AzerbaijaniDateFormatter;
use Carbon\Carbon;

/**
 * Pays out unused annual leave (istifadə olunmamış məzuniyyətə görə kompensasiya): the
 * compensated days leave the yearly balance of the work year the order names (the
 * order date's year when none is given). When the order states the amount and this
 * install runs payroll, the amount joins the month's payroll as a one-off earning;
 * otherwise the day count travels to payroll with the order's integration event.
 * Reversal gives the days back and withdraws the unpaid earning.
 */
class VacationCompensationEffect implements OrderEffect
{
    use RemembersEffectState;

    public function __construct(
        private readonly AzerbaijaniDateFormatter $dates,
        private readonly VacationBalanceService $balance,
    ) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $days = (int) ($fields['days'] ?? 0);

        if ($days <= 0 || blank($personnel->tabel_no)) {
            return;
        }

        $workYear = $this->dates->parse($fields['work_year'] ?? null);
        $year = (int) ($workYear !== null
            ? $workYear->year
            : ($order->given_date ? Carbon::parse($order->given_date)->year : now()->year));

        $this->balance->consume($personnel, $year, $days);

        $amount = AwardEffect::parseAmount((string) ($fields['amount'] ?? ''));
        $paid = false;

        if ($amount !== null && $amount > 0 && app()->bound(PayrollOneOffEarnings::class)) {
            $paid = app(PayrollOneOffEarnings::class)->record(
                (string) $personnel->tabel_no,
                'vacation_compensation',
                __('orders::order_composer.effects.vacation_compensation_payroll_line', ['number' => $order->order_no], config('app.locale')),
                $amount,
                (int) now()->year,
                (int) now()->month,
                self::sourceKey($order),
            );
        }

        $this->rememberState($order, ['vacation_compensation' => [
            'year' => $year,
            'days' => $days,
            'payroll_line' => $paid,
        ]]);
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $state = $this->rememberedState($order)['vacation_compensation'] ?? null;

        if (is_array($state) && (int) ($state['days'] ?? 0) > 0) {
            $this->balance->release($personnel, (int) $state['year'], (int) $state['days']);
        }

        if (app()->bound(PayrollOneOffEarnings::class)) {
            app(PayrollOneOffEarnings::class)->withdraw(self::sourceKey($order));
        }

        $this->forgetState($order, ['vacation_compensation']);
    }

    public static function sourceKey(OrderLog $order): string
    {
        return 'order_vacation_compensation:'.$order->id;
    }
}
