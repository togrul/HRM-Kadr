<?php

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PersonnelVacation;
use App\Models\UserPersonnelLink;
use App\Modules\Personnel\Livewire\MyHr\MyHrDashboard;
use App\Modules\Personnel\Livewire\MyHr\MyHrDocuments;
use App\Modules\Personnel\Livewire\MyHr\MyHrPayslips;
use App\Modules\Personnel\Livewire\MyHr\MyHrRequests;
use App\Modules\Personnel\Livewire\MyHr\MyHrSummary;
use App\Services\UserPersonnelLinkResolver;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

require_once __DIR__.'/Support/identity_fixtures.php';

/*
 * Hücum: şəxsi kabinet komponentləri kliyentdən gələn `personnelId`-yə etibar edirdi —
 * istifadəçi id-ni dəyişib başqasının vərəqəsini, sənədlərini görür, onun adından
 * müraciət yaradırdı. İndi əməkdaş hər sorğuda serverdə açıq bağdan müəyyən olunur.
 */

beforeEach(function (): void {
    $this->me = identityPersonnel('MH-ME');
    $this->victim = identityPersonnel('MH-VICTIM');
    $this->user = identityUser(['show-my-hr', 'view-own-personnel-documents', 'submit-self-service-vacations']);
    identityLink($this->user, $this->me);
    $this->actingAs($this->user);
});

it('refuses to mount a cabinet tab for someone else\'s personnel id', function (string $component): void {
    Livewire::test($component, ['personnelId' => $this->victim->id])->assertForbidden();
})->with([
    'payslips' => MyHrPayslips::class,
    'documents' => MyHrDocuments::class,
    'summary' => MyHrSummary::class,
    'requests' => MyHrRequests::class,
]);

it('locks the personnel id against client-side changes', function (): void {
    $component = Livewire::test(MyHrPayslips::class, ['personnelId' => $this->me->id]);

    expect(fn () => $component->set('personnelId', $this->victim->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('resolves the cabinet owner server-side even when no id is passed', function (): void {
    expect(Livewire::test(MyHrDashboard::class)->get('personnelId'))->toBe($this->me->id)
        ->and(Livewire::test(MyHrSummary::class)->get('personnelId'))->toBe($this->me->id);
});

it('opens only the user\'s own payslip', function (): void {
    $period = PayrollPeriod::query()->create([
        'code' => '2026-08', 'year' => 2026, 'month' => 8,
        'starts_on' => '2026-08-01', 'ends_on' => '2026-08-31', 'currency' => 'AZN', 'status' => 'open',
    ]);
    $run = PayrollRun::query()->create(['payroll_period_id' => $period->id, 'status' => 'locked']);
    $theirs = Payslip::query()->create([
        'payroll_run_id' => $run->id, 'tabel_no' => $this->victim->tabel_no, 'gross' => 5000,
        'total_deductions' => 500, 'net' => 4500, 'employer_cost' => 0, 'currency' => 'AZN', 'status' => 'locked',
    ]);

    Livewire::test(MyHrPayslips::class)
        ->call('viewPayslip', $theirs->id)
        ->assertStatus(404)
        ->assertSet('selectedPayslipId', null);
});

it('files a self-service request only for the linked personnel', function (): void {
    Livewire::test(MyHrRequests::class)
        ->call('openCreateForm', 'vacation')
        ->set('vacationForm.vacation_places', 'Qəbələ')
        ->set('vacationForm.start_date', '2026-12-01')
        ->set('vacationForm.end_date', '2026-12-03')
        ->call('storeVacationRequest');

    expect(PersonnelVacation::query()->pluck('tabel_no')->all())->toBe([$this->me->tabel_no]);
});

it('closes an open cabinet as soon as the admin removes the link', function (): void {
    $component = Livewire::test(MyHrPayslips::class);

    UserPersonnelLink::query()->where('user_id', $this->user->id)->delete();
    app(UserPersonnelLinkResolver::class)->forget();

    $component->call('closePayslip')->assertForbidden();
});
