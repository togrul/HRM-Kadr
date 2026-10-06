<?php

namespace App\Modules\Compensation\Application\Services;

use Illuminate\Support\Carbon;

/**
 * Small helper for effective-dated record windows.
 */
class EffectiveDating
{
    public static function dayBefore(Carbon $date): Carbon
    {
        return $date->copy()->subDay();
    }

    /**
     * A null end date means the window is still open.
     */
    public static function overlaps(Carbon $aFrom, ?Carbon $aTo, Carbon $bFrom, ?Carbon $bTo): bool
    {
        return ($bTo === null || $aFrom->lte($bTo))
            && ($aTo === null || $bFrom->lte($aTo));
    }
}
