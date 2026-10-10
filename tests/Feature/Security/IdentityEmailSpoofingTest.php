<?php

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use App\Services\UserPersonnelLinkResolver;

require_once __DIR__.'/Support/identity_fixtures.php';

/*
 * Hücum: istifadəçi öz e-poçtunu (və ya adını) başqa əməkdaşınkı ilə eyniləşdirib həmin
 * əməkdaşın kabinetinə, əmək haqqı vərəqəsinə və təsdiq hüququna sahib olurdu. İndi
 * eyniləşdirmə yalnız admin tərəfindən yaradılmış açıq bağ ilədir.
 */

it('does not let a user change their own e-mail through the profile', function (): void {
    $victim = identityPersonnel('ID-VICTIM', ['email' => 'ceo@example.test']);
    $attacker = identityUser(['show-my-hr'], ['email' => 'attacker@example.test']);

    $this->actingAs($attacker)
        ->patch('/profile', ['name' => 'Attacker', 'email' => $victim->email])
        ->assertSessionHasNoErrors();

    expect($attacker->fresh()->email)->toBe('attacker@example.test');
});

it('never resolves identity from a matching e-mail or name', function (): void {
    $victim = identityPersonnel('ID-VICTIM2', ['email' => 'victim@example.test', 'name' => 'Aysel', 'surname' => 'Məmmədova']);
    $attacker = identityUser(['show-my-hr'], ['email' => 'victim@example.test', 'name' => 'Aysel Məmmədova']);

    expect(app(UserPersonnelLinkResolver::class)->resolve($attacker))->toBeNull()
        ->and(app(MyHrAccess::class)->resolvePersonnelId($attacker))->toBeNull()
        ->and($attacker->personnel()->first())->toBeNull();

    $this->assertDatabaseMissing('user_personnel_links', ['personnel_id' => $victim->id]);
});

it('does not treat a users.id equal to the approver personnel id as the approver', function (): void {
    $approver = identityPersonnel('ID-APPR');
    $intruder = identityUser();
    // users.id ilə personnels.id ayrı id məkanlarıdır — üst-üstə düşmə hüquq vermir.
    $leave = Leave::withoutEvents(fn (): Leave => Leave::query()->create([
        'tabel_no' => 'ID-OTHER',
        'leave_type_id' => 1,
        'starts_at' => '2026-11-02',
        'ends_at' => '2026-11-03',
        'duration_unit' => 'day',
        'reason' => 'x',
        'status_id' => OrderStatusEnum::PENDING->value,
        'assigned_to' => $intruder->id,
    ]));

    expect($leave->canBeApprovedBy($intruder))->toBeFalse();

    identityLink($intruder, $approver);
    $leave->forceFill(['assigned_to' => $approver->id]);

    expect($leave->canBeApprovedBy($intruder))->toBeTrue();
});

it('lets only the explicitly linked owner print a payslip', function (): void {
    $owner = identityPersonnel('ID-PAY', ['email' => 'pay@example.test']);
    $period = PayrollPeriod::query()->create([
        'code' => '2026-09', 'year' => 2026, 'month' => 9,
        'starts_on' => '2026-09-01', 'ends_on' => '2026-09-30', 'currency' => 'AZN', 'status' => 'open',
    ]);
    $run = PayrollRun::query()->create(['payroll_period_id' => $period->id, 'status' => 'locked']);
    $payslip = Payslip::query()->create([
        'payroll_run_id' => $run->id, 'tabel_no' => $owner->tabel_no, 'gross' => 1000,
        'total_deductions' => 100, 'net' => 900, 'employer_cost' => 0, 'currency' => 'AZN', 'status' => 'locked',
    ]);

    $sameEmail = identityUser([], ['email' => 'pay@example.test']);
    $this->actingAs($sameEmail)->get(route('payroll.payslip.print', $payslip))->assertForbidden();

    $linked = identityUser();
    identityLink($linked, $owner);
    $this->actingAs($linked)->get(route('payroll.payslip.print', $payslip))->assertOk();
});
