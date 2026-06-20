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
