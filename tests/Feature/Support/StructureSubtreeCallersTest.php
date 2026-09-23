<?php

namespace Tests\Feature\Support;

use App\Modules\Attendance\Application\Services\AttendanceCalendarSyncService;
use App\Modules\Attendance\Application\Services\AttendancePunchProcessingPipelineService;
use App\Modules\Attendance\Application\Services\AttendanceStructureScopeReadService;
use App\Modules\Reports\Application\Services\ReportsStructureScopeService;
use App\Modules\Staff\Livewire\AddStaff;
use App\Modules\Staff\Livewire\Staffs;
use App\Services\Structures\StructureDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Every caller that turns "a unit" into "the unit plus everything below it" must agree:
 * root included, whole subtree, and an unknown id resolves to itself (not to nothing —
 * several callers treat an empty list as "no filter").
 */
class StructureSubtreeCallersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1 ─ 2 ─ 4      6
        //   └ 3 ─ 5
        foreach ([[1, null], [2, 1], [3, 1], [4, 2], [5, 3], [6, null]] as [$id, $parent]) {
            DB::table('structures')->insert(['id' => $id, 'name' => "U{$id}", 'shortname' => "U{$id}", 'parent_id' => $parent, 'code' => $id, 'level' => 1]);
        }
    }

    /**
     * @return array<string, callable(int): array<int,int>>
     */
    private function callers(): array
    {
        $private = fn (object $target, string $method) => fn (int $id): array => (new ReflectionMethod($target, $method))->invoke($target, $id);

        return [
            'deletion' => fn (int $id): array => app(StructureDeletionService::class)->descendantIds($id),
            'attendance scope' => fn (int $id): array => app(AttendanceStructureScopeReadService::class)->resolveIds($id),
            'reports scope' => fn (int $id): array => app(ReportsStructureScopeService::class)->resolveIds($id),
            'calendar sync' => $private(app(AttendanceCalendarSyncService::class), 'resolveStructureScopeIds'),
            'punch pipeline' => $private(app(AttendancePunchProcessingPipelineService::class), 'resolveStructureScopeIds'),
            'staff crud' => $private(new AddStaff, 'resolveStructureTreeIds'),
        ];
    }

    public function test_every_caller_resolves_the_same_subtree(): void
    {
        foreach ($this->callers() as $name => $resolve) {
            $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5], $resolve(1), $name);
            $this->assertEqualsCanonicalizing([3, 5], $resolve(3), $name);
            $this->assertEqualsCanonicalizing([5], $resolve(5), $name);
            $this->assertSame(1, $resolve(1)[0], "{$name}: the unit itself comes first");
            $this->assertSame([99], $resolve(99), "{$name}: an unknown unit resolves to itself");
        }
    }

    public function test_staff_filled_counts_cover_each_units_subtree(): void
    {
        $bindings = function (callable $run): array {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $run();
            $query = collect(DB::getQueryLog())->first(fn (array $q): bool => str_contains($q['query'], '"personnels"'));
            DB::disableQueryLog();

            return array_map('intval', $query['bindings'] ?? []);
        };

        $staffs = new Staffs;
        $rows = collect([(object) ['structure_id' => 1, 'position_id' => null, 'total' => 5, 'structure' => null]]);
        $staffIds = $bindings(fn () => (new ReflectionMethod($staffs, 'hydrateFilledAndVacant'))->invoke($staffs, $rows));

        $crud = new AddStaff;
        $crud->staff = [['structure_id' => 3, 'position_id' => null, 'total' => 1]];
        $crudIds = $bindings(fn () => (new ReflectionMethod($crud, 'syncComputedStaffRows'))->invoke($crud));

        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5], array_values(array_diff($staffIds, [0])));
        $this->assertEqualsCanonicalizing([3, 5], array_values(array_diff($crudIds, [0])));
    }
}
