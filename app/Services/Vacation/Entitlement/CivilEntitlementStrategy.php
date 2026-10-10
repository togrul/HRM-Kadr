<?php

namespace App\Services\Vacation\Entitlement;

use App\Models\Personnel;
use App\Models\VacationNorm;
use App\Modules\Personnel\Contracts\LeaveEntitlementFacts;
use App\Modules\Personnel\Contracts\LeaveEntitlementFactsProvider;
use App\Modules\Vacation\Application\Services\EntitlementBreakdown;
use App\Modules\Vacation\Application\Services\VacationNormEvaluator;
use App\Modules\Vacation\Application\Services\VacationSettings;
use App\Modules\Vacation\Application\Services\WorkYearPeriod;
use App\Services\Vacation\OrderLeaveFacts;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Mülki işçilər: hüquq Admin → «Məzuniyyət normaları» cədvəlindən (ƏM m.114–117, 119) iş ilinin
 * başlanğıcına görə hesablanır. Birinci iş ili üçün məzuniyyət 6 ay işlədikdən sonra (m.131.1);
 * 18 yaşadək və əlilliyi olan işçilər üçün gözləmə yoxdur (m.131.4).
 */
class CivilEntitlementStrategy implements EntitlementStrategy
{
    public const KEY = 'civil';

    /** @var Collection<int, VacationNorm>|null */
    private ?Collection $norms = null;

    /** @var array<string, LeaveEntitlementFacts|null> */
    private array $facts = [];

    public function __construct(
        private readonly LeaveEntitlementFactsProvider $provider,
        private readonly VacationNormEvaluator $evaluator,
        private readonly OrderLeaveFacts $orderFacts,
    ) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function entitlement(Personnel $personnel, WorkYearPeriod $period, CarbonImmutable $asOf): EntitlementBreakdown
    {
        $facts = $this->factsFor($personnel);

        if ($facts === null) {
            return new EntitlementBreakdown(0);
        }

        return $this->evaluator->evaluate($facts, $period->start, $this->norms(), $period, $asOf);
    }

    public function availableFrom(Personnel $personnel, WorkYearPeriod $period): CarbonImmutable
    {
        if ($period->sequence !== 1) {
            return $period->start;
        }

        $facts = $this->factsFor($personnel);

        if ($facts !== null && ($facts->isUnder18On($period->start) || $facts->isDisabledOn($period->start))) {
            return $period->start;
        }

        return $period->start->addMonths(VacationSettings::FIRST_YEAR_WAIT_MONTHS);
    }

    /** Normalar və ya işçi məlumatı dəyişəndə yaddaşdakı nüsxəni at. */
    public function forget(): void
    {
        $this->norms = null;
        $this->facts = [];
    }

    private function factsFor(Personnel $personnel): ?LeaveEntitlementFacts
    {
        $tabelNo = (string) $personnel->tabel_no;

        if ($tabelNo === '') {
            return null;
        }

        if (! array_key_exists($tabelNo, $this->facts)) {
            $facts = $this->provider->facts([$tabelNo])[$tabelNo] ?? null;

            // Əmrlərdən çıxan faktlar: m.116 stajından çıxılan dövrlər və vəzifə tarixçəsi.
            $this->facts[$tabelNo] = $facts?->withOrderFacts(
                $this->orderFacts->seniorityGaps($tabelNo),
                $this->orderFacts->positionChanges((int) $personnel->getKey(), $facts->positionId),
            );
        }

        return $this->facts[$tabelNo];
    }

    /**
     * @return Collection<int, VacationNorm>
     */
    private function norms(): Collection
    {
        return $this->norms ??= VacationNorm::query()->where('is_active', true)->get();
    }
}
