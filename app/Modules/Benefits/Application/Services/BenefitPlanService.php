<?php

namespace App\Modules\Benefits\Application\Services;

use App\Models\BenefitEnrollment;
use App\Models\BenefitPlan;
use Illuminate\Validation\ValidationException;

/**
 * Paket kataloqu: yaratma, redaktə, deaktivləşdirmə. Uyğunluq qaydaları bir JSON sahəsində
 * normallaşdırılmış formada saxlanır (BenefitEligibilityService oxuyur).
 */
class BenefitPlanService
{
    /** Uyğunluq qaydasının açarları. */
    public const ELIGIBILITY_KEYS = ['after_probation', 'min_service_months', 'position_ids', 'structure_ids', 'vtisk_categories', 'contract_types'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?int $planId = null): BenefitPlan
    {
        $plan = $planId ? BenefitPlan::query()->findOrFail($planId) : new BenefitPlan;
        $type = (string) ($data['type'] ?? 'other');
        $defaults = BenefitTypes::defaults($type);

        $plan->fill([
            'code' => trim((string) $data['code']),
            'name' => trim((string) $data['name']),
            'type' => $type,
            'description' => $this->nullableString($data['description'] ?? null),
            'provider' => $this->nullableString($data['provider'] ?? null),
            'currency' => strtoupper((string) ($data['currency'] ?? 'AZN')) ?: 'AZN',
            'monthly_cost' => round((float) ($data['monthly_cost'] ?? 0), 2),
            'employer_share_percent' => round((float) ($data['employer_share_percent'] ?? 100), 2),
            'allows_dependents' => (bool) ($data['allows_dependents'] ?? false),
            'dependent_monthly_cost' => round((float) ($data['dependent_monthly_cost'] ?? 0), 2),
            'dependent_employer_share_percent' => round((float) ($data['dependent_employer_share_percent'] ?? 0), 2),
            'max_dependents' => ($data['max_dependents'] ?? '') === '' || $data['max_dependents'] === null ? null : (int) $data['max_dependents'],
            'taxable' => (bool) ($data['taxable'] ?? $defaults['taxable']),
            'affects_social' => (bool) ($data['affects_social'] ?? $defaults['affects_social']),
            // Açar verilməyibsə növün standart normasına düşür; açıq null/boş — norma yoxdur.
            'exempt_daily_norm' => array_key_exists('exempt_daily_norm', $data)
                ? $this->nullableAmount($data['exempt_daily_norm'])
                : $defaults['exempt_daily_norm'],
            'norm_is_invoice_based' => (bool) ($data['norm_is_invoice_based'] ?? false),
            'tax_basis' => $this->nullableString($data['tax_basis'] ?? null) ?? $defaults['basis'],
            'contract_start' => $this->nullableString($data['contract_start'] ?? null),
            'contract_end' => $this->nullableString($data['contract_end'] ?? null),
            'renewal_window_days' => max(1, (int) ($data['renewal_window_days'] ?? config('benefits.renewal.window_days', 30))),
            'auto_renew' => (bool) ($data['auto_renew'] ?? true),
            'self_service' => (bool) ($data['self_service'] ?? true),
            'eligibility' => $this->normalizeEligibility((array) ($data['eligibility'] ?? [])),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort' => (int) ($data['sort'] ?? 0),
        ]);

        if (! $plan->allows_dependents) {
            $plan->max_dependents = null;
        }

        $plan->save();

        return $plan;
    }

    /**
     * Qoşulması olan paket silinmir — tarixçə (payroll, xərc hesabatı) ona istinad edir; onu
     * deaktivləşdirmək kifayətdir.
     *
     * @throws ValidationException
     */
    public function delete(int $planId): void
    {
        $plan = BenefitPlan::query()->findOrFail($planId);

        if (BenefitEnrollment::query()->where('benefit_plan_id', $plan->id)->exists()) {
            throw ValidationException::withMessages(['plan' => __('benefits::dashboard.messages.plan_in_use')]);
        }

        $plan->delete();
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array{after_probation: bool, min_service_months: ?int, position_ids: list<int>, structure_ids: list<int>, vtisk_categories: list<string>, contract_types: list<string>}
     */
    public function normalizeEligibility(array $rules): array
    {
        $ints = static fn ($values): array => array_values(array_unique(array_filter(array_map('intval', (array) $values), static fn (int $id): bool => $id > 0)));
        $strings = static fn ($values): array => array_values(array_unique(array_filter(array_map(static fn ($v): string => trim((string) $v), (array) $values), static fn (string $v): bool => $v !== '')));
        $months = $rules['min_service_months'] ?? null;

        return [
            'after_probation' => (bool) ($rules['after_probation'] ?? false),
            'min_service_months' => $months === null || $months === '' || (int) $months <= 0 ? null : (int) $months,
            'position_ids' => $ints($rules['position_ids'] ?? []),
            'structure_ids' => $ints($rules['structure_ids'] ?? []),
            'vtisk_categories' => $strings($rules['vtisk_categories'] ?? []),
            'contract_types' => $strings($rules['contract_types'] ?? []),
        ];
    }

    /** Boş/null → null (norma yoxdur); mənfi məbləğ qəbul olunmur. */
    private function nullableAmount(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (! is_numeric($value) || (float) $value < 0) {
            throw ValidationException::withMessages(['exempt_daily_norm' => __('benefits::dashboard.messages.norm_negative')]);
        }

        return round((float) $value, 2);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
