<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

it('deduplicates canonical menus and ignores unknown rail items', function () {
    $user = User::factory()->create();
    $permission = Permission::findOrCreate('show-staff', 'web');
    $user->givePermissionTo($permission);

    $this->actingAs($user);

    $html = view('includes.header', [
        'menus' => collect([
            (object) [
                'url' => 'staffs',
                'name' => 'Ştat cədvəli',
                'icon' => 'network-icon',
                'permission' => $permission,
            ],
            (object) [
                'url' => 'staffs.index',
                'name' => 'ui::menu.items.staff_table',
                'icon' => 'icons.network-icon',
                'permission' => $permission,
            ],
            (object) [
                'url' => 'service',
                'name' => 'Service',
                'icon' => 'document-icon',
                'permission' => $permission,
            ],
        ]),
    ])->render();

    expect(substr_count($html, 'href="' . route('staffs') . '"'))->toBe(2)
        ->and($html)->not->toContain('Service');
});
