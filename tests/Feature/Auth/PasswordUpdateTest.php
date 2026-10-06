<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});

test('a user forced to reset the password can set a new one and continue', function () {
    \Spatie\Permission\Models\Role::findOrCreate(\App\Modules\Personnel\Application\Services\MyHr\MyHrAccountProvisioningService::EMPLOYEE_ROLE, 'web');
    $user = User::factory()->create(['must_reset_password' => true]);
    $user->assignRole(\App\Modules\Personnel\Application\Services\MyHr\MyHrAccountProvisioningService::EMPLOYEE_ROLE);

    $this->actingAs($user)->get('/')->assertRedirect(route('profile.edit', ['force_password_reset' => 1]));

    $this->actingAs($user)
        ->from(route('profile.edit', ['force_password_reset' => 1]))
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->must_reset_password)->toBeFalse()
        ->and(Hash::check('new-password', $user->password))->toBeTrue();

    $response = $this->actingAs($user)->get('/');
    expect((string) $response->headers->get('Location'))->not->toContain('force_password_reset');
});
