<?php

use App\Models\BenefitEnrollment;
use App\Models\OrderLog;
use App\Modules\Benefits\Application\Services\BenefitEnrollmentService;
use App\Modules\Orders\Infrastructure\Document\Effects\TerminationEffect;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Benefits\Concerns\BuildsBenefitsFixtures;

/*
 * İşdən çıxma: xitam əmrinin effekti əməkdaşın kartına işdən çıxma tarixini yazanda
 * qoşulmalar həmin gün bitir (sonra başlamalı olanlar ləğv olunur); əmr geri alınanda bərpa
 * olunur. Benefits modulu Orders-dən asılı deyil — Personnel modelinin hadisəsinə baxır.
 */

uses(BuildsBenefitsFixtures::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-15 10:00:00');
    $this->useProfile('private');
    $this->seedBenefitsReferenceData();
    $this->personnel = $this->benefitsPersonnel('E-1');
    $this->benefitsCompensation('E-1', 1500);
    $this->service = app(BenefitEnrollmentService::class);
});

afterEach(fn () => Carbon::setTestNow());

it('ends enrollments on the termination order\'s last day and restores them when the order is reversed', function (): void {
    $medical = $this->service->enroll($this->benefitsPlan('MED-A'), 'E-1', '2026-09-01');
    $this->service->end($medical, '2027-06-30');
    $future = $this->service->enroll($this->benefitsPlan('GYM', 'gym'), 'E-1', '2026-12-01');

    $effect = app(TerminationEffect::class);
    $order = new OrderLog;
    $effect->apply($order, ['date' => '31.10.2026'], $this->personnel->refresh());

    $medical->refresh();
    expect($medical->end_date->toDateString())->toBe('2026-10-31')
        ->and($medical->end_reason)->toBe('termination')
        ->and($medical->status)->toBe('active')
        ->and($future->refresh()->status)->toBe('cancelled');

    $effect->reverse($order, [], $this->personnel->refresh());

    $medical->refresh();
    expect($medical->end_date->toDateString())->toBe('2027-06-30')
        ->and($medical->end_reason)->toBe('manual')
        ->and($future->refresh()->status)->toBe('active')
        ->and($future->end_date)->toBeNull();
});

it('closes the enrollment for good once the last day has passed', function (): void {
    $medical = $this->service->enroll($this->benefitsPlan('MED-A'), 'E-1', '2026-09-01');

    $this->personnel->forceFill(['leave_work_date' => '2026-10-20'])->save();

    Carbon::setTestNow('2026-10-21 05:00:00');
    Artisan::call('benefits:daily');

    expect($medical->refresh()->status)->toBe('ended');
});

it('catches a termination written without model events in the daily job', function (): void {
    $medical = $this->service->enroll($this->benefitsPlan('MED-A'), 'E-1', '2026-09-01');

    DB::table('personnels')->where('tabel_no', 'E-1')->update(['leave_work_date' => '2026-10-10']);
    Artisan::call('benefits:daily');

    $medical->refresh();
    expect($medical->status)->toBe(BenefitEnrollment::STATUS_ENDED)
        ->and($medical->end_date->toDateString())->toBe('2026-10-10')
        ->and($medical->end_reason)->toBe('termination');
});
