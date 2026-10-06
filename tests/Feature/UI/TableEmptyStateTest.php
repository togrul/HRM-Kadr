<?php

use Illuminate\Support\Facades\Blade;

/** The empty table state names the next step: clear the filters, or create the first record. */
it('offers to clear filters when a filter emptied the list', function (): void {
    $html = Blade::render('<x-table.empty :rows="3" :filtered="true" />');

    expect($html)
        ->toContain(__('ui::common.empty.filtered_title'))
        ->toContain(__('ui::common.actions.reset_filters'))
        ->not->toContain('empty.png');
});

it('shows a custom title and the page create action when there is simply no data', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-table.empty :rows="3">
            Hələ vakansiya yoxdur
            <x-slot:action><button>Yeni vakansiya</button></x-slot:action>
        </x-table.empty>
        BLADE);

    expect($html)
        ->toContain('Hələ vakansiya yoxdur')
        ->toContain('Yeni vakansiya')
        ->not->toContain(__('ui::common.actions.reset_filters'));
});

it('falls back to the generic title and hides the reset for parent-owned filters', function (): void {
    expect(Blade::render('<x-table.empty :rows="3"><x-slot:action> </x-slot:action></x-table.empty>'))
        ->toContain(__('ui::common.empty.title'));

    expect(Blade::render('<x-table.empty :rows="3" :filtered="true" :resettable="false" />'))
        ->toContain(__('ui::common.empty.filtered_title'))
        ->not->toContain(__('ui::common.actions.reset_filters'));
});
