<?php

use App\Models\StaffSchedule;
use App\Models\User;
use App\Modules\Staff\Application\Services\StaffHeadcountService;
use App\Modules\Staff\Contracts\StaffingLookup;
use App\Modules\Staff\Contracts\StaffSlotCheck;
use App\Modules\Staff\Livewire\EditStaff;
use App\Modules\Staff\Livewire\Staffs;
use App\Services\Staff\StaffScheduleVacancyService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
 * Ştat cədvəli figures (audit 08.10.2026): Dolu must be the live headcount of each row's
 * exact (structure, position), vacancies and over-staffing must never net out, and a
 * position-less row must not count the whole unit a second time.
 */

beforeEach(function (): void {
    $this->travelTo('2026-10-08 10:00:00');

    DB::table('structures')->insert([
        ['id' => 1, 'name' => 'Şirkət', 'shortname' => 'ROOT', 'parent_id' => null, 'level' => 1],
        ['id' => 2, 'name' => 'Texniki nəzarət şöbəsi', 'shortname' => 'TN', 'parent_id' => 1, 'level' => 2],
        ['id' => 3, 'name' => 'Kadrlarla iş şöbəsi', 'shortname' => 'KD', 'parent_id' => 1, 'level' => 2],
    ]);
    DB::table('positions')->insert([
        ['id' => 1, 'name' => 'Mühəndis'],
        ['id' => 2, 'name' => 'Kadr mütəxəssisi'],
        ['id' => 3, 'name' => 'Anbardar'],
    ]);
});

function staffHeadcountPerson(int $structureId, ?int $positionId, array $overrides = []): void
{
    static $seq = 0;
    $seq++;

    DB::table('personnels')->insert([
        'tabel_no' => 'SH'.$seq,
        'surname' => 'Test',
        'name' => 'Person'.$seq,
        'patronymic' => 'Test',
        'has_changed_initials' => false,
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'mobile' => '0501234567',
        'nationality_id' => 1,
        'has_changed_nationality' => false,
        'pin' => 'PIN'.$seq,
        'residental_address' => 'Baku',
        'education_degree_id' => 1,
        'structure_id' => $structureId,
        'position_id' => $positionId,
        'work_norm_id' => 1,
        'join_work_date' => '2020-01-01',
        'added_by' => 1,
        'created_at' => now(),
        'updated_at' => now(),
        'is_pending' => false,
        ...$overrides,
    ]);
}

function staffHeadcountRow(int $structureId, ?int $positionId, int $total, int $storedFilled = 0): StaffSchedule
{
    return StaffSchedule::query()->create([
        'structure_id' => $structureId,
        'position_id' => $positionId,
        'total' => $total,
        'filled' => $storedFilled,
        'vacant' => max(0, $total - $storedFilled),
    ]);
}

function staffHeadcountViewer(): User
{
    $user = grantAllStructures(User::factory()->create());
    $user->givePermissionTo(Permission::findOrCreate('show-staff', 'web'));
    $role = Role::findOrCreate('staff-headcount-tester', 'web');
    $user->assignRole($role);
    DB::table('role_structures')->insert(array_map(
        fn (int $id): array => ['role_id' => $role->id, 'structure_id' => $id],
        [1, 2, 3],
    ));

    return $user;
}

/** @return array{summary: array<string, mixed>, tree: array<int, array<string, mixed>>} */
function staffHeadcountPage(): array
{
    $component = Livewire::actingAs(staffHeadcountViewer())->test(Staffs::class);

    return ['summary' => $component->viewData('staffSummary'), 'tree' => $component->viewData('staffTree'), 'component' => $component];
}

/** @param array<int, array<string, mixed>> $tree */
function staffHeadcountNode(array $tree, int $id): ?array
{
    foreach ($tree as $node) {
        if ((int) $node['id'] === $id) {
            return $node;
        }
        if ($found = staffHeadcountNode($node['children'], $id)) {
            return $found;
        }
    }

    return null;
}

