<?php

namespace Tests\Feature\Support;

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
}
