<?php

namespace Tests\Unit\Support;

use App\Support\Language\AzerbaijaniDateFormatter;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AzerbaijaniDateFormatterTest extends TestCase
{
    private AzerbaijaniDateFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new AzerbaijaniDateFormatter;
    }

    /**
     * @dataProvider yearSuffixCases
     */
    public function test_year_suffix(int $year, string $expected): void
    {
        $this->assertSame($expected, $this->formatter->yearSuffix($year));
    }

    public static function yearSuffixCases(): array
    {
        return [
            [2021, 'ci'], [2022, 'ci'], [2023, 'cü'], [2024, 'cü'], [2025, 'ci'],
            [2026, 'cı'], [2027, 'ci'], [2028, 'ci'], [2029, 'cu'],
            [2020, 'ci'], [2030, 'cu'], [2040, 'cı'],
        ];
    }

    public function test_long_date(): void
    {
        $this->assertSame('26.11.2025-ci il', $this->formatter->longDate(Carbon::parse('2025-11-26')));
        $this->assertSame('06.06.2026-cı il', $this->formatter->longDate(Carbon::parse('2026-06-06')));
    }

    public function test_work_year_span_ends_the_day_before_the_anniversary(): void
    {
        // The reported bug: a labour year must not read 26.11.2025-26.11.2026 (same
        // day); the end is one day before the next anniversary.
        $this->assertSame(
            '26.11.2025-25.11.2026-cı il',
            $this->formatter->workYearSpan(Carbon::parse('2025-11-26'))
        );
        $this->assertSame(
            '01.01.2025-31.12.2025-ci il',
            $this->formatter->workYearSpan(Carbon::parse('2025-01-01'))
        );
    }
}
