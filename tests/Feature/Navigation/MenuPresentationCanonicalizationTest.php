<?php

use App\Support\Navigation\MenuPresentation;

it('normalizes legacy menu rows to canonical route and label', function () {
    $menu = (object) [
        'name' => 'Əmrlər',
        'url' => 'orders.index',
        'icon' => 'icons.line-order-icon',
    ];

    expect(MenuPresentation::canonicalKey($menu))->toBe('ui::menu.items.orders');
    expect(MenuPresentation::routeBase($menu))->toBe('orders');
    expect(MenuPresentation::railLabel($menu))->toBe(__('ui::menu.items.orders'));
    expect(MenuPresentation::visibleInRail($menu))->toBeTrue();
});

it('hides unknown menus from the module rail', function () {
    $menu = (object) [
        'name' => 'Legacy Unknown',
        'url' => 'legacy.unknown',
        'icon' => 'document-icon',
    ];

    expect(MenuPresentation::canonicalKey($menu))->toBeNull();
    expect(MenuPresentation::visibleInRail($menu))->toBeFalse();
});

it('gates a menu with the permission its config entry names, even when the row has none', function () {
    // A menu row created before its permission existed has permission_id = null; the
    // rail must still hide it from users the page itself would answer with a 403.
    $menu = (object) ['name' => 'ui::menu.items.compensation', 'url' => 'compensation', 'permission' => null];

    expect(MenuPresentation::permissionName($menu))->toBe('show-compensation');
    expect(MenuPresentation::permissionName((object) ['name' => 'ui::menu.items.payroll', 'url' => 'payroll', 'permission' => null]))
        ->toBe('show-payroll');
});
