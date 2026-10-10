<?php

namespace App\Services\Vacation\Entitlement;

use App\Models\Personnel;
use App\Modules\Vacation\Application\Services\EntitlementBreakdown;
use App\Modules\Vacation\Application\Services\WorkYearPeriod;
use Carbon\CarbonImmutable;

/**
 * Bir iş ili üçün məzuniyyət hüququnu hesablayan qayda dəsti. Mülki işçilər normalarla
 * (CivilEntitlementStrategy), rütbəli heyət rütbə kateqoriyası ilə (RankedEntitlementStrategy).
 */
interface EntitlementStrategy
{
    public function key(): string;

    /**
     * @param  CarbonImmutable  $asOf  hesablama anı (davam edən iş ili üçün bu gün)
     */
    public function entitlement(Personnel $personnel, WorkYearPeriod $period, CarbonImmutable $asOf): EntitlementBreakdown;

    /**
     * İş ilinin günlərindən istifadə oluna biləcəyi ilk tarix (birinci iş ili üçün 6 ay qaydası).
     */
    public function availableFrom(Personnel $personnel, WorkYearPeriod $period): CarbonImmutable;
}
