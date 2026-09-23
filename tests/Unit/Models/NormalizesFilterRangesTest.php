<?php

use App\Models\Concerns\NormalizesFilterRanges;
use Carbon\Carbon;

function filterRanges(): object
{
    return new class
    {
        use NormalizesFilterRanges;

        public function __call(string $method, array $arguments): mixed
        {
            return $this->{$method}(...$arguments);
        }
    };
}

afterEach(fn () => Carbon::setTestNow());

it('treats null, blank strings and all-blank arrays as empty, booleans never', function (mixed $value, bool $empty): void {
    expect(filterRanges()->filterValueIsEmpty($value))->toBe($empty);
})->with([
    'null' => [null, true],
    'blank string' => ['  ', true],
    'nested blanks' => [['min' => '', 'max' => [null, ' ']], true],
    'false' => [false, false],
    'zero' => [0, false],
    'zero string' => ['0', false],
    'one filled item' => [['min' => '', 'max' => '2024-01-01'], false],
]);

it('fills blank bounds with the defaults and formats dates', function (): void {
    Carbon::setTestNow('2026-09-23 10:00:00');

    expect(filterRanges()->normalizeDateRange([]))->toBe(['1990-01-01', '2026-09-23'])
        ->and(filterRanges()->normalizeDateRange(['min' => ' ', 'max' => null], '2000-01-01', '2001-01-01'))->toBe(['2000-01-01', '2001-01-01'])
        ->and(filterRanges()->normalizeDateRange(['min' => '05.03.2024', 'max' => '2024-06-01 13:00']))->toBe(['2024-03-05', '2024-06-01']);
});

it('swaps a range given backwards', function (): void {
    expect(filterRanges()->normalizeDateRange(['min' => '2024-06-01', 'max' => '2024-01-01']))->toBe(['2024-01-01', '2024-06-01']);
});
