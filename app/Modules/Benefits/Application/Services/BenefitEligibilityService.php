<?php

namespace App\Modules\Benefits\Application\Services;

use App\Models\BenefitPlan;
use App\Models\Personnel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Paketin uyğunluq qaydalarını əməkdaşın kartı ilə yoxlayır:
 *   - sınaq müddəti bitib (işə qəbul tarixi + probation_amount × probation_unit);
 *   - işə qəbuldan ən azı N ay keçib;
 *   - vəzifə siyahısı; struktur siyahısı (alt strukturlar daxil);
 *   - vəzifənin VTİSK kateqoriyası (məs. yalnız «rəhbər» — menecerlər);
 *   - əmək müqaviləsinin növü (müddətli / müddətsiz).
 * Bütün qaydalar AND ilə birləşir; boş qayda məhdudiyyət qoymur. İşdən çıxmış əməkdaş uyğun deyil.
 */
class BenefitEligibilityService
{
    /** @var array<int, int|null>|null structure id → parent id */
    private ?array $parents = null;

    /** @var array<int, string|null> position id → vtisk category */
    private array $categories = [];

    /**
     * @return list<string> uyğunsuzluq səbəbləri (benefits::dashboard.eligibility.reasons.*); boşdursa uyğundur
     */
    public function reasons(BenefitPlan $plan, Personnel $personnel, ?CarbonImmutable $on = null): array
    {
        $on ??= CarbonImmutable::today();
        $rules = (array) ($plan->eligibility ?? []);
        $reasons = [];

        $leave = $personnel->getRawOriginal('leave_work_date');
        if ($leave !== null && CarbonImmutable::parse($leave)->lt($on)) {
            $reasons[] = 'terminated';
        }

        $joined = $personnel->getRawOriginal('join_work_date');
        $joinedOn = $joined !== null ? CarbonImmutable::parse($joined)->startOfDay() : null;

        if (($rules['after_probation'] ?? false) && $this->probationEnd($personnel, $joinedOn)?->gt($on)) {
            $reasons[] = 'probation';
        }

        $months = (int) ($rules['min_service_months'] ?? 0);
        if ($months > 0 && ($joinedOn === null || $joinedOn->addMonths($months)->gt($on))) {
            $reasons[] = 'service_months';
        }

        $positions = array_map('intval', (array) ($rules['position_ids'] ?? []));
        if ($positions !== [] && ! in_array((int) $personnel->position_id, $positions, true)) {
            $reasons[] = 'position';
        }

        $structures = array_map('intval', (array) ($rules['structure_ids'] ?? []));
        if ($structures !== [] && ! $this->withinStructures((int) $personnel->structure_id, $structures)) {
            $reasons[] = 'structure';
        }

        $categories = (array) ($rules['vtisk_categories'] ?? []);
        if ($categories !== [] && ! in_array($this->category((int) $personnel->position_id), $categories, true)) {
            $reasons[] = 'category';
        }

        $contractTypes = (array) ($rules['contract_types'] ?? []);
        if ($contractTypes !== [] && ! in_array((string) $personnel->getAttribute('contract_type'), $contractTypes, true)) {
            $reasons[] = 'contract_type';
        }

        return $reasons;
    }

    public function eligible(BenefitPlan $plan, Personnel $personnel, ?CarbonImmutable $on = null): bool
    {
        return $this->reasons($plan, $personnel, $on) === [];
    }

    /**
     * Əməkdaşın uyğun olduğu aktiv paketlər.
     *
     * @return Collection<int, BenefitPlan>
     */
    public function eligiblePlans(Personnel $personnel, ?CarbonImmutable $on = null, bool $selfServiceOnly = false): Collection
    {
        return BenefitPlan::query()
            ->where('is_active', true)
            ->when($selfServiceOnly, fn ($query) => $query->where('self_service', true))
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->filter(fn (BenefitPlan $plan): bool => $this->eligible($plan, $personnel, $on))
            ->values();
    }

    public function probationEnd(Personnel $personnel, ?CarbonImmutable $joinedOn = null): ?CarbonImmutable
    {
        $joined = $personnel->getRawOriginal('join_work_date');
        $joinedOn ??= $joined !== null ? CarbonImmutable::parse($joined)->startOfDay() : null;
        $amount = (int) ($personnel->probation_amount ?? 0);

        if ($joinedOn === null || $amount <= 0) {
            return null;
        }

        return match ($personnel->probation_unit) {
            'day' => $joinedOn->addDays($amount),
            'week' => $joinedOn->addWeeks($amount),
            default => $joinedOn->addMonths($amount),
        };
    }

    /**
     * @param  list<int>  $allowed
     */
    private function withinStructures(int $structureId, array $allowed): bool
    {
        $this->parents ??= DB::table('structures')->pluck('parent_id', 'id')
            ->mapWithKeys(fn ($parent, $id): array => [(int) $id => $parent !== null ? (int) $parent : null])
            ->all();

        $seen = [];
        $current = $structureId > 0 ? $structureId : null;

        while ($current !== null && ! isset($seen[$current])) {
            if (in_array($current, $allowed, true)) {
                return true;
            }

            $seen[$current] = true;
            $current = $this->parents[$current] ?? null;
        }

        return false;
    }

    private function category(int $positionId): ?string
    {
        if ($positionId <= 0) {
            return null;
        }

        if (! array_key_exists($positionId, $this->categories)) {
            $value = DB::table('positions')->where('id', $positionId)->value('vtisk_category');
            $this->categories[$positionId] = $value !== null ? (string) $value : null;
        }

        return $this->categories[$positionId];
    }
}
