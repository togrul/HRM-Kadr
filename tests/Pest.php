<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Struktur görünürlüyü fail closed-dur: struktur verilməmiş istifadəçi heç bir işçi qeydini
 * görmür. Bütün təşkilatı görməli olan test istifadəçisinə «bütün strukturlar» bayraqlı rol ver.
 */
function grantAllStructures(App\Models\User $user): App\Models\User
{
    $role = App\Models\Role::query()->firstOrCreate(
        ['name' => 'test-all-structures', 'guard_name' => 'web'],
    );
    $role->forceFill(['all_structures' => true])->save();
    $user->assignRole($role);
    app(App\Services\StructureService::class)->forgetUser((int) $user->getKey());

    return $user;
}

/**
 * İstifadəçiyə yalnız verilmiş strukturları açan ayrıca rol verir.
 *
 * @param  list<int>  $structureIds
 */
function grantStructures(App\Models\User $user, array $structureIds): App\Models\User
{
    $role = App\Models\Role::query()->create([
        'name' => 'test-structures-'.Illuminate\Support\Str::random(8),
        'guard_name' => 'web',
    ]);
    $role->structures()->sync($structureIds);
    $user->assignRole($role);
    app(App\Services\StructureService::class)->forgetUser((int) $user->getKey());

    return $user;
}
