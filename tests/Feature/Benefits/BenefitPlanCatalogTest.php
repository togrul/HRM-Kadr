<?php

use App\Models\BenefitPlan;
use App\Modules\Benefits\Application\Services\BenefitEnrollmentService;
use App\Modules\Benefits\Application\Services\BenefitTypes;
use App\Modules\Benefits\Livewire\Dashboard;
use App\Modules\Benefits\Livewire\Tabs\PlansTab;
use Livewire\Livewire;
use Tests\Feature\Benefits\Concerns\BuildsBenefitsFixtures;

/*
 * Paket kataloqu və standart vergi rejimləri (VM m.98, 102.1.8; Sosial sığorta Qanunu m.15).
 */

uses(BuildsBenefitsFixtures::class);

beforeEach(function (): void {
    $this->useProfile('private');
    $this->seedBenefitsReferenceData();
});

it('ships the documented default tax treatment per benefit type', function (string $type, bool $taxable, bool $social, string $basis): void {
    expect(BenefitTypes::defaults($type))->toMatchArray(['taxable' => $taxable, 'affects_social' => $social, 'basis' => $basis])
        ->and(__('benefits::dashboard.tax_basis.'.$basis.'.url'))->toStartWith('https://e-qanun.az/framework/');
})->with([
    'tibbi sığorta — VM 102.1.8' => ['medical_insurance', false, false, 'vm_102_1_8_medical'],
    'həyat (risk) — VM 98.2.5' => ['life_insurance', true, true, 'vm_98_2_5'],
    'yığım/pensiya — VM 102.1.8' => ['life_savings_insurance', false, false, 'vm_102_1_8_savings'],
    'yemək — VM 98.5' => ['meal_card', false, true, 'vm_98_5_meal'],
    'nəqliyyat — VM 98.2.3' => ['transport', true, true, 'vm_98_2_3'],
    'mobil — işgüzar xərc' => ['mobile', false, false, 'business_expense'],
    'idman — VM 98.5' => ['gym', false, true, 'vm_98_5_social'],
    'digər — VM 98.1' => ['other', true, true, 'vm_98_1'],
]);

it('lets HR create a plan with the type defaults and an accountant change the tax treatment', function (): void {
    $this->actingAs($this->benefitsUser(['show-benefits', 'manage-benefits']));

    Livewire::test(PlansTab::class)
        ->call('openPanel', 'plan')
        ->set('planForm.type', 'life_insurance')
        ->assertSet('planForm.taxable', true)
        ->assertSet('planForm.tax_basis', 'vm_98_2_5')
        ->set('planForm.code', 'LIFE-1')
        ->set('planForm.name', 'Həyat sığortası')
        ->set('planForm.provider', 'Sığorta ASC')
        ->set('planForm.monthly_cost', '25')
        ->set('planForm.contract_start', '2026-01-01')
        ->set('planForm.contract_end', '2026-12-31')
        ->set('planForm.eligibility.after_probation', true)
        ->set('planForm.eligibility.vtisk_categories', ['rehber'])
        ->call('savePlan')
        ->assertHasNoErrors();

    $plan = BenefitPlan::query()->where('code', 'LIFE-1')->sole();
    expect($plan->taxable)->toBeTrue()
        ->and($plan->provider)->toBe('Sığorta ASC')
        ->and($plan->eligibility)->toMatchArray(['after_probation' => true, 'vtisk_categories' => ['rehber']]);

    Livewire::test(PlansTab::class)
        ->call('editPlan', $plan->id)
        ->set('planForm.taxable', false)
        ->set('planForm.tax_basis', 'vm_102_1_8_savings')
        ->call('savePlan')
        ->assertHasNoErrors();

    expect($plan->refresh()->taxable)->toBeFalse()
        ->and($plan->tax_basis)->toBe('vm_102_1_8_savings');
});

it('validates the plan form and keeps a plan with enrollments from being deleted', function (): void {
    $this->actingAs($this->benefitsUser(['show-benefits', 'manage-benefits']));

    Livewire::test(PlansTab::class)
        ->call('openPanel', 'plan')
        ->set('planForm.code', 'bad code!')
        ->set('planForm.employer_share_percent', '150')
        ->set('planForm.contract_start', '2026-12-31')
        ->set('planForm.contract_end', '2026-01-01')
        ->call('savePlan')
        ->assertHasErrors(['planForm.code', 'planForm.name', 'planForm.employer_share_percent', 'planForm.contract_end']);

    $plan = $this->benefitsPlan('MED-A');
    $this->benefitsPersonnel('E-1');
    app(BenefitEnrollmentService::class)->enroll($plan, 'E-1', now()->toDateString());

    Livewire::test(PlansTab::class)->call('deletePlan', $plan->id)->assertHasErrors('plan');
    expect(BenefitPlan::query()->whereKey($plan->id)->exists())->toBeTrue();
});

it('renders the workspace with every tab for a full-access user', function (string $tab): void {
    $this->actingAs($this->benefitsUser(['show-benefits', 'manage-benefits', 'show-benefit-costs']));
    $this->benefitsPlan('MED-A');

    $this->get(route('benefits', ['tab' => $tab]))->assertOk()->assertSee(__('benefits::dashboard.tabs.'.$tab));
    Livewire::test(Dashboard::class)->call('switchTab', $tab)->assertSet('activeTab', $tab);
})->with(['plans', 'enrollments', 'renewals', 'costs']);
