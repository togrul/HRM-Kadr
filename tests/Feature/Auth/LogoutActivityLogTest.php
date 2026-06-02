<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('successful logout is written to activity log', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();

    $this->assertDatabaseHas(config('activitylog.table_name'), [
        'log_name' => 'auth',
        'description' => 'User logged out',
        'event' => 'logout',
        'causer_type' => User::class,
        'causer_id' => $user->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
    ], config('activitylog.database_connection'));
});
