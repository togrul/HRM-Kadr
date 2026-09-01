<?php

namespace Tests\Feature\Support;

use App\Support\Database\InstalledTables;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InstalledTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_answers_existence_for_installed_and_missing_tables(): void
    {
        $this->assertTrue(InstalledTables::has('personnels'));
        $this->assertTrue(InstalledTables::has('order_logs'));
        $this->assertFalse(InstalledTables::has('definitely_not_a_table'));
    }

    public function test_it_reads_the_listing_once_per_request(): void
    {
        // The whole point of the guard: a dozen probes on a render path must not be a
        // dozen round trips.
        InstalledTables::has('personnels');

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        foreach (['personnels', 'order_logs', 'personnel_vacations', 'nope'] as $table) {
            InstalledTables::has($table);
        }

        $this->assertSame(0, $queries);
    }

    public function test_it_is_shared_across_callers(): void
    {
        $this->assertSame(app(InstalledTables::class), app(InstalledTables::class));
    }
}
