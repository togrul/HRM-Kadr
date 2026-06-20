<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditActivityBackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_copies_old_activity_rows_to_separate_audit_connection_idempotently(): void
    {
        $auditDatabase = tempnam(sys_get_temp_dir(), 'hrm-audit-backfill-');

        Config::set('database.connections.audit_testing', [
            'driver' => 'sqlite',
            'database' => $auditDatabase,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        Config::set('activitylog.database_connection', 'audit_testing');

        try {
            DB::table('activity_log')->insert([
                'id' => 10,
                'log_name' => 'auth',
                'description' => 'Legacy login',
                'subject_type' => null,
                'subject_id' => null,
                'causer_type' => null,
                'causer_id' => null,
                'properties' => json_encode(['ip' => '127.0.0.1']),
                'event' => 'login',
                'batch_uuid' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Artisan::call('audit:activity-migrate', ['--force' => true]);

            $this->artisan('audit:activity-backfill', [
                '--source-connection' => 'sqlite',
            ])->assertExitCode(0);

            $this->assertDatabaseHas('activity_log', [
                'id' => 10,
                'log_name' => 'auth',
                'description' => 'Legacy login',
                'event' => 'login',
            ], 'audit_testing');

            $this->artisan('audit:activity-backfill', [
                '--source-connection' => 'sqlite',
            ])->assertExitCode(0);

            $this->assertSame(1, DB::connection('audit_testing')->table('activity_log')->count());
        } finally {
            DB::purge('audit_testing');

            if (is_string($auditDatabase) && file_exists($auditDatabase)) {
                unlink($auditDatabase);
            }
        }
    }
}
