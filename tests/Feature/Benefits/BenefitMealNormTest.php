<?php

use App\Models\AttendanceMonthlySummary;
use App\Models\PayrollOneOffEarning;
use App\Models\User;
use App\Modules\Benefits\Application\Services\BenefitEnrollmentService;
use App\Modules\Benefits\Application\Services\BenefitPayrollSync;
use App\Modules\Benefits\Application\Services\BenefitPlanService;
use App\Modules\Payroll\Application\Services\PayrollCalculator;
use App\Modules\Payroll\Application\Services\PayrollPeriodService;
use App\Modules\Payroll\Application\Services\PayrollRunService;
use App\Modules\Payroll\Application\Services\TaxAdjustmentReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Benefits\Concerns\BuildsBenefitsFixtures;

/*
 * Yemək kartı: gündəlik norma (VM m.119.2, DVX izahı 10.07.2026).
 *  - norma × iş günü daxilində: gəlir vergisi, DSMF, işsizlik, icbari tibbi — təsir yoxdur;
 *  - artıq hissə: gəlir vergisi və DSMF, işsizlik/tibbi heç vaxt;
 *  - qaimə-fakturalı rejim: məbləğdən asılı olmayaraq təsir yoxdur;
 *  - norması olmayan paketlər (idman zalı) əvvəlki kimi bütün sosial bazaya daxildir.
 */

