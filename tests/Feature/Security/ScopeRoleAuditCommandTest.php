<?php

use App\Models\Role;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * `security:audit-role-structures` — yerləşdirmədən əvvəl/sonra admin üçün hesabat.
 */
it('reports roles, the flag, orphan rows and users who would see nothing', function (): void {
    Structure::query()->create(['id' => 7, 'name' => 'İR', 'shortname' => 'IR', 'code' => 7, 'level' => 1]);

    $everything = Role::query()->create(['name' => 'Hamısı', 'guard_name' => 'web']);
    $everything->forceFill(['all_structures' => true])->save();
    $limited = Role::query()->create(['name' => 'Məhdud', 'guard_name' => 'web']);
    $limited->structures()->sync([7]);
    $empty = Role::query()->create(['name' => 'Boş', 'guard_name' => 'web']);

    $seesAll = User::factory()->create(['name' => 'Hamısını görən']);
    $seesAll->assignRole($everything);
    $seesSome = User::factory()->create(['name' => 'Bir qismini görən']);
    $seesSome->assignRole($limited);
    $blind = User::factory()->create(['name' => 'Heç nə görməyən']);
    $blind->assignRole($empty);

    DB::statement('PRAGMA foreign_keys = OFF');
    DB::table('role_structures')->insert(['role_id' => 9999, 'structure_id' => 7]);
    DB::statement('PRAGMA foreign_keys = ON');

    Artisan::call('security:audit-role-structures', ['--json' => true]);
    $report = json_decode(Artisan::output(), true);

    $roles = collect($report['roles'])->keyBy('name');

    expect($report['all_structures_column'])->toBeTrue()
        ->and($roles['Hamısı'])->toMatchArray(['users' => 1, 'structures' => 0, 'all_structures' => true])
        ->and($roles['Məhdud'])->toMatchArray(['users' => 1, 'structures' => 1, 'all_structures' => false])
        ->and($roles['Boş'])->toMatchArray(['users' => 1, 'structures' => 0, 'all_structures' => false])
        ->and($report['orphan_role_structures'])->toBe([['role_id' => 9999, 'structure_id' => 7, 'reason' => 'rol yoxdur']])
        ->and(collect($report['users_seeing_nothing'])->pluck('id')->all())->toBe([$blind->id]);

    $this->artisan('security:audit-role-structures')->assertSuccessful();
});
