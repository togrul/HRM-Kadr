@once
    <style>
        .module-side-nav {
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
            overflow: visible;
        }
        .module-rail-desktop {
            display: none;
        }
        .module-rail-fallback {
            display: grid;
        }
        .module-menu-link svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        .module-menu-link--inactive svg [stroke]:not([stroke='none']) {
            stroke: currentColor !important;
        }
        .module-menu-link--inactive svg [fill]:not([fill='none']) {
            fill: currentColor !important;
        }
        .module-menu-link--active svg [stroke]:not([stroke='none']) {
            stroke: currentColor !important;
        }
        .module-menu-link--active svg [fill]:not([fill='none']) {
            fill: currentColor !important;
        }
        @media (min-width: 1400px) {
            .module-rail-desktop {
                display: block;
            }
            .module-rail-fallback {
                display: none;
            }
        }
        .module-side-nav::-webkit-scrollbar {
            width: 6px;
        }
        .module-side-nav::-webkit-scrollbar-thumb {
            background: #d4d4d8;
            border-radius: 9999px;
        }
    </style>
@endonce

@php
    use App\Services\HrPolicies\HrPolicyPackService;
    use App\Support\Navigation\MenuPresentation;

    $policyPack = app(HrPolicyPackService::class);

    $preparedMenus = collect($menus)
        ->map(static function ($menuItem) {
        $routeBase = MenuPresentation::routeBase($menuItem);
        $canonicalKey = MenuPresentation::canonicalKey($menuItem) ?? $routeBase;

        return (object) [
            'item' => $menuItem,
            'canonicalKey' => $canonicalKey,
            'moduleName' => MenuPresentation::moduleName($routeBase),
            'permissionName' => MenuPresentation::permissionName($menuItem),
            'route' => MenuPresentation::route($routeBase),
            'routeBase' => $routeBase,
            'isActive' => request()->routeIs($routeBase) || request()->routeIs($routeBase . '.*'),
            'iconComponent' => MenuPresentation::iconComponent($menuItem),
            'label' => MenuPresentation::railLabel($menuItem),
            'visibleInRail' => MenuPresentation::visibleInRail($menuItem),
        ];
    })
        ->filter(static fn ($menu) => $menu->visibleInRail)
        ->filter(static fn ($menu) => $policyPack->menuVisible($menu->routeBase))
        ->filter(static fn ($menu) => MenuPresentation::hasRoute($menu->routeBase))
        ->unique('canonicalKey')
        ->values();
@endphp

<header class="relative z-20">
    <div class="mx-auto max-w-7xl px-4 lg:px-0 relative">
        <aside class="module-rail-desktop absolute left-0 top-4 -translate-x-[calc(100%+7px)]" aria-label="{{ __('ui::common.labels.module_navigation') }}">
            <nav class="module-side-nav w-24 rounded-[20px] border border-zinc-200 bg-zinc-50/70 p-1.5">
                <ul class="flex  flex-col gap-1.5 overflow-y-auto">
                    @foreach ($preparedMenus as $menu)
                        @module($menu->moduleName)
                        @can($menu->permissionName)
                            <li class="relative">
                                <a
                                    href="{{ $menu->route }}"
                                    wire:navigate
                                    title="{{ $menu->label }}"
                                    @class([
                                        'module-menu-link flex w-full flex-col items-center justify-center gap-1 rounded-2xl px-1 py-2 text-center transition-all duration-200',
                                        "module-menu-link--active bg-zinc-900 text-zinc-100 shadow-sm" => $menu->isActive,
                                        'module-menu-link--inactive bg-white text-zinc-600 hover:text-zinc-600 hover:bg-zinc-100/80' => ! $menu->isActive,
                                    ])
                                >
                                    <x-dynamic-component
                                        :component="$menu->iconComponent"
                                        :color="$menu->isActive ? 'text-zinc-100' : 'text-zinc-600'"
                                        size="w-6 h-6"
                                    />
                                    <span class="max-w-full whitespace-normal break-words text-[10px] font-medium leading-3 text-center">
                                        {{ $menu->label }}
                                    </span>
                                </a>
                            </li>
                        @endcan
                        @endmodule
                    @endforeach
                </ul>
            </nav>
        </aside>

        <div class="module-rail-fallback grid-cols-2 gap-3 py-2 sm:grid-cols-4 md:grid-cols-8">
            @foreach ($preparedMenus as $menu)
                @module($menu->moduleName)
                @can($menu->permissionName)
                    <a
                        href="{{ $menu->route }}"
                        wire:navigate
                        @class([
                            'module-menu-link flex items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm transition-all duration-200',
                            "module-menu-link--active bg-zinc-900 text-zinc-100" => $menu->isActive,
                            'module-menu-link--inactive bg-white text-zinc-600 hover:text-zinc-600' => ! $menu->isActive,
                        ])
                    >
                        <x-dynamic-component
                            :component="$menu->iconComponent"
                            :color="$menu->isActive ? 'text-zinc-100' : 'text-zinc-600'"
                            size="w-6 h-6"
                        />
                        <span class="truncate">{{ $menu->label }}</span>
                    </a>
                @endcan
                @endmodule
            @endforeach
        </div>
    </div>
</header>
