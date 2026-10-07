<?php

use App\Services\CalculateSeniorityService;
use Illuminate\Support\Facades\Blade;

it('gives no seniority figure for an unfinished date instead of throwing', function (): void {
    expect(app(CalculateSeniorityService::class)->calculate('32.13.2024', '2026-01-01', null)['diff'])->toBe(0)
        ->and(app(CalculateSeniorityService::class)->calculateEducation(['admission_year' => '01.09.2018', 'coefficient' => null])['year'])->toBeGreaterThan(0);
});

it('syncs a date field on change, never per keystroke', function (): void {
    $html = Blade::render('<x-pikaday-input name="d" wire:model.live="form.date" />');

    expect($html)->toContain('wire:model.change="form.date"')
        ->not->toContain('wire:model.live');
});
