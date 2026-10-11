<?php

namespace App\Modules\Benefits\Application\Services;

use App\Models\BenefitEnrollment;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use Carbon\CarbonImmutable;

/**
 * «Ümumi mükafatlandırma» (total rewards): maaş + təkrarlanan əlavələr + işəgötürənin ödədiyi
 * imtiyazlar = əməkdaşın aylıq ümumi dəyəri. İşçinin öz payı ayrıca göstərilir (xalis maaşdan
 * tutulur). Maaş Compensation modulunun oxuma kontraktından gəlir; modul yoxdursa maaş boş qalır.
 */
class TotalRewardsService
{
    public function __construct(private readonly BenefitPayrollSync $payroll) {}

    /**
     * @return array{
     *     currency: string,
     *     salary: ?float,
     *     allowances: float,
     *     allowance_lines: list<array{name: string, amount: float}>,
     *     benefits_employer: float,
     *     benefits_employee: float,
     *     benefit_lines: list<array{plan: string, type: string, employer: float, employee: float, dependents: int, provider: ?string}>,
     *     total: float,
     *     has_salary: bool
     * }
     */
    public function forEmployee(string $tabelNo, ?CarbonImmutable $on = null): array
    {
        $on ??= CarbonImmutable::today();
        $salary = null;
        $currency = 'AZN';
        $allowanceLines = [];

        if (app()->bound(CompensationReadRepository::class)) {
            $repository = app(CompensationReadRepository::class);
            $current = $repository->currentCompensation($tabelNo, $on->toDateString());

            if ($current !== null) {
                $salary = round((float) $current->base_amount, 2);
                $currency = (string) ($current->currency ?: 'AZN');

                foreach ($repository->componentsFor($tabelNo, $on->toDateString()) as $component) {
                    if (($component['type'] ?? 'earning') !== 'earning') {
                        continue;
                    }

                    $allowanceLines[] = ['name' => (string) ($component['component_name'] ?? ''), 'amount' => round((float) ($component['amount'] ?? 0), 2)];
                }
            }
        }

        $benefitLines = [];
        $enrollments = BenefitEnrollment::query()
            ->with('plan:id,name,type,provider')
            ->withCount('dependents')
            ->where('tabel_no', $tabelNo)
            ->where('status', BenefitEnrollment::STATUS_ACTIVE)
            ->orderBy('start_date')
            ->get();

        foreach ($enrollments as $enrollment) {
            if (! $this->payroll->inForce($enrollment, $on)) {
                continue;
            }

            $benefitLines[] = [
                'plan' => (string) $enrollment->plan?->name,
                'type' => (string) $enrollment->plan?->type,
                'employer' => round((float) $enrollment->employer_amount, 2),
                'employee' => round((float) $enrollment->employee_amount, 2),
                'dependents' => (int) $enrollment->getAttribute('dependents_count'),
                'provider' => $enrollment->plan?->provider,
            ];
        }

        $allowances = round(array_sum(array_column($allowanceLines, 'amount')), 2);
        $employer = round(array_sum(array_column($benefitLines, 'employer')), 2);
        $employee = round(array_sum(array_column($benefitLines, 'employee')), 2);

        return [
            'currency' => $currency,
            'salary' => $salary,
            'allowances' => $allowances,
            'allowance_lines' => $allowanceLines,
            'benefits_employer' => $employer,
            'benefits_employee' => $employee,
            'benefit_lines' => $benefitLines,
            'total' => round(($salary ?? 0.0) + $allowances + $employer, 2),
            'has_salary' => $salary !== null,
        ];
    }
}
