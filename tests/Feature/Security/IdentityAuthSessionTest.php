<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

require_once __DIR__.'/Support/identity_fixtures.php';

/*
 * Giriş, sessiya və şifrə qaydaları: açıq qeydiyyat yoxdur, deaktiv hesab daxil ola bilmir
 * və açıq sessiyası bağlanır, şifrə bərpası hesabın varlığını sızdırmır, IP üzrə limit var,
 * şifrə dəyişəndə digər sessiyalar etibarsız olur.
 */

it('has no public registration', function (): void {
    $this->get('/register')->assertNotFound();
    $this->post('/register', ['name' => 'X', 'email' => 'x@example.test', 'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!'])
        ->assertNotFound();

    expect(User::query()->where('email', 'x@example.test')->exists())->toBeFalse();
});

it('does not let a deactivated user log in', function (): void {
    $user = identityUser([], ['is_active' => false]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs a user out on the next request once deactivated', function (): void {
    $user = identityUser();

    $this->actingAs($user)->get('/profile')->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->actingAs($user->fresh())->get('/profile')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('does not send a reset link to a deactivated account and answers the same for unknown e-mails', function (): void {
    Notification::fake();
    $inactive = identityUser([], ['is_active' => false]);

    $known = $this->post('/forgot-password', ['email' => $inactive->email]);
    $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.test']);

    $known->assertSessionHasNoErrors()->assertSessionHas('status', __('passwords.sent_generic'));
    $unknown->assertSessionHasNoErrors()->assertSessionHas('status', __('passwords.sent_generic'));
    Notification::assertNothingSent();
});

it('refuses a password reset for a deactivated account even with a valid token', function (): void {
    $user = identityUser([], ['is_active' => false]);
    $token = Password::broker()->createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'New-Passw0rd-2026',
        'password_confirmation' => 'New-Passw0rd-2026',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('sends a reset link to an active account', function (): void {
    Notification::fake();
    $user = identityUser();

    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status', __('passwords.sent_generic'));

    Notification::assertSentTo($user, ResetPassword::class);
});

it('throttles failed login attempts per IP across different e-mails', function (): void {
    $user = identityUser();

    for ($i = 0; $i < 20; $i++) {
        $this->post('/login', ['email' => "spray{$i}@example.test", 'password' => 'wrong-password']);
    }

    // Düzgün şifrə ilə belə həmin IP-dən giriş müvəqqəti bağlanıb.
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('does not count successful logins toward the IP limit', function (): void {
    $user = identityUser();

    for ($i = 0; $i < 25; $i++) {
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/logout');
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
});

it('rejects weak passwords everywhere the policy applies', function (): void {
    $user = identityUser();

    $this->actingAs($user)
        ->from('/profile')
        ->put('/password', ['current_password' => 'password', 'password' => 'password123', 'password_confirmation' => 'password123'])
        ->assertSessionHasErrorsIn('updatePassword', 'password');
});

it('invalidates the user\'s other sessions when the password changes', function (): void {
    $user = identityUser();

    // Birinci "cihaz": sessiya şifrə heşini yadda saxlayır.
    $this->actingAs($user)->get('/profile')->assertOk();

    // Şifrə başqa yerdə (digər cihaz / admin) dəyişdirilir.
    $user->forceFill(['password' => Hash::make('Another-Passw0rd-1')])->save();

    $this->actingAs($user->fresh())->get('/profile')->assertRedirect(route('login'));
    $this->assertGuest();
});
