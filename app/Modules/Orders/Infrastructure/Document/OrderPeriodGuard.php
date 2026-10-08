<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Data\AbsencePeriod;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Modules\Orders\Application\Document\OrderLeaveDateRules;
use App\Services\Absence\AbsenceOverlapGuard;
use Carbon\CarbonImmutable;

/**
 * The checks a period order (leave, business trip…) must pass both when it is issued and
 * again when it is approved — an invalid draft saved before these rules existed, or one
 * whose employee has since gone away on other dates, must not reach the register:
 *
 *   - the period's dates are coherent (OrderLeaveDateRules);
 *   - an order that sends the employee away is only for an active employee;
 *   - it does not overlap a live leave, vacation or business trip (AbsenceOverlapGuard).
 */
class OrderPeriodGuard
{
    /** Effects that take the employee away from work, and the absence each one records. */
    private const ABSENCE_EFFECTS = [
        'vacation' => AbsencePeriod::TYPE_VACATION,
        'social_leave' => AbsencePeriod::TYPE_VACATION,
        'business_trip' => AbsencePeriod::TYPE_BUSINESS_TRIP,
    ];

    public function __construct(
        private readonly OrderLeaveDateRules $dates,
        private readonly AbsenceOverlapGuard $absences,
    ) {}

    /**
     * Field-level date errors (fields.<token> => message).
     *
     * @param  array<string,mixed>  $fields
     * @return array<string,string>
     */
    public function dateErrors(OrderWordTemplate $template, array $fields, ?Personnel $personnel): array
    {
        $rawJoined = $personnel?->getRawOriginal('join_work_date');
        $joined = filled($rawJoined) ? CarbonImmutable::parse((string) $rawJoined) : null;

        return $this->dates->violations($template, $fields, $joined);
    }

    /**
     * Why the employee cannot be sent away by this order (inactive, or already away on
     * these dates), or null.
     *
     * @param  array<string,mixed>  $fields
     */
    public function absenceBlocker(OrderWordTemplate $template, array $fields, ?Personnel $personnel): ?string
    {
        $type = self::ABSENCE_EFFECTS[$template->effect] ?? null;

        if ($type === null || $personnel === null) {
            return null;
        }

        if ((bool) $personnel->is_pending || filled($personnel->leave_work_date)) {
            return __('orders::order_composer.errors.employee_inactive');
        }

        $period = $this->dates->period($template, $fields);

        return $period === null
            ? null
            : $this->absences->violation((string) $personnel->tabel_no, AbsencePeriod::days($type, null, $period[0], $period[1]));
    }

    /**
     * Everything that stops the order — dates first, then the employee's availability —
     * as one sentence for the approval flow, or null when it may proceed.
     *
     * @param  array<string,mixed>  $fields
     */
    public function approvalBlocker(OrderWordTemplate $template, array $fields, ?Personnel $personnel): ?string
    {
        $dateErrors = $this->dateErrors($template, $fields, $personnel);

        if ($dateErrors !== []) {
            return implode(' ', array_unique(array_values($dateErrors)));
        }

        return $this->absenceBlocker($template, $fields, $personnel);
    }
}
