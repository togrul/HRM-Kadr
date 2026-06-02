<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('successful login is written to activity log', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($user);

    $this->assertDatabaseHas(config('activitylog.table_name'), [
        'log_name' => 'auth',
        'description' => 'User logged in',
        'event' => 'login',
        'causer_type' => User::class,
        'causer_id' => $user->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
    ], config('activitylog.database_connection'));
});

test('successful login can be written to a separate audit connection', function () {
    $auditDatabase = tempnam(sys_get_temp_dir(), 'hrm-audit-test-');

    config([
        'database.connections.audit_testing' => [
            'driver' => 'sqlite',
            'database' => $auditDatabase,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ],
        'activitylog.database_connection' => 'audit_testing',
    ]);

    try {
        Artisan::call('audit:activity-migrate', ['--force' => true]);

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas(config('activitylog.table_name'), [
            'log_name' => 'auth',
            'description' => 'User logged in',
            'event' => 'login',
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ], 'audit_testing');

        expect(DB::connection('audit_testing')->table(config('activitylog.table_name'))->count())->toBe(1);
    } finally {
        DB::purge('audit_testing');

        if (is_string($auditDatabase) && file_exists($auditDatabase)) {
            unlink($auditDatabase);
        }
    }
});
