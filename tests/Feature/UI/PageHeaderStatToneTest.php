<?php

use Illuminate\Support\Facades\Blade;

/** Header stats use colour only for something to act on: warning / danger, and only above zero. */
it('colours a header stat only when it signals work', function (string $tone, string $value, string $expected): void {
    $html = Blade::render('<x-page-header.stat :value="$value" label="x" :tone="$tone" />', compact('tone', 'value'));

    expect($html)->toContain($expected);
})->with([
    'decorative green → ink' => ['green', '12', 'text-ink'],
    'decorative violet → ink' => ['violet', '3', 'text-ink'],
    'pending work → amber' => ['amber', '4', 'text-[#b45309]'],
    'nothing pending → ink' => ['amber', '0', 'text-ink'],
    'overdue → rose' => ['rose', '1 204', 'text-[#e11d48]'],
]);
