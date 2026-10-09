<?php

namespace App\Modules\Orders\Infrastructure\Document\Effects;

/**
 * Social leave (sosial məzuniyyət, ƏM m.112.1(b), on doqquzuncu fəsil): maternity
 * (ƏM m.125.1–3) and the father's 14-day paid leave at the child's birth (atalıq
 * məzuniyyəti, ƏM m.125.4). The employee goes on leave exactly like an annual-leave order
 * puts them (a personnel vacation record typed by the order's template), but the days are
 * granted by law on their own and never touch the yearly vacation balance.
 */
class SocialLeaveEffect extends VacationEffect
{
    protected function countsAgainstAnnualBalance(): bool
    {
        return false;
    }
}
