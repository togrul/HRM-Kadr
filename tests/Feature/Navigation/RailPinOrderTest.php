<?php

use App\Models\User;
use App\Support\Navigation\MenuPresentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function railMenus(string ...$routeBases): Illuminate\Support\Collection
{
    return collect($routeBases)->map(fn (string $routeBase): object => (object) ['routeBase' => $routeBase]);
}

it('pins the HR office modules for someone who can open the personnel list', function (): void {
    $hr = User::factory()->create();
    $hr->givePermissionTo(Permission::findOrCreate('show-personnels', 'web'));

    [$pinned, $other] = MenuPresentation::splitPinned(
        railMenus('training-needs', 'my-hr', 'leaves', 'orders', 'reports', 'personnel.index', 'staffs'),
        $hr,
    );

    expect($pinned->pluck('routeBase')->all())->toBe(['personnel.index', 'orders', 'staffs', 'leaves', 'training-needs'])
        ->and($other->pluck('routeBase')->all())->toBe(['my-hr', 'reports']);
});

it('pins self-service modules for an employee and fills spare slots in menu order', function (): void {
    [$pinned, $other] = MenuPresentation::splitPinned(
        railMenus('reports', 'vacations.list', 'my-hr', 'performance-evaluation'),
        User::factory()->create(),
    );

    expect($pinned->pluck('routeBase')->all())->toBe(['my-hr', 'vacations.list', 'reports', 'performance-evaluation'])
        ->and($other)->toBeEmpty();
});
