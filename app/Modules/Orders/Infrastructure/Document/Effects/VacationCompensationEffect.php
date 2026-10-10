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
 * compensated days leave the balance — the work year the order names first, then the oldest
 * open work year. ƏM m.144.2 regulates the pay-out on termination of the employment
 * contract; during employment it is refused unless the organisation enables it (see
 * VacationSettings::COMPENSATION_WITHOUT_TERMINATION). When the order states the amount and this
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
        $on = $order->given_date ? Carbon::parse($order->given_date) : now();
        $year = (int) ($workYear !== null ? $workYear->year : $on->year);

        // ƏM m.144.2: refused (DomainException) unless the employment has ended or the
        // organisation allows paying out leave during employment.
        $this->balance->compensate($personnel, $days, self::sourceKey($order), $on, $workYear);

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
            $this->balance->release($personnel, (int) $state['year'], (int) $state['days'], self::sourceKey($order));
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
