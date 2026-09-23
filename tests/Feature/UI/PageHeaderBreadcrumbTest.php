<?php

use Illuminate\Support\Facades\Blade;

it('does not repeat a breadcrumb that equals its root', function (): void {
    $same = Blade::render('<x-page-header title="Şəxsi kabinet" breadcrumb="Şəxsi kabinet" breadcrumb-root="Şəxsi kabinet" />');
    $nested = Blade::render('<x-page-header title="Sorğular" breadcrumb="Sorğular" breadcrumb-root="Şəxsi kabinet" />');

    expect(substr_count($same, 'Şəxsi kabinet'))->toBe(2) // crumb root + the h1, no "/ Şəxsi kabinet"
        ->and($nested)->toContain('Şəxsi kabinet')->toContain('/</span> Sorğular');
});
