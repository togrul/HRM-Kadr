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

    public static function overlaps(Carbon $aFrom, ?Carbon $aTo, Carbon $bFrom, ?Carbon $bTo): bool
    {
        $aTo ??= Carbon::maxValue();
        $bTo ??= Carbon::maxValue();

        return $aFrom->lte($bTo) && $bFrom->lte($aTo);
    }
}
