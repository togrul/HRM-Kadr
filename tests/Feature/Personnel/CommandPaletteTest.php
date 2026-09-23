<?php

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Leaves\Livewire\Leaves;
use App\Modules\Orders\Livewire\AllOrders;
use App\Modules\Personnel\Livewire\AllPersonnel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * @param  list<string>  $permissions
 */
function paletteUser(array $permissions, array $structureIds = [1]): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user->givePermissionTo($permissions);

    $user->assignRole($role = \Spatie\Permission\Models\Role::findOrCreate('structure-scope', 'web'));

    foreach ($structureIds as $structureId) {
        DB::table('role_structures')->insert(['role_id' => $role->id, 'structure_id' => $structureId]);
    }

    return $user;
}

function palettePerson(string $tabel, string $surname, string $name, int $structureId = 1, array $overrides = []): Personnel
{
    return Personnel::withoutEvents(fn () => Personnel::factory()->create(array_merge([
        'tabel_no' => $tabel,
        'surname' => $surname,
        'name' => $name,
        'patronymic' => 'Rasim',
        'pin' => 'PIN'.$tabel,
        'structure_id' => $structureId,
        'position_id' => 1,
        'is_pending' => false,
        'leave_work_date' => null,
        'birthdate' => '1986-07-14',
        'mobile' => '0500000001',
        'nationality_id' => 1,
        'residental_address' => 'Baku',
        'education_degree_id' => 1,
        'join_work_date' => '2019-03-12',
        'added_by' => 1,
        'work_norm_id' => 1,
    ], $overrides)));
}

beforeEach(function (): void {
    DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
    DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr']);
    DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam ştat']);
    Structure::query()->create(['id' => 1, 'name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
    Structure::query()->create(['id' => 2, 'name' => 'Maliyyə', 'shortname' => 'MF', 'code' => 2, 'level' => 1]);
    Position::query()->create(['id' => 1, 'name' => 'Məsləhətçi']);
});

it('refuses the people search without the personnel view permission', function (): void {
    $this->actingAs(paletteUser([]));

    $this->getJson(route('personnel.palette-search', ['q' => 'Məm']))->assertForbidden();
});

it('finds people by several words, tabel number or FİN, within the user\'s structures only', function (): void {
    $this->actingAs(paletteUser(['show-personnels']));

    palettePerson('P-100', 'Məmmədov', 'Elçin');
    palettePerson('P-200', 'Məmmədov', 'Anar');
    palettePerson('P-300', 'Məmmədov', 'Elçin', structureId: 2);

    $names = fn (string $q) => collect($this->getJson(route('personnel.palette-search', ['q' => $q]))->assertOk()->json('results'))->pluck('tabel_no')->all();

    expect($names('Məmmədov Elçin'))->toBe(['P-100'])
        ->and($names('elçin məmmədov'))->toBe(['P-100'])
        ->and($names('P-200'))->toBe(['P-200'])
        ->and($names('PINP-100'))->toBe(['P-100'])
        ->and($names('M'))->toBe([]);
});

it('lists current employees before those who left and links to the file', function (): void {
    $this->actingAs(paletteUser(['show-personnels']));

    palettePerson('P-1', 'Əliyev', 'Anar', overrides: ['leave_work_date' => '2024-01-01']);
    $current = palettePerson('P-2', 'Əliyev', 'Bəxtiyar');

    $results = $this->getJson(route('personnel.palette-search', ['q' => 'Əliyev']))->json('results');

    expect(collect($results)->pluck('tabel_no')->all())->toBe(['P-2', 'P-1'])
        ->and($results[0]['url'])->toBe(route('personnel.show', $current->id))
        ->and($results[0]['position'])->toBe('Məsləhətçi')
        ->and($results[1]['left'])->toBeTrue();
});

it('opens the create form when a page is deep-linked with ?create=1', function (string $component, array $permissions, string $menu): void {
    $this->actingAs(paletteUser($permissions));

    Livewire::withQueryParams(['create' => 1])
        ->test($component)
        ->assertSet('showSideMenu', $menu);
})->with([
    'personnel' => [AllPersonnel::class, ['show-personnels', 'add-personnels'], 'add-personnel'],
    'orders' => [AllOrders::class, ['show-orders', 'add-orders'], 'order-composer'],
    'leaves' => [Leaves::class, ['show-leaves', 'add-leaves'], 'add-leave'],
]);

it('ignores ?create=1 for a user who may not create', function (string $component, array $permissions): void {
    $this->actingAs(paletteUser($permissions));

    Livewire::withQueryParams(['create' => 1])
        ->test($component)
        ->assertSet('showSideMenu', '');
})->with([
    'personnel' => [AllPersonnel::class, ['show-personnels']],
    'orders' => [AllOrders::class, ['show-orders']],
    'leaves' => [Leaves::class, ['show-leaves']],
]);
