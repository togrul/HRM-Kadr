<?php

use App\Support\Language\AzerbaijaniPatronymic;

it('appends the suffix by gender', function (): void {
    expect(AzerbaijaniPatronymic::appendTo('Əliyev Rəşad Hikmət', 1))->toBe('Əliyev Rəşad Hikmət oğlu')
        ->and(AzerbaijaniPatronymic::appendTo('Əliyeva Aysel Hikmət', 2))->toBe('Əliyeva Aysel Hikmət qızı');
});

it('does not repeat a suffix the patronymic already carries', function (string $fullName): void {
    expect(AzerbaijaniPatronymic::appendTo($fullName, 1))->toBe($fullName);
})->with([
    'Əliyev Rəşad Hikmət oğlu',
    'Əliyev Rəşad Hikmət OĞLU',
    'Əliyev Rəşad Hikmət oglu',
    'Əliyeva Aysel Hikmət qızı',
    'Əliyeva Aysel Hikmət QIZI',
    'Əliyeva Aysel Hikmət qizi',
]);

it('strips the suffix from a stored patronymic but never empties it', function (): void {
    expect(AzerbaijaniPatronymic::strip('Hikmət oğlu'))->toBe('Hikmət')
        ->and(AzerbaijaniPatronymic::strip('  Hikmət   QIZI '))->toBe('Hikmət')
        ->and(AzerbaijaniPatronymic::strip('Hikmət'))->toBe('Hikmət')
        ->and(AzerbaijaniPatronymic::strip('Oğlu'))->toBe('Oğlu')
        ->and(AzerbaijaniPatronymic::strip('Oğuz'))->toBe('Oğuz');
});
