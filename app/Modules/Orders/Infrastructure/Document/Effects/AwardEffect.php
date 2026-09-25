<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Models\Award;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\PersonnelAward;
use App\Modules\Payroll\Domain\Contracts\PayrollOneOffEarnings;
use DomainException;

/**
 * Records a monetary award (pul mükafatı) in the employee's file, keyed by the order
 * number so reversal removes exactly what this order added, and hands the amount to
 * payroll for the current month when this installation runs payroll.
 */
class AwardEffect implements OrderEffect
{
    /** Award type "mükafatlar" in the personnel catalogue. */
    private const REWARD_TYPE_ID = 20;

    /** Data key: looked up by name in the awards catalogue, so it must match the stored row byte for byte. */
    private const DEFAULT_AWARD = 'Xidmətdə fərqləndiyinə görə';

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $awardId = Award::query()->where('award_type_id', self::REWARD_TYPE_ID)->where('name', self::DEFAULT_AWARD)->value('id')
            ?? Award::query()->where('award_type_id', self::REWARD_TYPE_ID)->orderBy('id')->value('id');

        // Fail safe: without an award catalogue entry there is nothing to link the record to.
        if ($awardId === null || blank($personnel->tabel_no)) {
            return;
        }

        $amount = self::parseAmount((string) ($fields['amount'] ?? ''));

        PersonnelAward::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'award_id' => $awardId,
            'reason' => (string) ($fields['reason'] ?? self::DEFAULT_AWARD),
            'amount' => $amount,
            'given_date' => optional($order->given_date)->format('Y-m-d') ?? now()->toDateString(),
            'order_no' => $order->order_no,
            'order_given_by' => (string) ($order->given_by ?? ''),
            'order_date' => optional($order->given_date)->format('Y-m-d'),
        ]);

        if ($amount > 0 && app()->bound(PayrollOneOffEarnings::class)) {
            app(PayrollOneOffEarnings::class)->record(
                (string) $personnel->tabel_no, 'award', __('orders::order_composer.effects.award_payroll_line', ['number' => $order->order_no], config('app.locale')), $amount,
                (int) now()->year, (int) now()->month, 'order_award:'.$order->id,
            );
        }
    }

    /**
     * "1 234,50", "1,234.50", "1.234,50" and "1234.5" all read as 1234.5: whichever of
     * `.`/`,` comes last is the decimal mark. A typed amount that still is not a number
     * stops the approval — recording the award without its money would lose it silently.
     *
     * @throws DomainException
     */
    public static function parseAmount(string $raw): ?float
    {
        $value = preg_replace('/[\s\x{00A0}]+/u', '', $raw) ?? '';

        if ($value === '') {
            return null;
        }

        $decimal = strrpos($value, ',') > strrpos($value, '.') ? ',' : '.';
        $value = str_replace($decimal === ',' ? '.' : ',', '', $value);
        $value = str_replace(',', '.', $value);

        if (! is_numeric($value)) {
            throw new DomainException(__('orders::order_composer.errors.award_amount_invalid', ['amount' => $raw]));
        }

        return (float) $value;
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        PersonnelAward::query()
            ->where('tabel_no', $personnel->tabel_no)
            ->where('order_no', $order->order_no)
            ->delete();

        if (app()->bound(PayrollOneOffEarnings::class)) {
            app(PayrollOneOffEarnings::class)->withdraw('order_award:'.$order->id);
        }
    }
}
