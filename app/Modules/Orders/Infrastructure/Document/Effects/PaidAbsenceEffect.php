<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Leaves\Contracts\OrderAbsenceRecorder;
use App\Support\Language\AzerbaijaniDateFormatter;
use Carbon\CarbonImmutable;

/**
 * Releases the employee from work for a period with pay kept (military muster, donor
 * day, election commission, civil defence training…): the days are filed as an approved
 * leave of the order type's own kind through the Leaves contract, so the puantaj shows
 * the type's code on each day and no other absence can be booked over them. Reversal
 * removes that leave.
 */
class PaidAbsenceEffect implements OrderEffect
{
    use RemembersEffectState;

    /**
     * Standard order type code => [leave type name, puantaj code]. Data keys: the leave
     * type is looked up by name, so they must stay byte for byte as stored.
     */
    public const KINDS = [
        'herbi_toplanti' => ['Hərbi toplantı', 'HT'],
        'donor_gunu' => ['Donor günü', 'DG'],
        'secki_komissiyasi' => ['Seçki komissiyasında iştirak', 'SK'],
        'mulki_mudafie' => ['Mülki müdafiə təlimi', 'MM'],
    ];

    /** Designer-made order types on this effect share one generic kind. */
    public const GENERIC_KIND = ['Ödənişli azadolma (əmrlə)', 'OA'];

    public function __construct(private readonly AzerbaijaniDateFormatter $dates) {}

    public function apply(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $start = $this->dates->parse($fields['start_date'] ?? null);
        $end = $this->dates->parse($fields['end_date'] ?? null) ?? $start;

        // Fail safe: without a coherent period there is nothing to put on record.
        if (! $start || ! $end || $end->lt($start) || blank($personnel->tabel_no) || ! app()->bound(OrderAbsenceRecorder::class)) {
            return;
        }

        $snapshot = (array) $order->template_snapshot;
        [$typeName, $code] = self::KINDS[(string) ($snapshot['template_code'] ?? '')] ?? self::GENERIC_KIND;

        $reason = trim((string) ($fields['reason'] ?? ''));
        if ($reason === '') {
            $reason = __('orders::order_composer.effects.paid_absence_reason', [
                'label' => (string) ($snapshot['label'] ?? $typeName),
                'number' => $order->order_no,
            ], config('app.locale'));
        }

        $leaveId = app(OrderAbsenceRecorder::class)->record(
            (string) $personnel->tabel_no,
            CarbonImmutable::instance($start)->startOfDay(),
            CarbonImmutable::instance($end)->startOfDay(),
            $typeName,
            $code,
            $reason,
        );

        $this->rememberState($order, ['absence_leave_id' => $leaveId]);
    }

    public function reverse(OrderLog $order, array $fields, Personnel $personnel): void
    {
        $leaveId = (int) ($this->rememberedState($order)['absence_leave_id'] ?? 0);

        if ($leaveId > 0 && app()->bound(OrderAbsenceRecorder::class)) {
            app(OrderAbsenceRecorder::class)->remove($leaveId);
        }

        $this->forgetState($order, ['absence_leave_id']);
    }
}