it('does not count a unit twice through a position-less "—" row (production: Texniki 36/36 with 18 people)', function (): void {
    // 18 engineers on an 18-slot row, plus a "—" row of 18 that HR typed on a top-level
    // unit. The old code made the "—" row count all 18 people again: 36 / 36 / 0 vacant.
    staffHeadcountRow(2, 1, 18, storedFilled: 18);
    staffHeadcountRow(2, null, 18, storedFilled: 18);
    foreach (range(1, 18) as $ignored) {
        staffHeadcountPerson(2, 1);
    }

    ['summary' => $summary, 'tree' => $tree] = staffHeadcountPage();
    $node = staffHeadcountNode($tree, 2);

    expect($node['agg'])->toMatchArray(['total' => 36, 'filled' => 18, 'vacant' => 18, 'over' => 0, 'unassigned' => 1])
        ->and($summary)->toMatchArray(['total' => 36, 'filled' => 18, 'vacant' => 18, 'unassigned' => 1])
        ->and(collect($node['positions'])->firstWhere('kind', 'unassigned'))
        ->toMatchArray(['title' => __('staff::common.fields.position_unassigned'), 'filled' => 0, 'vacant' => 18]);
});

it('counts only active people: not dismissed, not deleted, not pending, already joined', function (): void {
    staffHeadcountRow(2, 1, 10);

    staffHeadcountPerson(2, 1);                                            // active
    staffHeadcountPerson(2, 1, ['leave_work_date' => '2026-12-31']);       // leaving later — still here
    staffHeadcountPerson(2, 1, ['leave_work_date' => '2026-10-08']);       // last day today — still here
    staffHeadcountPerson(2, 1, ['leave_work_date' => '2026-09-30']);       // dismissed
    staffHeadcountPerson(2, 1, ['deleted_at' => now()]);                    // soft-deleted
    staffHeadcountPerson(2, 1, ['is_pending' => true]);                    // awaiting approval
    staffHeadcountPerson(2, 1, ['join_work_date' => '2026-11-01']);        // starts next month
    staffHeadcountPerson(3, 1);                                            // same position, other unit

    expect(app(StaffHeadcountService::class)->activeCounts([2])[2][1])->toBe(3);

    $node = staffHeadcountNode(staffHeadcountPage()['tree'], 2);
    expect($node['agg'])->toMatchArray(['total' => 10, 'filled' => 3, 'vacant' => 7]);
});

it('ignores the stored filled counter', function (): void {
    staffHeadcountRow(2, 1, 5, storedFilled: 5);
    staffHeadcountPerson(2, 1);

    expect(staffHeadcountPage()['summary'])->toMatchArray(['total' => 5, 'filled' => 1, 'vacant' => 4]);
});

it('never lets over-staffing in one unit cancel a vacancy in another', function (): void {
    staffHeadcountRow(2, 1, 3);                 // 1 of 3 → 2 vacant
    staffHeadcountPerson(2, 1);
    staffHeadcountRow(3, 2, 1);                 // 3 of 1 → 2 over
    foreach (range(1, 3) as $ignored) {
        staffHeadcountPerson(3, 2);
    }

    ['summary' => $summary, 'tree' => $tree] = staffHeadcountPage();

    expect($summary)->toMatchArray(['total' => 4, 'filled' => 4, 'vacant' => 2, 'over' => 2])
        // Doluluq credits each row only up to its own total: (1 + 1) / 4.
        ->and($summary['rate'])->toBe(50.0)
        ->and(staffHeadcountNode($tree, 3)['positions'][0])->toMatchArray(['filled' => 3, 'vacant' => 0, 'over' => 2])
        ->and(staffHeadcountNode($tree, 1)['agg'])->toMatchArray(['total' => 4, 'filled' => 4, 'vacant' => 2, 'over' => 2]);
});

