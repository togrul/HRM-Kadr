<?php

use App\Models\BenefitEnrollment;
use App\Modules\Benefits\Application\Services\BenefitEnrollmentService;
use App\Modules\Benefits\Livewire\MyBenefits;
use App\Modules\Benefits\Livewire\Tabs\EnrollmentsTab;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Benefits\Concerns\BuildsBenefitsFixtures;

/*
 * Qoşulma: HR və işçinin özü, ailə üzvləri (qohumluq qeydlərindən), məbləğ bölgüsü,
 * üst-üstə düşmə, uyğunluq istisnası və audit.
 */

uses(BuildsBenefitsFixtures::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-15 10:00:00');
    $this->useProfile('private');
    $this->seedBenefitsReferenceData();
    $this->service = app(BenefitEnrollmentService::class);
});

afterEach(fn () => Carbon::setTestNow());

it('splits the monthly cost between employer and employee, dependents included', function (): void {
    $plan = $this->benefitsPlan('MED-A');
    $this->benefitsPersonnel('E-1');
    $spouse = $this->benefitsKinship('E-1', 'Həyat Yoldaşı', 1);
    $child = $this->benefitsKinship('E-1', 'Övlad Bir', 2);

    $enrollment = $this->service->enroll($plan, 'E-1', '2026-11-01', [$spouse, $child]);

    // Özü: 100 (80% işəgötürən) → işçi 20; 2 ailə üzvü × 50 (50%) → işçi 50; cəmi 200.
    expect((string) $enrollment->employee_amount)->toBe('70.00')
        ->and((string) $enrollment->employer_amount)->toBe('130.00')
        ->and($enrollment->dependents->pluck('full_name')->sort()->values()->all())->toBe(['Həyat Yoldaşı', 'Övlad Bir'])
        ->and($enrollment->dependents->firstWhere('full_name', 'Həyat Yoldaşı')->relation)->toBe('Həyat yoldaşı');

    $updated = $this->service->updateDependents($enrollment, [$spouse]);
    expect((string) $updated->employee_amount)->toBe('45.00')
        ->and($updated->dependents)->toHaveCount(1);

    expect(Activity::query()->where('log_name', 'benefits')->where('subject_type', BenefitEnrollment::class)->exists())->toBeTrue();
});

it('refuses a dependent from another employee and too many dependents', function (): void {
    $plan = $this->benefitsPlan('MED-A', 'medical_insurance', ['max_dependents' => 1]);
    $this->benefitsPersonnel('E-1');
    $this->benefitsPersonnel('E-2');
    $foreign = $this->benefitsKinship('E-2', 'Başqasının qohumu');
    $own1 = $this->benefitsKinship('E-1', 'Bir');
    $own2 = $this->benefitsKinship('E-1', 'İki');

    expect(fn () => $this->service->enroll($plan, 'E-1', '2026-11-01', [$foreign]))->toThrow(ValidationException::class)
        ->and(fn () => $this->service->enroll($plan, 'E-1', '2026-11-01', [$own1, $own2]))->toThrow(ValidationException::class);
});

it('allows only one plan of an exclusive type at a time (medical A or B)', function (): void {
    $a = $this->benefitsPlan('MED-A');
    $b = $this->benefitsPlan('MED-B');
    $this->benefitsPersonnel('E-1');

    $first = $this->service->enroll($a, 'E-1', '2026-11-01');

    expect(fn () => $this->service->enroll($b, 'E-1', '2026-12-01'))->toThrow(ValidationException::class);

    $this->service->end($first, '2026-12-31');
    expect($this->service->enroll($b, 'E-1', '2027-01-01')->status)->toBe('active');
});

it('needs a written justification to enroll an ineligible employee', function (): void {
    $plan = $this->benefitsPlan('MOB', 'mobile', ['eligibility' => ['vtisk_categories' => ['rehber']]]);
    $this->benefitsPersonnel('E-1');

    expect(fn () => $this->service->enroll($plan, 'E-1', '2026-11-01'))->toThrow(ValidationException::class)
        ->and(fn () => $this->service->enroll($plan, 'E-1', '2026-11-01', [], 'hr', true))->toThrow(ValidationException::class);

    $enrollment = $this->service->enroll($plan, 'E-1', '2026-11-01', [], 'hr', true, 'İdarə heyətinin qərarı');
    expect($enrollment->eligibility_overridden)->toBeTrue();
});

