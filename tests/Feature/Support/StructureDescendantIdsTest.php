<?php

namespace Tests\Feature\Support;

use App\Models\Structure;
use App\Services\StructurePathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StructureDescendantIdsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_walks_the_whole_subtree_from_one_read(): void
    {
        // 1 ─ 2 ─ 4
        //   └ 3 ─ 5
        foreach ([[1, null], [2, 1], [3, 1], [4, 2], [5, 3]] as [$id, $parent]) {
            DB::table('structures')->insert(['id' => $id, 'name' => "U{$id}", 'shortname' => "U{$id}", 'parent_id' => $parent, 'code' => $id, 'level' => 1]);
        }

        $paths = app(StructurePathService::class);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5], $paths->descendantIds(1));
        $this->assertEqualsCanonicalizing([3, 5], $paths->descendantIds(3));
        $this->assertSame([], $paths->descendantIds(99));
        // Only descends through the accessible units; the unit itself always counts.
        $this->assertEqualsCanonicalizing([1, 2, 4], $paths->descendantIds(1, [2, 4, 5]));
        $this->assertSame(1, $queries);
    }

    public function test_a_unit_saved_mid_request_is_seen_by_the_next_lookup(): void
    {
        DB::table('structures')->insert(['id' => 1, 'name' => 'U1', 'shortname' => 'U1', 'parent_id' => null, 'code' => 1, 'level' => 1]);
        $paths = app(StructurePathService::class);
        $this->assertSame([1], $paths->descendantIds(1));

        Structure::create(['id' => 2, 'name' => 'U2', 'shortname' => 'U2', 'parent_id' => 1, 'code' => 2, 'level' => 2]);

        $this->assertEqualsCanonicalizing([1, 2], $paths->descendantIds(1));
    }
}
