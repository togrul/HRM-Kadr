<?php

use App\Services\WordSuffixService;

it('returns an empty suffix instead of crashing on missing text', function (): void {
    $service = new WordSuffixService;

    expect($service->educationSuffix(null))->toBe('')
        ->and($service->educationSuffix(''))->toBe('')
        ->and($service->getMilitarySuffix(null))->toBe('');
});

it('never throws an unhandled match for consonant-ending institutions', function (): void {
    $service = new WordSuffixService;

    expect($service->educationSuffix('Bakı Dövlət Universiteti'))->not->toBe('')
        ->and($service->educationSuffix('BDU'))->not->toBe('');
});
