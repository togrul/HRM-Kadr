<?php

use App\Models\EmployeeCompensationLine;
use App\Modules\Benefits\Application\Services\BenefitCostReportService;
use App\Modules\Benefits\Application\Services\BenefitEnrollmentService;
use App\Modules\Benefits\Application\Services\TotalRewardsService;
use App\Modules\Benefits\Livewire\Tabs\CostsTab;
use App\Modules\Benefits\Livewire\TotalRewards;
use App\Modules\Personnel\Livewire\PersonnelProfile;
use App\Services\StructureScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Feature\Benefits\Concerns\BuildsBenefitsFixtures;

/*
 * Total rewards (maaş + əlavələr + imtiyazlar) və xərc hesabatının struktur görünürlüyü.
 */

uses(BuildsBenefitsFixtures::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-15 10:00:00');
    $this->useProfile('private');
    $this->seedBenefitsReferenceData();
    $this->service = app(BenefitEnrollmentService::class);
    $this->inside = $this->benefitsPersonnel('IN-1', ['structure_id' => 2]);
    $this->outside = $this->benefitsPersonnel('OUT-1', ['structure_id' => 3]);
    $compensation = $this->benefitsCompensation('IN-1', 2000);
    $this->benefitsCompensation('OUT-1', 3000);

    $allowance = DB::table('compensation_components')->where('type', 'earning')->where('code', '!=', 'base')->value('id');
    EmployeeCompensationLine::query()->create(['employee_compensation_id' => $compensation->id, 'component_id' => $allowance, 'amount' => 150]);

    $this->medical = $this->benefitsPlan('MED-A');
    $this->meal = $this->benefitsPlan('MEAL', 'meal_card', ['monthly_cost' => 60, 'employer_share_percent' => 100, 'allows_dependents' => false]);
    $this->service->enroll($this->medical, 'IN-1', '2026-10-01', [$this->benefitsKinship('IN-1', 'Həyat Yoldaşı')]);
    $this->service->enroll($this->meal, 'IN-1', '2026-10-01');
    $this->service->enroll($this->medical, 'OUT-1', '2026-10-01');
});

afterEach(fn () => Carbon::setTestNow());

it('adds salary, recurring allowances and employer-paid benefits into one monthly value', function (): void {
    $rewards = app(TotalRewardsService::class)->forEmployee('IN-1');

    // Tibbi: 100 + 50 (ailə üzvü) = 150; işçi 20 + 25 = 45; işəgötürən 105. Yemək kartı: 60.
    expect($rewards['salary'])->toEqual(2000.0)
        ->and($rewards['allowances'])->toEqual(150.0)
        ->and($rewards['benefits_employer'])->toEqual(165.0)
        ->and($rewards['benefits_employee'])->toEqual(45.0)
        ->and($rewards['total'])->toEqual(2315.0)
        ->and($rewards['benefit_lines'])->toHaveCount(2);
});

it('masks the salary for HR without the amounts permission and refuses out-of-scope employees', function (): void {
    $this->actingAs($this->benefitsUser(['show-benefits'], [1, 2]));

    Livewire::test(TotalRewards::class, ['tabelNo' => 'IN-1'])
        ->assertSee('•••')
        ->assertDontSee('2 000,00');

    Livewire::test(TotalRewards::class, ['tabelNo' => 'OUT-1'])->assertForbidden();

    $this->actingAs($this->benefitsUser(['show-benefits', 'view-compensation-amounts'], [1, 2]));
    Livewire::test(TotalRewards::class, ['tabelNo' => 'IN-1'])->assertSee('2 000,00')->assertSee('2 315,00');
});

it('shows an employee their own breakdown without HR permissions', function (): void {
    $user = $this->benefitsUser(['show-my-hr'], [2]);
    $this->linkUserToPersonnel($user, $this->inside);
    $this->actingAs($user);

    Livewire::test(TotalRewards::class, ['tabelNo' => 'IN-1'])->assertSee('2 315,00');
    Livewire::test(TotalRewards::class, ['tabelNo' => 'OUT-1'])->assertForbidden();
});

it('adds a pay-and-benefits section to the employee profile', function (): void {
    $this->actingAs($this->benefitsUser(['show-personnels', 'show-benefits', 'view-compensation-amounts']));

    Livewire::test(PersonnelProfile::class, ['personnel' => $this->inside])
        ->call('setSection', PersonnelProfile::REWARDS_SECTION)
        ->assertSet('section', PersonnelProfile::REWARDS_SECTION)
        ->assertSee(__('benefits::dashboard.rewards.title'));
});

it('reports costs by plan and unit only within the user\'s structures', function (): void {
    $all = app(BenefitCostReportService::class)->report(StructureScope::all(), ['month' => '2026-10']);
    expect($all['totals']['enrollees'])->toBe(3)
        ->and($all['totals']['employer'])->toEqual(80.0 + 105.0 + 60.0);

    $scoped = app(BenefitCostReportService::class)->report(StructureScope::of([1, 2]), ['month' => '2026-10']);
    expect($scoped['totals']['enrollees'])->toBe(2)
        ->and($scoped['totals']['dependents'])->toBe(1)
        ->and(collect($scoped['by_structure'])->pluck('structure')->all())->toBe(['İT şöbəsi'])
        ->and(collect($scoped['by_plan'])->pluck('employer', 'plan')->all())->toEqual(['Paket MED-A' => 105.0, 'Paket MEAL' => 60.0]);

    // Kənar struktur süzgəci görünürlüyü genişləndirmir (fail closed).
    $foreign = app(BenefitCostReportService::class)->report(StructureScope::of([1, 2]), ['month' => '2026-10', 'structure_id' => 3]);
    expect($foreign['totals']['enrollees'])->toBe(0);

    // Üst struktur süzgəci alt strukturları da əhatə edir; paket süzgəci.
    $parent = app(BenefitCostReportService::class)->report(StructureScope::all(), ['month' => '2026-10', 'structure_id' => 1, 'plan_id' => $this->meal->id]);
    expect($parent['totals']['enrollees'])->toBe(1);

    // Başlamamış ay.
    expect(app(BenefitCostReportService::class)->report(StructureScope::all(), ['month' => '2026-09'])['totals']['enrollees'])->toBe(0);
});

it('needs its own permission for the cost tab', function (): void {
    $this->actingAs($this->benefitsUser(['show-benefits'], [2]));
    Livewire::test(CostsTab::class)->assertForbidden();

    $this->actingAs($this->benefitsUser(['show-benefits', 'show-benefit-costs'], [2]));
    $report = Livewire::test(CostsTab::class)->set('month', '2026-10')->instance()->report;
    expect($report['totals']['enrollees'])->toBe(2);
});
