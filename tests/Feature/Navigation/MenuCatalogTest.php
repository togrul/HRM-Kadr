<?php

it('includes operational modules in the global menu catalog', function () {
    $urls = collect(config('menus.global'))->pluck('url')->all();

    expect($urls)->toContain('attendance');
    expect($urls)->toContain('training-needs');
    expect($urls)->toContain('performance-evaluation');
});
