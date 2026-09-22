<?php

namespace App\Services\Orders\Document\Effects;

use App\Models\Award;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\PersonnelAward;

/**
 * Records a monetary award (pul mükafatı) in the employee's file, keyed by the order
 * number so reversal removes exactly what this order added.
 */
class AwardEffect implements OrderEffect
{
    /** Award type "mükafatlar" in the personnel catalogue. */
    private const REWARD_TYPE_ID = 20;

    private const DEFAULT_AWARD = 'Xidmətdə fərqləndiyinə görə';

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $awardId = Award::query()->where('award_type_id', self::REWARD_TYPE_ID)->where('name', self::DEFAULT_AWARD)->value('id')
            ?? Award::query()->where('award_type_id', self::REWARD_TYPE_ID)->orderBy('id')->value('id');

        // Fail safe: without an award catalogue entry there is nothing to link the record to.
        if ($awardId === null || blank($personnel->tabel_no)) {
            return;
        }

        $amount = str_replace([' ', ','], ['', '.'], (string) ($fields['amount'] ?? ''));

        PersonnelAward::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'award_id' => $awardId,
            'reason' => (string) ($fields['reason'] ?? self::DEFAULT_AWARD),
            'amount' => is_numeric($amount) ? (float) $amount : null,
            'given_date' => optional($order->given_date)->format('Y-m-d') ?? now()->toDateString(),
            'order_no' => $order->order_no,
            'order_given_by' => (string) ($order->given_by ?? ''),
            'order_date' => optional($order->given_date)->format('Y-m-d'),
        ]);
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        PersonnelAward::query()
            ->where('tabel_no', $personnel->tabel_no)
            ->where('order_no', $order->order_no)
            ->delete();
    }
}
