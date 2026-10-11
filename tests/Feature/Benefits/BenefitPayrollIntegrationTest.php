<?php

use App\Models\CompensationComponent;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeCompensationLine;
use App\Models\PayrollOneOffEarning;
use App\Modules\Benefits\Application\Services\BenefitEnrollmentService;
use App\Modules\Payroll\Application\Services\PayrollCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Benefits\Concerns\BuildsBenefitsFixtures;

/*
 * Payroll inteqrasiyası PayrollCalculator/StatutoryEngine dəyişdirilmədən: işçi payı →
 * kompensasiya komponenti (təkrarlanan tutulma); vergiyə/sosial sığortaya cəlb olunan
 * işəgötürən payı → aylıq birdəfəlik gəlir + eyni məbləğdə mənfi əvəzləmə.
 */

uses(BuildsBenefitsFixtures::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-15 10:00:00');
    $this->useProfile('private');
    $this->seedBenefitsReferenceData();
    $this->benefitsPersonnel('E-1');
    $this->compensation = $this->benefitsCompensation('E-1', 1500);
    $this->service = app(BenefitEnrollmentService::class);
});

afterEach(fn () => Carbon::setTestNow());

function benefitLines(int $compensationId): \Illuminate\Support\Collection
{
    return EmployeeCompensationLine::query()
        ->where('employee_compensation_id', $compensationId)
        ->where('note', 'like', 'benefits#%')
        ->get();
}

it('turns the employee share into a recurring deduction line on the active compensation', function (): void {
    expect(CompensationComponent::query()->where('code', 'benefit_employee_share')->exists())->toBeFalse();

    $medical = $this->service->enroll($this->benefitsPlan('MED-A'), 'E-1', '2026-10-01');

    // Komponent ilk işçi payı yazılanda Compensation kataloqunda yaranır (tutulma, vergisiz).
    $component = CompensationComponent::query()->where('code', 'benefit_employee_share')->sole();
    expect($component->type)->toBe('deduction')
        ->and($component->taxable)->toBeFalse();

    $lines = benefitLines($this->compensation->id);
    expect($lines)->toHaveCount(1)
        ->and((string) $lines->first()->amount)->toBe('20.00')
        ->and((int) $lines->first()->component_id)->toBe((int) $component->id)
        ->and($lines->first()->note)->toStartWith('benefits#'.$medical->id);

    // Exempt (VM m.102.1.8): no income in kind is posted.
    expect(PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->count())->toBe(0);
});

it('adds the taxable employer share to income and offsets it so no cash is paid', function (): void {
    $calculator = app(PayrollCalculator::class);
    $before = $calculator->calculate('E-1', '2026-10-15', 2026, 10);

    $this->service->enroll($this->benefitsPlan('LIFE', 'life_insurance', ['monthly_cost' => 40, 'employer_share_percent' => 100, 'allows_dependents' => false]), 'E-1', '2026-10-01');
    $this->service->enroll($this->benefitsPlan('MED-A'), 'E-1', '2026-10-01');

    $oneOffs = PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->orderBy('amount')->get();
    expect($oneOffs)->toHaveCount(2)
        ->and((float) $oneOffs[0]->amount)->toBe(-40.0)
        ->and($oneOffs[0]->taxable)->toBeFalse()
        ->and((float) $oneOffs[1]->amount)->toBe(40.0)
        ->and($oneOffs[1]->taxable)->toBeTrue()
        ->and($oneOffs[1]->affects_social)->toBeTrue()
        ->and([$oneOffs[1]->pay_year, $oneOffs[1]->pay_month])->toBe([2026, 10]);

    $after = $calculator->calculate('E-1', '2026-10-15', 2026, 10);
    $codes = collect($after['lines'])->pluck('amount', 'code');

    expect($codes['benefit_in_kind'] ?? null)->toEqual(40.0)
        ->and($codes['benefit_in_kind_offset'] ?? null)->toEqual(-40.0)
        ->and($codes['benefit_employee_share'] ?? null)->toEqual(20.0)
        // Nağd brüt dəyişmir; vergi/sığorta artır, xalis = əvvəlki − işçi payı − əlavə vergi/sığorta.
        ->and($after['gross'])->toEqual($before['gross']);

    $extraStatutory = round($before['net'] - $after['net'] - 20, 2);
    expect($extraStatutory)->toBeGreaterThan(0.0)->toBeLessThan(40.0);
});

it('stops the deduction after the end date and withdraws unpaid lines on cancel', function (): void {
    $medical = $this->service->enroll($this->benefitsPlan('MED-A'), 'E-1', '2026-10-01');
    $life = $this->service->enroll($this->benefitsPlan('LIFE', 'life_insurance', ['monthly_cost' => 40, 'employer_share_percent' => 100]), 'E-1', '2026-10-01');

    $this->service->end($medical, '2026-10-20');
    expect(benefitLines($this->compensation->id))->toHaveCount(1);

    Carbon::setTestNow('2026-10-21 08:00:00');
    Artisan::call('benefits:daily');

    expect($medical->refresh()->status)->toBe('ended')
        ->and(benefitLines($this->compensation->id))->toHaveCount(0);

    $this->service->cancel($life);
    expect(PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->count())->toBe(0);
});

it('restores the deduction on a new compensation the daily job finds', function (): void {
    $this->service->enroll($this->benefitsPlan('MED-A'), 'E-1', '2026-10-01');

    // HR assigns a new salary by hand without the benefit line.
    $this->compensation->update(['status' => 'ended', 'effective_to' => '2026-10-14']);
    $new = EmployeeCompensation::query()->create([
        'tabel_no' => 'E-1', 'regime_id' => $this->compensation->regime_id, 'base_amount' => 1800,
        'currency' => 'AZN', 'effective_from' => '2026-10-15', 'status' => 'active',
    ]);

    Artisan::call('benefits:daily');

    expect(benefitLines($new->id))->toHaveCount(1)
        ->and(benefitLines($this->compensation->id))->toHaveCount(1);
});

it('posts income in kind for social insurance only when a norm-less plan is income-tax exempt but in the DSMF base', function (): void {
    // Norması olmayan paket (məs. idman zalı və ya norması silinmiş yemək kartı) əvvəlki kimi işlənir.
    $this->service->enroll($this->benefitsPlan('MEAL', 'meal_card', ['monthly_cost' => 60, 'employer_share_percent' => 100, 'allows_dependents' => false, 'exempt_daily_norm' => null]), 'E-1', '2026-10-01');

    $value = PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->where('amount', '>', 0)->firstOrFail();

    expect($value->taxable)->toBeFalse()
        ->and($value->affects_social)->toBeTrue();
});
