<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

/**
 * Social leave (sosial məzuniyyət — e.g. maternity, ƏM m.125): the employee goes on leave
 * exactly like an annual-leave order puts them (a personnel vacation record typed by the
 * order's template), but the days are granted by law and never touch the yearly
 * vacation balance.
 */
class SocialLeaveEffect extends VacationEffect
{
    protected function countsAgainstAnnualBalance(): bool
    {
        return false;
    }
}
