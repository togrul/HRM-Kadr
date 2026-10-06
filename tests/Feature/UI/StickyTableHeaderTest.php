<?php

use Illuminate\Support\Facades\Blade;

it('keeps the header row in view only for tables that ask for it', function (): void {
    $sticky = Blade::render('<x-table.tbl sticky :headers="[\'Ad\', \'Status\']"><tr><td>x</td></tr></x-table.tbl>');
    $plain = Blade::render('<x-table.tbl :headers="[\'Ad\', \'Status\']"><tr><td>x</td></tr></x-table.tbl>');

    expect($sticky)->toContain('sticky top-0')->toContain('overflow-y-auto')
        ->and($plain)->not->toContain('sticky top-0')->not->toContain('overflow-y-auto');
});
