<?php

use Illuminate\Support\Facades\Blade;

it('folds a list toolbar behind a Filters button on phones only when asked', function (): void {
    $folded = Blade::render('<x-page-header title="Siyahı" collapsible-filters :filters-active="true"><input name="q"></x-page-header>');
    $plain = Blade::render('<x-page-header title="Siyahı"><nav>tabs</nav></x-page-header>');

    expect($folded)
        ->toContain(__('ui::common.labels.filters'))
        ->toContain(__('ui::common.labels.filters_active'))
        ->toContain('sm:!block')
        ->toContain('<input name="q">')
        ->and($plain)->not->toContain(__('ui::common.labels.filters_active'))
        ->toContain('<nav>tabs</nav>');
});
