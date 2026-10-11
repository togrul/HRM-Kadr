<?php

use App\Models\BenefitEnrollment;
use App\Models\BenefitRenewalWindow;
use App\Modules\Benefits\Application\Services\BenefitEnrollmentService;
use App\Modules\Benefits\Livewire\MyBenefits;
use App\Modules\Benefits\Livewire\Tabs\RenewalsTab;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\Feature\Benefits\Concerns\BuildsBenefitsFixtures;

/*
 * İllik yenilənmə: müqavilə bitməzdən əvvəl pəncərə açılır (scheduler + bildiriş), işçilər
 * yeniləyir / imtina edir / başqa paketə keçir; bağlananda qərarlar tətbiq olunur.
 */

uses(BuildsBenefitsFixtures::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-11-20 06:00:00');
    $this->useProfile('private');
    $this->seedBenefitsReferenceData();
    $this->service = app(BenefitEnrollmentService::class);
    $this->plan = $this->benefitsPlan('MED-A', 'medical_insurance', [
        'contract_start' => '2026-01-01',
        'contract_end' => '2026-12-31',
        'renewal_window_days' => 45,
        'auto_renew' => true,
    ]);

    foreach (['E-1', 'E-2', 'E-3'] as $tabelNo) {
        $this->benefitsPersonnel($tabelNo);
        $this->benefitsCompensation($tabelNo);
    }

    $this->spouse = $this->benefitsKinship('E-1', 'Həyat Yoldaşı');
    $this->first = $this->service->enroll($this->plan, 'E-1', '2026-02-01', [$this->spouse]);
    $this->second = $this->service->enroll($this->plan, 'E-2', '2026-02-01');
    $this->third = $this->service->enroll($this->plan, 'E-3', '2026-02-01');
});

afterEach(fn () => Carbon::setTestNow());

it('opens the election window on schedule and notifies enrolled employees', function (): void {
    $user = $this->benefitsUser(['show-my-hr'], [2]);
    $this->linkUserToPersonnel($user, \App\Models\Personnel::query()->where('tabel_no', 'E-1')->firstOrFail());

    Carbon::setTestNow('2026-11-10 06:00:00');
    Artisan::call('benefits:daily');
    expect(BenefitRenewalWindow::query()->count())->toBe(0);

    Carbon::setTestNow('2026-11-20 06:00:00');
    Artisan::call('benefits:daily');

    $window = BenefitRenewalWindow::query()->sole();
    expect($window->status)->toBe('open')
        ->and($window->closes_on->toDateString())->toBe('2026-12-31')
        ->and($window->next_contract_end->toDateString())->toBe('2027-12-31')
        ->and($this->first->refresh()->renewal_window_id)->toBe($window->id)
        ->and($user->notifications()->count())->toBe(1)
        ->and($user->notifications()->first()->data['action'])->toBe('benefitsRenewal');

    Artisan::call('benefits:daily');
    expect(BenefitRenewalWindow::query()->count())->toBe(1);
});

it('applies renew, opt-out, the auto-renew default and switches when the window closes', function (): void {
    $this->benefitsPersonnel('E-4');
    $silent = $this->service->enroll($this->plan, 'E-4', '2026-03-01');
    Artisan::call('benefits:daily');
    $planB = $this->benefitsPlan('MED-B', 'medical_insurance', ['monthly_cost' => 150]);

    $user = $this->benefitsUser(['show-my-hr'], [2]);
    $this->linkUserToPersonnel($user, \App\Models\Personnel::query()->where('tabel_no', 'E-2')->firstOrFail());
    $this->actingAs($user);
    Livewire::test(MyBenefits::class)->call('decide', $this->second->id, 'opt_out')->assertHasNoErrors();

    $this->service->decideRenewal($this->first->refresh(), 'renew');

    // E-3 switches to plan B from the next term.
    $switcher = $this->benefitsUser(['show-my-hr'], [2]);
    $this->linkUserToPersonnel($switcher, \App\Models\Personnel::query()->where('tabel_no', 'E-3')->firstOrFail());
    $this->actingAs($switcher);
    Livewire::test(MyBenefits::class)
        ->call('startSwitch', $this->third->id)
        ->set('form.plan_id', (string) $planB->id)
        ->call('enroll')
        ->assertHasNoErrors();

    // New price for the next term.
    $this->plan->update(['monthly_cost' => 120]);

    Carbon::setTestNow('2027-01-01 06:00:00');
    Artisan::call('benefits:daily');

    expect(BenefitRenewalWindow::query()->sole()->status)->toBe('closed')
        ->and($this->first->refresh()->end_date->toDateString())->toBe('2026-12-31')
        ->and($this->first->end_reason)->toBe('renewal')
        ->and($this->second->refresh()->end_reason)->toBe('opt_out')
        ->and($this->third->refresh()->end_date->toDateString())->toBe('2026-12-31');

    $renewed = BenefitEnrollment::query()->where('renewal_of_id', $this->first->id)->sole();
    expect($renewed->start_date->toDateString())->toBe('2027-01-01')
        ->and($renewed->source)->toBe('renewal')
        ->and($renewed->dependents)->toHaveCount(1)
        // 120 × 20% + 50 × 50% = 49
        ->and((string) $renewed->employee_amount)->toBe('49.00');

    expect(BenefitEnrollment::query()->where('tabel_no', 'E-2')->where('start_date', '2027-01-01')->exists())->toBeFalse()
        ->and(BenefitEnrollment::query()->where('tabel_no', 'E-3')->where('benefit_plan_id', $planB->id)->value('start_date')?->toDateString())->toBe('2027-01-01')
        ->and(BenefitEnrollment::query()->where('tabel_no', 'E-3')->where('benefit_plan_id', $this->plan->id)->where('start_date', '2027-01-01')->exists())->toBeFalse();

    // E-4 cavab vermədi — paket avtomatik yenilənir.
    expect(BenefitEnrollment::query()->where('renewal_of_id', $silent->id)->value('start_date')?->toDateString())->toBe('2027-01-01');

    $this->plan->refresh();
    expect($this->plan->contract_start->toDateString())->toBe('2027-01-01')
        ->and($this->plan->contract_end->toDateString())->toBe('2027-12-31');
});

it('lets HR open a window early and refuses a second one for the same term', function (): void {
    $this->actingAs($this->benefitsUser(['show-benefits', 'manage-benefits']));

    Livewire::test(RenewalsTab::class)->call('openWindow', $this->plan->id)->assertHasNoErrors();
    expect(BenefitRenewalWindow::query()->count())->toBe(1);

    Livewire::test(RenewalsTab::class)->call('openWindow', $this->plan->id)->assertHasErrors('plan');
    expect(BenefitRenewalWindow::query()->count())->toBe(1);
});

it('refuses a renewal decision when no window is open', function (): void {
    expect(fn () => $this->service->decideRenewal($this->first, 'renew'))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});
