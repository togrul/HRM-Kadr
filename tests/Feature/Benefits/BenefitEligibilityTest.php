<?php

use App\Modules\Benefits\Application\Services\BenefitEligibilityService;
use Carbon\CarbonImmutable;
use Tests\Feature\Benefits\Concerns\BuildsBenefitsFixtures;

/*
 * Uyğunluq qaydaları: sınaq müddəti, staj (ay), vəzifə, struktur (alt strukturlar daxil),
 * VTİSK kateqoriyası (menecerlər), müqavilə növü, xitam.
 */

uses(BuildsBenefitsFixtures::class);

beforeEach(function (): void {
    $this->useProfile('private');
    $this->seedBenefitsReferenceData();
    $this->service = app(BenefitEligibilityService::class);
});

it('waits for the probation period to end', function (): void {
    $plan = $this->benefitsPlan('MED-A', 'medical_insurance', ['eligibility' => ['after_probation' => true]]);
    $personnel = $this->benefitsPersonnel('E-1', ['join_work_date' => '2026-09-01', 'probation_unit' => 'month', 'probation_amount' => 3]);

    expect($this->service->reasons($plan, $personnel, CarbonImmutable::parse('2026-10-15')))->toBe(['probation'])
        ->and($this->service->eligible($plan, $personnel, CarbonImmutable::parse('2026-12-01')))->toBeTrue();
});

it('requires the configured months of service', function (): void {
    $plan = $this->benefitsPlan('GYM', 'gym', ['eligibility' => ['min_service_months' => 6]]);
    $personnel = $this->benefitsPersonnel('E-2', ['join_work_date' => '2026-06-01']);

    expect($this->service->reasons($plan, $personnel, CarbonImmutable::parse('2026-11-30')))->toBe(['service_months'])
        ->and($this->service->eligible($plan, $personnel, CarbonImmutable::parse('2026-12-01')))->toBeTrue();
});

it('limits a plan to managers by position category', function (): void {
    $plan = $this->benefitsPlan('MOB', 'mobile', ['eligibility' => ['vtisk_categories' => ['rehber']]]);

    expect($this->service->reasons($plan, $this->benefitsPersonnel('E-3', ['position_id' => 1])))->toBe(['category'])
        ->and($this->service->eligible($plan, $this->benefitsPersonnel('E-4', ['position_id' => 2])))->toBeTrue();
});

it('matches a unit together with its sub-units, and explicit positions', function (): void {
    $plan = $this->benefitsPlan('MEAL', 'meal_card', ['eligibility' => ['structure_ids' => [1], 'position_ids' => [1]]]);

    expect($this->service->eligible($plan, $this->benefitsPersonnel('E-5', ['structure_id' => 2, 'position_id' => 1])))->toBeTrue()
        ->and($this->service->reasons($plan, $this->benefitsPersonnel('E-6', ['structure_id' => 3, 'position_id' => 2])))->toBe(['position', 'structure']);
});

it('checks the employment contract type and termination', function (): void {
    $plan = $this->benefitsPlan('LIFE', 'life_insurance', ['eligibility' => ['contract_types' => ['indefinite']]]);

    expect($this->service->reasons($plan, $this->benefitsPersonnel('E-7', ['contract_type' => 'fixed'])))->toBe(['contract_type'])
        ->and($this->service->reasons($plan, $this->benefitsPersonnel('E-8', ['leave_work_date' => '2026-01-31']), CarbonImmutable::parse('2026-10-01')))->toBe(['terminated']);
});

it('lists only the eligible self-service plans for an employee', function (): void {
    $this->benefitsPlan('OPEN', 'medical_insurance');
    $this->benefitsPlan('MANAGERS', 'mobile', ['eligibility' => ['vtisk_categories' => ['rehber']]]);
    $this->benefitsPlan('HR-ONLY', 'gym', ['self_service' => false]);

    $plans = $this->service->eligiblePlans($this->benefitsPersonnel('E-9'), null, true);

    expect($plans->pluck('code')->all())->toBe(['OPEN']);
});