uses(BuildsBenefitsFixtures::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-15 10:00:00');
    $this->useProfile('private');
    $this->seedBenefitsReferenceData();
    $this->benefitsPersonnel('E-1');
    $this->compensation = $this->benefitsCompensation('E-1', 1500);
    $this->service = app(BenefitEnrollmentService::class);

    // Oktyabr 2026-da 22 iş günü — norma 5 × 22 = 110 AZN.
    AttendanceMonthlySummary::query()->create([
        'tabel_no' => 'E-1', 'year' => 2026, 'month' => 10,
        'total_scheduled_minutes' => 0, 'total_worked_minutes' => 0, 'total_overtime_minutes' => 0,
        'total_absence_minutes' => 0, 'total_workdays' => 22, 'total_present_days' => 22,
        'total_absence_days' => 0, 'is_locked' => false,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

/** @return array<string, float> */
function payslipCodes(): array
{
    $result = app(PayrollCalculator::class)->calculate('E-1', '2026-10-15', 2026, 10);

    return collect($result['lines'])->pluck('amount', 'code')->map(fn ($v): float => (float) $v)->all();
}

function mealPlan(object $test, array $overrides = []): \App\Models\BenefitPlan
{
    return app(BenefitPlanService::class)->save(array_merge(
        ['code' => 'MEAL', 'name' => 'Yemək kartı', 'type' => 'meal_card', 'monthly_cost' => 150, 'employer_share_percent' => 100, 'allows_dependents' => false],
        $overrides,
    ));
}

it('ships a 5 AZN daily norm as the meal card default', function (): void {
    expect(mealPlan($this)->exempt_daily_norm)->toBe('5.00')
        ->and($this->benefitsPlan('GYM', 'gym')->exempt_daily_norm)->toBeNull();
});

it('leaves every base untouched while the card stays within norm x working days', function (): void {
    $before = payslipCodes();

    $this->service->enroll(mealPlan($this, ['monthly_cost' => 110]), 'E-1', '2026-10-01');

    expect(PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->count())->toBe(0)
        ->and(payslipCodes())->toEqual($before);
});

it('charges income tax and DSMF but never unemployment or medical on the part above the norm', function (): void {
    $before = payslipCodes();

    // 22 iş günü × 5 = 110 AZN norma; 150 AZN kart → 40 AZN artıq.
    $this->service->enroll(mealPlan($this), 'E-1', '2026-10-01');

    $lines = PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->orderBy('amount')->get();
    expect($lines)->toHaveCount(2)
        ->and((float) $lines[0]->amount)->toBe(-40.0)
        ->and($lines[0]->affects_social)->toBeFalse()
        ->and((float) $lines[1]->amount)->toBe(40.0)
        ->and($lines[1]->code)->toBe('benefit_in_kind_dsmf')
        ->and($lines[1]->taxable)->toBeTrue()
        ->and($lines[1]->affects_social)->toBeTrue();

    $after = payslipCodes();
    $delta = fn (string $code): float => round(($after[$code] ?? 0.0) - ($before[$code] ?? 0.0), 2);

    expect($delta('income_tax_ee'))->toBeGreaterThan(0.0)
        ->and($delta('dsmf_ee'))->toBeGreaterThan(0.0)
        ->and($delta('dsmf_er'))->toBeGreaterThan(0.0)
        ->and($delta('unemployment_ee'))->toBe(0.0)
        ->and($delta('unemployment_er'))->toBe(0.0)
        ->and($delta('medical_ee'))->toBe(0.0)
        ->and($delta('medical_er'))->toBe(0.0);
});

it('applies no tax or insurance at all in the e-invoice mode, whatever the amount', function (): void {
    $before = payslipCodes();

    $this->service->enroll(mealPlan($this, ['monthly_cost' => 900, 'norm_is_invoice_based' => true]), 'E-1', '2026-10-01');

    expect(PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->count())->toBe(0)
        ->and(payslipCodes())->toEqual($before);
});

it('keeps the legacy full social base for plans without a norm such as the gym', function (): void {
    $before = payslipCodes();

    $this->service->enroll($this->benefitsPlan('GYM', 'gym', ['monthly_cost' => 50, 'employer_share_percent' => 100, 'allows_dependents' => false]), 'E-1', '2026-10-01');

    $value = PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->where('amount', '>', 0)->sole();
    expect($value->code)->toBe('benefit_in_kind')
        ->and($value->taxable)->toBeFalse()
        ->and($value->affects_social)->toBeTrue();

    $after = payslipCodes();
    expect($after['dsmf_ee'])->toBeGreaterThan($before['dsmf_ee'])
        ->and($after['unemployment_ee'])->toBeGreaterThan($before['unemployment_ee'])
        ->and($after['medical_ee'])->toBeGreaterThan($before['medical_ee'])
        ->and($after['income_tax_ee'] ?? 0.0)->toEqual($before['income_tax_ee'] ?? 0.0);
});

it('does not change a line that is already paid when the norm is introduced later', function (): void {
    // Norma təyin olunmazdan əvvəlki köhnə davranış: 150 AZN-in hamısı DSMF bazasında.
    $plan = mealPlan($this, ['exempt_daily_norm' => null]);
    $this->service->enroll($plan, 'E-1', '2026-10-01');

    $value = PayrollOneOffEarning::query()->where('amount', '>', 0)->sole();
    expect($value->code)->toBe('benefit_in_kind')->and($value->amount)->toBe(150.0);

    // Ay kilidlənib/ödənilib.
    PayrollOneOffEarning::query()->update(['paid_payroll_run_id' => 999]);

    app(BenefitPlanService::class)->save(['code' => $plan->code, 'name' => $plan->name, 'type' => 'meal_card', 'monthly_cost' => 150, 'employer_share_percent' => 100, 'exempt_daily_norm' => 5], $plan->id);
    app(BenefitPayrollSync::class)->syncEmployee('E-1');

    $value->refresh();
    expect($value->code)->toBe('benefit_in_kind')
        ->and($value->amount)->toBe(150.0)
        ->and($value->affects_social)->toBeTrue()
        ->and($value->taxable)->toBeFalse()
        ->and(PayrollOneOffEarning::query()->count())->toBe(2);
});

it('recomputes an unpaid current-month line once the norm is set', function (): void {
    $plan = mealPlan($this, ['exempt_daily_norm' => null]);
    $this->service->enroll($plan, 'E-1', '2026-10-01');
    expect(PayrollOneOffEarning::query()->where('amount', '>', 0)->sole()->amount)->toBe(150.0);

    app(BenefitPlanService::class)->save(['code' => $plan->code, 'name' => $plan->name, 'type' => 'meal_card', 'monthly_cost' => 150, 'employer_share_percent' => 100, 'exempt_daily_norm' => 5], $plan->id);
    app(BenefitPayrollSync::class)->syncEmployee('E-1');

    $value = PayrollOneOffEarning::query()->where('amount', '>', 0)->sole();
    expect($value->amount)->toBe(40.0)->and($value->code)->toBe('benefit_in_kind_dsmf');
});

it('falls back to the global attendance calendar when no monthly summary exists', function (): void {
    AttendanceMonthlySummary::query()->delete();

    // Oktyabr 2026: 22 iş günü (B–C) → norma 110 AZN.
    $this->service->enroll(mealPlan($this, ['monthly_cost' => 110]), 'E-1', '2026-10-01');

    expect(PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->count())->toBe(0);
});

it('counts only the days the employee was at work, not vacation days', function (): void {
    // 5–9 oktyabr məzuniyyət (5 iş günü): norma 5 × (22 − 5) = 85 AZN; 110 AZN kart → 25 AZN artıq.
    DB::table('personnel_vacations')->insert([
        'tabel_no' => 'E-1', 'vacation_places' => 'Bakı', 'duration' => 5,
        'start_date' => '2026-10-05', 'end_date' => '2026-10-09', 'return_work_date' => '2026-10-12',
        'order_given_by' => 'HR', 'added_by' => User::factory()->create()->id,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->service->enroll(mealPlan($this, ['monthly_cost' => 110]), 'E-1', '2026-10-01');

    $value = PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->where('amount', '>', 0)->sole();
    expect($value->amount)->toBe(25.0)->and($value->code)->toBe('benefit_in_kind_dsmf');
});

it('exempts one employee who eats on e-invoice while the plan stays card based for the rest', function (): void {
    $this->benefitsPersonnel('E-2');
    $this->benefitsCompensation('E-2', 1500);
    $plan = mealPlan($this);
    $mine = $this->service->enroll($plan, 'E-1', '2026-10-01');
    $this->service->enroll($plan, 'E-2', '2026-10-01');
    expect(PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->count())->toBe(2);

    $this->service->setInvoiceBased($mine, true);

    expect(PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->count())->toBe(0)
        ->and(PayrollOneOffEarning::query()->where('tabel_no', 'E-2')->count())->toBe(2);

    $this->service->setInvoiceBased($mine->refresh(), false);
    expect(PayrollOneOffEarning::query()->where('tabel_no', 'E-1')->count())->toBe(2);
});

it('rejects a negative daily norm', function (): void {
    app(BenefitPlanService::class)->save(['code' => 'NEG', 'name' => 'Mənfi', 'type' => 'meal_card', 'monthly_cost' => 10, 'exempt_daily_norm' => -1]);
})->throws(ValidationException::class);

it('shows the social insurance over-withheld in a paid month before the norm in the tax adjustment report', function (): void {
    $plan = mealPlan($this, ['exempt_daily_norm' => null]);
    $this->service->enroll($plan, 'E-1', '2026-10-01');

    $runs = app(PayrollRunService::class);
    $regime = (int) $this->compensation->regime_id;
    $runs->lock($runs->approve($runs->calculate($runs->createRun(app(PayrollPeriodService::class)->createPeriod(2026, 10), $regime))));

    // Norma sonradan: 22 × 5 = 110 AZN azad, 40 AZN yalnız DSMF + gəlir vergisi; işsizlik/tibbi heç vaxt.
    app(BenefitPlanService::class)->save(['code' => $plan->code, 'name' => $plan->name, 'type' => 'meal_card', 'monthly_cost' => 150, 'employer_share_percent' => 100, 'exempt_daily_norm' => 5], $plan->id);

    $row = collect(app(TaxAdjustmentReportService::class)->build('2026-10', '2026-10'))->sole();

    expect($row['unemployment_ee_diff'])->toBe(0.75)
        ->and($row['medical_ee_diff'])->toBeGreaterThan(0.0)
        // Özəl sektor: 200 AZN-dən yuxarı hissəyə DSMF 10% — 110 × 10% = 11.
        ->and($row['dsmf_ee_diff'])->toBe(11.0)
        ->and($row['dsmf_er_diff'])->toBeGreaterThan(0.0)
        ->and(PayrollOneOffEarning::query()->where('amount', '>', 0)->sole()->amount)->toBe(150.0);
});