it('deals people out across duplicate rows of one pair without double counting', function (): void {
    $first = staffHeadcountRow(2, 1, 2);
    $second = staffHeadcountRow(2, 1, 2);
    foreach (range(1, 5) as $ignored) {
        staffHeadcountPerson(2, 1);
    }

    $rows = StaffSchedule::query()->orderBy('id')->get();
    $service = app(StaffHeadcountService::class);
    $service->hydrate($rows, $service->activeCounts([2]));

    expect($rows->pluck('filled')->all())->toBe([2, 3])
        ->and($rows->pluck('vacant')->all())->toBe([0, 0])
        ->and($rows->pluck('over')->all())->toBe([0, 1]);
});

it('shows people whose position has no ştat row as Ştatdankənar instead of hiding them in a row', function (): void {
    // Production: an "Anbardar" hired into Kadrlar, which has no such ştat row.
    staffHeadcountRow(3, 2, 2);
    staffHeadcountPerson(3, 2);
    staffHeadcountPerson(3, 2);
    staffHeadcountPerson(3, 3);

    ['summary' => $summary, 'tree' => $tree, 'component' => $component] = staffHeadcountPage();
    $node = staffHeadcountNode($tree, 3);

    expect($node['agg'])->toMatchArray(['total' => 2, 'filled' => 2, 'vacant' => 0, 'off_staff' => 1])
        ->and($node['off_staff'])->toHaveCount(1)
        ->and($node['off_staff'][0])->toMatchArray(['title' => 'Anbardar', 'filled' => 1, 'position_id' => 3])
        ->and($summary)->toMatchArray(['filled' => 2, 'off_staff' => 1]);

    $component
        ->call('expandAllNodes')
        ->assertSee(__('staff::common.fields.off_staff'))
        ->assertSee('Anbardar');
});

it('makes the side panel show filled/total from the same aggregate as the tree', function (): void {
    staffHeadcountRow(3, 2, 4);
    foreach (range(1, 3) as $ignored) {
        staffHeadcountPerson(3, 2);
    }

    $tree = staffHeadcountPage()['tree'];
    $html = Blade::render('<x-staff.panel-node :node="$node" :depth="0" :selected="null" />', ['node' => staffHeadcountNode($tree, 3)]);

    expect($html)->toContain('3/4');
});

it('renders the header with Artıq only when there is over-staffing', function (): void {
    staffHeadcountRow(2, 1, 1);
    staffHeadcountPerson(2, 1);

    staffHeadcountPage()['component']->assertDontSee(__('staff::common.fields.over'));

    staffHeadcountPerson(2, 1);
    Cache::flush(); // the page memoises its tree for a few seconds
    Livewire::actingAs(staffHeadcountViewer())->test(Staffs::class)
        ->assertSee(__('staff::common.fields.over'))
        ->call('expandAllNodes')
        ->assertSee(__('staff::common.fields.over_count', ['count' => 1]));
});

it('lets HR fix a position-less row in the edit form, which now requires the position', function (): void {
    $row = staffHeadcountRow(2, null, 18);
    $user = grantAllStructures(User::factory()->create());
    $user->givePermissionTo(Permission::findOrCreate('edit-staff', 'web'));

    Livewire::actingAs($user)->test(EditStaff::class, ['staffModel' => 2])
        ->call('store')
        ->assertHasErrors(['staff.0.position_id'])
        ->set('staff.0.position_id', 1)
        ->call('store')
        ->assertHasNoErrors();

    expect((int) $row->fresh()->position_id)->toBe(1);
});

