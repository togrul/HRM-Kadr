<?php

use App\Modules\Personnel\Application\Services\PersonnelProfileReadService;

it('groups local and international mobile numbers', function (?string $raw, ?string $expected): void {
    expect(app(PersonnelProfileReadService::class)->phone($raw))->toBe($expected);
})->with([
    ['0500000000', '050 000 00 00'],
    ['(055) 123-45-67', '055 123 45 67'],
    ['+994501234567', '+994 50 123 45 67'],
    ['12345', '12345'],
    ['', null],
    [null, null],
]);