it('lets HR enroll only employees inside their structures', function (): void {
    $plan = $this->benefitsPlan('MED-A');
    $this->benefitsPersonnel('IN-1', ['structure_id' => 2]);
    $this->benefitsPersonnel('OUT-1', ['structure_id' => 3]);
    $this->actingAs($this->benefitsUser(['show-benefits', 'manage-benefits'], [1, 2]));

    Livewire::test(EnrollmentsTab::class)->call('selectPersonnel', 'OUT-1', 'Xarici')->assertForbidden();

    Livewire::test(EnrollmentsTab::class)
        ->call('openPanel', 'enroll')
        ->call('selectPersonnel', 'IN-1', 'Daxili')
        ->set('enrollForm.plan_id', (string) $plan->id)
        ->set('enrollForm.start_date', '2026-11-01')
        ->call('saveEnrollment')
        ->assertHasNoErrors();

    $outside = $this->service->enroll($plan, 'OUT-1', '2026-11-01');

    $list = Livewire::test(EnrollmentsTab::class)->instance()->enrollments();
    expect(collect($list->items())->pluck('tabel_no')->all())->toBe(['IN-1']);

    expect(fn () => Livewire::test(EnrollmentsTab::class)->call('cancelEnrollment', $outside->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect($outside->refresh()->status)->toBe('active');
});

it('needs the manage permission for enrollment changes', function (): void {
    $plan = $this->benefitsPlan('MED-A');
    $this->benefitsPersonnel('IN-1');
    $enrollment = $this->service->enroll($plan, 'IN-1', '2026-11-01');
    $this->actingAs($this->benefitsUser(['show-benefits']));

    Livewire::test(EnrollmentsTab::class)->call('cancelEnrollment', $enrollment->id)->assertForbidden();
    Livewire::test(EnrollmentsTab::class)->call('openPanel', 'enroll')->assertForbidden();
});

it('lets an employee enroll themselves with family members from their own file', function (): void {
    $plan = $this->benefitsPlan('MED-A');
    $hrOnly = $this->benefitsPlan('GYM', 'gym', ['self_service' => false]);
    $personnel = $this->benefitsPersonnel('E-1');
    $spouse = $this->benefitsKinship('E-1', 'Həyat Yoldaşı');
    $user = $this->benefitsUser(['show-my-hr'], [2]);
    $this->linkUserToPersonnel($user, $personnel);
    $this->actingAs($user);

    $component = Livewire::test(MyBenefits::class);
    expect($component->instance()->availablePlans->pluck('code')->all())->toBe(['MED-A']);

    $component->call('startEnroll', $hrOnly->id)->assertForbidden();

    Livewire::test(MyBenefits::class)
        ->call('startEnroll', $plan->id)
        ->set('form.start_date', '2026-10-01')
        ->call('enroll')
        ->assertHasErrors('form.start_date');

    Livewire::test(MyBenefits::class)
        ->call('startEnroll', $plan->id)
        ->set('form.kinship_ids', [(string) $spouse])
        ->call('enroll')
        ->assertHasNoErrors();

    $enrollment = BenefitEnrollment::query()->where('tabel_no', 'E-1')->firstOrFail();
    expect($enrollment->source)->toBe('self_service')
        ->and($enrollment->dependents)->toHaveCount(1)
        ->and($enrollment->start_date->toDateString())->toBe('2026-11-01');
});

it('refuses another employee\'s card in My HR', function (): void {
    $own = $this->benefitsPersonnel('E-1');
    $other = $this->benefitsPersonnel('E-2');
    $user = $this->benefitsUser(['show-my-hr'], [2]);
    $this->linkUserToPersonnel($user, $own);
    $this->actingAs($user);

    Livewire::test(MyBenefits::class, ['personnelId' => $other->id])->assertForbidden();
});

it('opens as a My HR tab', function (): void {
    $this->benefitsPlan('MED-A');
    $personnel = $this->benefitsPersonnel('E-1');
    $user = $this->benefitsUser(['show-my-hr'], [2]);
    $this->linkUserToPersonnel($user, $personnel);

    $this->actingAs($user)
        ->get(route('my-hr', ['tab' => 'benefits']))
        ->assertOk()
        ->assertSee(__('personnel::my_hr.tabs.benefits'))
        ->assertSee(__('benefits::dashboard.my.available'))
        ->assertSee(__('benefits::dashboard.rewards.title'));
});
