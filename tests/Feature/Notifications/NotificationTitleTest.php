<?php

use App\Modules\Notifications\Support\NotificationTitle;
use App\Modules\Notifications\Support\SamplePayloads;

it('strips every copy marker in either language and case', function (string $locale): void {
    app()->setLocale($locale);

    expect(NotificationTitle::normalize('Novruz (surət) (Copy)'))->toBe('Novruz')
        ->and(NotificationTitle::normalize('Novruz (SURƏT) bayramı (copy)'))->toBe('Novruz bayramı')
        ->and(NotificationTitle::normalize(null))->toBe('')
        ->and(NotificationTitle::copyCount('Novruz (surət)(surət) (copy)'))->toBe(3)
        ->and(NotificationTitle::copyCount('Novruz'))->toBe(0);
})->with(['az', 'en']);

it('only strips the trailing run when duplicating', function (): void {
    expect(NotificationTitle::normalize('A (copy) B (surət) (copy)', trailingOnly: true))->toBe('A (copy) B')
        ->and(NotificationTitle::normalize('A (copy) B', trailingOnly: true))->toBe('A (copy) B');
});

it('falls back to a generic sample payload for unknown categories', function (): void {
    expect(SamplePayloads::for('holiday'))->toHaveKey('holiday_name', 'Novruz bayramı')
        ->and(SamplePayloads::for('training_result'))->toBe(['name' => 'Nümunə istifadəçi', 'message' => 'Nümunə bildiriş mətni']);
});
