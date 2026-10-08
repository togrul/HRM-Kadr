<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

/**
 * Unpaid leave (ödənişsiz məzuniyyət, ƏM m.112.1(ç), m.128–130 — e.g. the father's up to
 * 14 days while the mother is on maternity leave, m.130(b)): a leave kind of its own that
 * never consumes the annual labour leave. The employee is put on leave (a personnel
 * vacation record) and the yearly balance stays as it is.
 *
 * ƏM m.132.1 counts only actual work and the listed periods (pay kept, sick leave, forced
 * absence) towards the length of service that earns annual leave, so a long unpaid leave
 * can push the work year back; that shift is not modelled here.
 */
class UnpaidLeaveEffect extends VacationEffect
{
    protected function countsAgainstAnnualBalance(): bool
    {
        return false;
    }
}