it('checks a hire target against the ştat: room, full, or no such row', function (): void {
    staffHeadcountRow(2, 1, 2);
    staffHeadcountPerson(2, 1);
    $lookup = app(StaffingLookup::class);

    $room = $lookup->check(2, 1);
    expect($room->status)->toBe(StaffSlotCheck::AVAILABLE)
        ->and($room->hasWarning())->toBeFalse()
        ->and($room->vacant())->toBe(1);

    staffHeadcountPerson(2, 1);
    $full = $lookup->check(2, 1);
    expect($full->status)->toBe(StaffSlotCheck::FULL)
        ->and($full->message())->toBe(__('staff::common.slot.full', ['filled' => 2, 'total' => 2]))
        ->and($full->blocks())->toBeFalse();

    $missing = $lookup->check(3, 3);
    expect($missing->status)->toBe(StaffSlotCheck::MISSING)
        ->and($missing->message())->toBe(__('staff::common.slot.missing'));

    expect($lookup->check(null, 1)->hasWarning())->toBeFalse();

    config(['staff.hire_guard.block' => true]);
    expect($lookup->check(2, 1)->blocks())->toBeTrue()
        ->and($lookup->check(3, 3)->blocks())->toBeTrue();
});

it('judges the order vacancy gate from the live headcount, not the stored counter', function (): void {
    $row = staffHeadcountRow(2, 1, 2, storedFilled: 2);   // counter says full, nobody works there
    $vacancies = app(StaffScheduleVacancyService::class);

    expect($vacancies->vacancy(2, 1))->toBe(2);
    $vacancies->ensureOneVacancy(2, 1);
    expect((int) $row->fresh()->total)->toBe(2);

    foreach (range(1, 3) as $ignored) {
        staffHeadcountPerson(2, 1);                          // really over-full: 3 of 2
    }
    $vacancies->ensureOneVacancy(2, 1);
    expect((int) $row->fresh()->total)->toBe(4)
        ->and($vacancies->vacancy(2, 1))->toBe(1);
});

it('feeds the landing page fill block from the live headcount', function (): void {
    staffHeadcountRow(2, 1, 4, storedFilled: 4);
    staffHeadcountRow(2, null, 4, storedFilled: 4);
    staffHeadcountPerson(2, 1);

    expect(app(StaffingLookup::class)->structureFill())->toBe([2 => ['total' => 8, 'filled' => 1, 'vacant' => 7]]);
});

it('keeps the ştat page within its query budget', function (): void {
    staffHeadcountRow(2, 1, 2);
    staffHeadcountRow(3, 2, 2);
    staffHeadcountPerson(2, 1);
    staffHeadcountPerson(3, 3);
    $viewer = staffHeadcountViewer();

    DB::flushQueryLog();
    DB::enableQueryLog();
    Livewire::actingAs($viewer)->test(Staffs::class);
    $personnelQueries = collect(DB::getQueryLog())->filter(fn (array $q): bool => str_contains($q['query'], '"personnels"'));
    DB::disableQueryLog();

    expect($personnelQueries)->toHaveCount(1);
});

it('warns in the hire form when the chosen structure + position has no free ştat slot', function (): void {
    staffHeadcountRow(2, 1, 1);
    staffHeadcountPerson(2, 1);
    Role::findOrCreate('admin', 'web');
    Permission::findOrCreate('get-notification', 'web');
    $user = grantAllStructures(User::factory()->create());
    $user->givePermissionTo(Permission::findOrCreate('add-personnels', 'web'));

    $form = Livewire::actingAs($user)->test(\App\Modules\Personnel\Livewire\AddPersonnel::class)
        ->set('personalForm.personnel.structure_id', 2)
        ->set('personalForm.personnel.position_id', 1)
        ->assertSee(__('staff::common.slot.full', ['filled' => 1, 'total' => 1]))
        ->set('personalForm.personnel.structure_id', 3)
        ->assertSee(__('staff::common.slot.missing'));

    // A warning only, unless the install hard-blocks.
    $blocks = fn (): bool => (new ReflectionMethod($form->instance(), 'staffSlotBlocksSave'))->invoke($form->instance());
    expect($blocks())->toBeFalse();

    config(['staff.hire_guard.block' => true]);
    unset($form->instance()->staffSlotCheck);
    expect($blocks())->toBeTrue()
        ->and($form->instance()->getErrorBag()->first('personalForm.personnel.position_id'))->toBe(__('staff::common.slot.missing'));
});
