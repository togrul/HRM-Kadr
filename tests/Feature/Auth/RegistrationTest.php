<?php

use App\Models\User;

/*
 * Açıq qeydiyyat ləğv olunub: hesabları yalnız admin yaradır.
 */
test('registration screen is not available', function () {
    $this->get('/register')->assertNotFound();
});

test('nobody can self-register an account', function () {
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Str0ng-Passw0rd!',
        'password_confirmation' => 'Str0ng-Passw0rd!',
    ])->assertStatus(404);

    $this->assertGuest();
    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});

test('login page has no registration link', function () {
    $this->get('/login')
        ->assertOk()
        ->assertDontSee('/register', false);
});
