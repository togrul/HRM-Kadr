<?php

namespace Tests\Unit\Modules\Compensation;

use App\Modules\Compensation\Application\Services\EffectiveDating;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EffectiveDatingTest extends TestCase
{
    public function test_day_before_does_not_mutate_the_given_date(): void
    {
        $date = Carbon::parse('2026-03-01');

        $this->assertSame('2026-02-28', EffectiveDating::dayBefore($date)->toDateString());
        $this->assertSame('2026-03-01', $date->toDateString());
    }

    /**
     * @return array<string, array{string, ?string, string, ?string, bool}>
     */
    public static function windowProvider(): array
    {
        return [
            'closed windows overlap' => ['2026-01-01', '2026-06-30', '2026-06-01', '2026-12-31', true],
            'closed windows are disjoint' => ['2026-01-01', '2026-05-31', '2026-06-01', '2026-12-31', false],
            'closed windows touch on a single day' => ['2026-01-01', '2026-06-01', '2026-06-01', '2026-12-31', true],
            'open window swallows a later one' => ['2026-01-01', null, '2030-01-01', '2030-12-31', true],
            'open window starts after the other ends' => ['2026-07-01', null, '2026-01-01', '2026-06-30', false],
            'both open always overlap' => ['2026-01-01', null, '2099-01-01', null, true],
            'open second window reaches back' => ['2026-01-01', '2026-06-30', '2026-01-01', null, true],
        ];
    }

    #[DataProvider('windowProvider')]
    public function test_overlaps_handles_open_ended_windows(
        string $aFrom,
        ?string $aTo,
        string $bFrom,
        ?string $bTo,
        bool $expected,
    ): void {
        $this->assertSame($expected, EffectiveDating::overlaps(
            Carbon::parse($aFrom),
            $aTo === null ? null : Carbon::parse($aTo),
            Carbon::parse($bFrom),
            $bTo === null ? null : Carbon::parse($bTo),
        ));

        $this->assertSame($expected, EffectiveDating::overlaps(
            Carbon::parse($bFrom),
            $bTo === null ? null : Carbon::parse($bTo),
            Carbon::parse($aFrom),
            $aTo === null ? null : Carbon::parse($aTo),
        ), 'overlap must be symmetric');
    }
}
