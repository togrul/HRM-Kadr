<?php

use App\Modules\Reports\Domain\Models\ReportSchedule;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Göndəriş vaxtının hesablanması bazasız yoxlanılır.
 */
function timingSchedule(array $attributes): ReportSchedule
{
    return (new ReportSchedule)->forceFill($attributes + ['send_time' => '08:00', 'period' => 'current_month']);
}

it('runs a daily schedule later today or tomorrow', function (): void {
    $schedule = timingSchedule(['frequency' => 'daily', 'send_time' => '08:00']);

    expect($schedule->nextRunAfter(CarbonImmutable::parse('2026-03-10 07:59'))->toDateTimeString())->toBe('2026-03-10 08:00:00')
        ->and($schedule->nextRunAfter(CarbonImmutable::parse('2026-03-10 08:00'))->toDateTimeString())->toBe('2026-03-11 08:00:00')
        ->and($schedule->nextRunAfter(CarbonImmutable::parse('2026-12-31 23:00'))->toDateTimeString())->toBe('2027-01-01 08:00:00');
});

it('runs a weekly schedule on the chosen ISO weekday', function (): void {
    // 2026-03-10 çərşənbə axşamıdır (ISO 2).
    $monday = timingSchedule(['frequency' => 'weekly', 'weekday' => 1, 'send_time' => '09:15']);
    $tuesday = timingSchedule(['frequency' => 'weekly', 'weekday' => 2, 'send_time' => '09:15']);

    expect($monday->nextRunAfter(CarbonImmutable::parse('2026-03-10 10:00'))->toDateTimeString())->toBe('2026-03-16 09:15:00')
        ->and($tuesday->nextRunAfter(CarbonImmutable::parse('2026-03-10 09:00'))->toDateTimeString())->toBe('2026-03-10 09:15:00')
        ->and($tuesday->nextRunAfter(CarbonImmutable::parse('2026-03-10 09:15'))->toDateTimeString())->toBe('2026-03-17 09:15:00');
});

it('runs a monthly schedule on the chosen day and never overflows a short month', function (): void {
    $schedule = timingSchedule(['frequency' => 'monthly', 'month_day' => 28, 'send_time' => '06:00']);

    expect($schedule->nextRunAfter(CarbonImmutable::parse('2026-01-30 10:00'))->toDateTimeString())->toBe('2026-02-28 06:00:00')
        ->and($schedule->nextRunAfter(CarbonImmutable::parse('2026-02-28 06:00'))->toDateTimeString())->toBe('2026-03-28 06:00:00')
        ->and(timingSchedule(['frequency' => 'monthly', 'month_day' => 1])->nextRunAfter(CarbonImmutable::parse('2026-12-15'))->toDateTimeString())->toBe('2027-01-01 08:00:00');
});

it('resolves the report period from the run date', function (): void {
    $at = CarbonImmutable::parse('2026-01-01 08:00');

    expect(timingSchedule(['frequency' => 'daily', 'period' => 'current_month'])->reportPeriod($at))->toBe(['year' => 2026, 'month' => 1])
        ->and(timingSchedule(['frequency' => 'daily', 'period' => 'previous_month'])->reportPeriod($at))->toBe(['year' => 2025, 'month' => 12])
        ->and(timingSchedule(['frequency' => 'daily', 'period' => 'previous_month'])->reportPeriod(CarbonImmutable::parse('2026-03-31')))->toBe(['year' => 2026, 'month' => 2]);
});
