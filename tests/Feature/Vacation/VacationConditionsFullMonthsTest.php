<?php

use App\Modules\Vacation\Application\Services\VacationNormEvaluator;
use Carbon\CarbonImmutable;

/*
 * NK-nin 95 nömrəli qərarı, b.10: tam iş aylarının sayı = cəmlənmiş günlər ÷ 30,4 («təqvim
 * günlərinin orta aylıq sayı»); qalıq 15,2 gündən azdırsa atılır, 15,2 və çoxdursa tam ay.
 * Sistem şəraitdə sayılan TƏQVİM günlərini bölür (docs/vacation-legal-basis.md, (c-3)).
 */

it('converts calendar days in conditions to full months by NK 95, b.10', function (int $days, int $months): void {
    expect(VacationNormEvaluator::fullMonths($days))->toBe($months);
})->with([
    'nothing' => [0, 0],
    '15 days — remainder below 15.2 is dropped' => [15, 0],
    '16 days — remainder of 15.2 or more is a full month' => [16, 1],
    '45 days = 1.48' => [45, 1],
    '46 days = 1.51' => [46, 2],
    'April–June (b.7, example 1: «3 aya»)' => [91, 3],
    '122 days = 4.01' => [122, 4],
    '182 days = 5.99' => [182, 6],
    '183 days = 6.02' => [183, 6],
    'whole year' => [365, 12],
    'leap year is capped at 12' => [366, 12],
]);

it('gives the b.7 and b.11 examples with the Σ norm × months ÷ 12 rule', function (): void {
    $days = fn (array $parts): int => (int) round(array_sum(array_map(
        fn (array $p): float => $p[0] * VacationNormEvaluator::fullMonths($p[1]) / 12,
        $parts,
    )), 0, PHP_ROUND_HALF_UP);

    // b.7, 1-ci misal: 12 günlük usta aprel–iyun (30 + 31 + 30 gün) → 3 ay → 3 gün.
    expect($days([[12, 91]]))->toBe(3)
        // b.11 misalı: 12 günlük sexdə 2 ay (61 gün) + 6 günlük sexdə 4 ay (122 gün) → 2 + 2 = 4 gün.
        ->and($days([[12, 61], [6, 122]]))->toBe(4);
});

it('shows why the literal working-day reading is rejected: a full year would be 8–10 months, depending on the week', function (): void {
    // 2026-cı il, 5 günlük iş həftəsi: şənbə-bazar xaric iş günləri (bayramlar çıxılmadan) — 261.
    $workingDays = 0;

    for ($day = CarbonImmutable::parse('2026-01-01'); $day->year === 2026; $day = $day->addDay()) {
        $workingDays += $day->isWeekend() ? 0 : 1;
    }

    expect($workingDays)->toBe(261)
        ->and(VacationNormEvaluator::fullMonths($workingDays))->toBe(9)
        // 6 günlük həftədə (yalnız bazar xaric) — 313 gün → 10 ay: rejimdən asılı nəticə.
        ->and(VacationNormEvaluator::fullMonths(365 - 52))->toBe(10)
        // Təqvim günü oxunuşu tam il üçün 12 ay verir (b.7: 12 ay işləyənə tam müddət).
        ->and(VacationNormEvaluator::fullMonths(365))->toBe(12);
});
