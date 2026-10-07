<?php

use App\Support\DateInput;

it('accepts only complete real dates', function (mixed $input, ?string $expected): void {
    expect(DateInput::parse($input)?->toDateString())->toBe($expected);
})->with([
    ['2024-03-12', '2024-03-12'],
    ['12.03.2024', '2024-03-12'],
    [' 12.03.2024 ', '2024-03-12'],
    ['12.0', null],
    ['12.03.24', null],
    ['32.13.2024', null],
    ['2024-02-30', null],
    ['abc', null],
    ['', null],
    [null, null],
]);
