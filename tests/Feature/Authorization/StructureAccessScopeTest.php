<?php

use App\Models\Structure;
use App\Models\User;
use App\Services\StructureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach ([1 => 'İR', 2 => 'Maliyyə', 3 => 'Hüquq'] as $id => $name) {
        Structure::query()->create(['id' => $id, 'name' => $name, 'shortname' => $name, 'code' => $id, 'level' => 1]);
    }
});

it('grants the structures of the user\'s roles', function (): void {
    $user = User::factory()->create();
    $hr = Role::findOrCreate('hr-scope', 'web');
    $finance = Role::findOrCreate('finance-scope', 'web');
    $user->assignRole($hr, $finance);

    DB::table('role_structures')->insert([
        ['role_id' => $hr->id, 'structure_id' => 1],
        ['role_id' => $finance->id, 'structure_id' => 1],
        ['role_id' => $finance->id, 'structure_id' => 2],
    ]);

    expect(collect(app(StructureService::class)->getAccessibleStructures($user))->sort()->values()->all())->toBe([1, 2]);
});

it('never grants the structures of a role that merely shares the user\'s id', function (): void {
    // The old relation joined role_structures.role_id to users.id, so user #N saw
    // whatever role #N was granted — regardless of the roles they actually hold.
    $user = User::factory()->create();
    DB::table('roles')->insertOrIgnore(['id' => $user->id, 'name' => 'unrelated-role', 'guard_name' => 'web']);
    DB::table('role_structures')->insert(['role_id' => $user->id, 'structure_id' => 3]);

    expect(app(StructureService::class)->getAccessibleStructures($user))->toBe([]);
});
