<?php

use App\Models\Structure;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    DB::table('structures')->insert([
        ['id' => 1, 'name' => 'Ministry', 'shortname' => 'MIN', 'parent_id' => null, 'coefficient' => 1, 'code' => 0, 'level' => 0],
        ['id' => 2, 'name' => 'Department', 'shortname' => 'D', 'parent_id' => 1, 'coefficient' => 1, 'code' => 1, 'level' => 1],
        ['id' => 3, 'name' => 'Section', 'shortname' => 'S', 'parent_id' => 2, 'coefficient' => 1, 'code' => 2, 'level' => 2],
    ]);
});

it('labels a unit from the flat chart map with the same strings as the parent walk', function (): void {
    $section = Structure::query()->find(3);
    $root = Structure::query()->find(1);

    expect($section->fullStructureName())->toBe('Department / Section')
        ->and($section->fullStructureName(includeRoot: true))->toBe('Ministry / Department / Section')
        ->and($section->name_with_parent)->toBe('Department / Section')
        ->and($root->name_with_parent)->toBe('Ministry')
        ->and($root->fullStructureName())->toBe('')
        ->and($section->fullStructurePath())->toBe('Ministry / Department / Section')
        ->and($section->fullStructurePath(rootAsShortname: true))->toBe('MIN / Department / Section')
        ->and($section->fullStructurePath(includeRoot: false))->toBe('Department / Section');
});

it('does not query per org level', function (): void {
    $units = Structure::query()->get();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $units->each(fn (Structure $unit) => $unit->fullStructureName(includeRoot: true));

    expect(DB::getQueryLog())->toHaveCount(1);
});
