@once
    <style>
        /*
         * Rail popovers (notification bell) must open to the RIGHT of the 60px rail.
         * Matched by an explicit marker, not by "any absolutely positioned child with
         * right-0" — that also caught the unread-count badge on the bell and flung it
         * outside the rail.
         */
        .hrm-rail-popover [data-rail-panel] {
            right: auto !important;
            left: calc(100% + 12px) !important;
            top: auto !important;
            bottom: 0 !important;
            margin-top: 0 !important;
            transform-origin: bottom left !important;
        }
    </style>

    <script>
        /**
         * Rail hover labels.
         *
         * The rail's module list scrolls, and a scroll container clips both axes, so an
         * absolutely positioned label per icon would be cut off at the 60px edge. Instead:
         * ONE reusable node on <body> and ONE delegated listener on document — constant
         * cost no matter how many modules the user can see, and nothing at all is allocated
         * until the first hover.
         *
         * document survives wire:navigate, so this registers exactly once per page load.
         */
        (() => {
            if (window.__hrmRailTip) {
                return;
            }

            // Touch and coarse pointers never hover; don't attach anything for them.
            if (! window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
                return;
            }

            window.__hrmRailTip = true;

            // The rail is 78px wide against the left edge. Comparing one number lets every
            // pointer event elsewhere in the app bail out before any DOM tree walk.
            const RAIL_EDGE = 96;

            let tip = null;
            let shown = false;
            let timer = 0;

            const surface = () => {
                // wire:navigate swaps <body>, taking the node with it; re-create on demand.
                if (! tip || ! tip.isConnected) {
                    tip = document.createElement('div');
                    tip.id = 'hrm-rail-tip';
                    tip.setAttribute('role', 'tooltip');
                    tip.setAttribute('aria-hidden', 'true');
                    document.body.appendChild(tip);
                }

                return tip;
            };

            const hide = () => {
                if (timer) {
                    clearTimeout(timer);
                    timer = 0;
                }

                if (! shown) {
                    return;
                }

                shown = false;
                tip.removeAttribute('data-show');
            };

            const place = (link) => {
                const node = surface();
                const box = link.getBoundingClientRect();

                node.textContent = link.dataset.railTip;
                // transform only: no top/left writes, so this never dirties layout.
                node.style.transform = 'translate3d('
                    + Math.round(box.right + 10) + 'px,'
                    + Math.round(box.top + box.height / 2) + 'px,0) translateY(-50%)';
                node.setAttribute('data-show', '');
                shown = true;
            };

            const show = (link) => {
                if (timer) {
                    clearTimeout(timer);
                    timer = 0;
                }

                // Already open: follow the pointer down the rail with no re-delay.
                if (shown) {
                    place(link);

                    return;
                }

                timer = setTimeout(() => {
                    timer = 0;
                    place(link);
                }, 70);
            };

            const resolve = (target) => target?.closest?.('[data-rail-tip]') ?? null;

            document.addEventListener('pointerover', (event) => {
                if (event.clientX > RAIL_EDGE) {
                    hide();

                    return;
                }

                const link = resolve(event.target);

                link ? show(link) : hide();
            }, { passive: true });

            document.addEventListener('focusin', (event) => {
                const link = resolve(event.target);

                link ? place(link) : hide();
            }, { passive: true });

            document.addEventListener('pointerdown', hide, { passive: true });
            document.addEventListener('focusout', hide, { passive: true });
            document.addEventListener('livewire:navigating', hide);
            document.documentElement.addEventListener('pointerleave', hide, { passive: true });
            window.addEventListener('blur', hide);
            // The label is fixed, so any scroll — the rail's own included — would leave it behind.
            window.addEventListener('scroll', hide, { passive: true, capture: true });
        })();
    </script>
@endonce

@php
    use App\Services\HrPolicies\HrPolicyPackService;
    use App\Services\Modules\ModuleState;
    use App\Support\Navigation\MenuPresentation;

    $policyPack = app(HrPolicyPackService::class);
    $moduleState = app(ModuleState::class);

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
                'shortLabel' => MenuPresentation::railShortLabel($menuItem),
                'visibleInRail' => MenuPresentation::visibleInRail($menuItem),
            ];
        })
        ->filter(static fn ($menu) => $menu->visibleInRail)
        ->filter(static fn ($menu) => $policyPack->menuVisible($menu->routeBase))
        ->filter(static fn ($menu) => MenuPresentation::hasRoute($menu->routeBase))
        ->unique('canonicalKey')
        ->values();

    // The palette is a plain list with no @module/@can wrappers, so gate it in PHP —
    // otherwise it would leak module names the user is not allowed to see.
    $paletteMenus = $preparedMenus
        ->filter(static fn ($menu) => $moduleState->enabled($menu->moduleName))
        ->filter(static fn ($menu) => $menu->permissionName === null || auth()->user()?->can($menu->permissionName))
        ->values();

    // Palette commands: the everyday "create" jobs, each deep-linking to a page that opens
    // its form on arrival. Gated here for the same reason as $paletteMenus.
    $paletteActions = collect([
        ['module' => 'personnel', 'route' => 'personnel.index', 'can' => 'add-personnels', 'label' => __('ui::common.palette.actions.new_personnel'), 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M19 8v6M22 11h-6'],
        ['module' => 'orders', 'route' => 'orders', 'can' => 'add-orders', 'label' => __('ui::common.palette.actions.new_order'), 'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M12 18v-6M9 15h6'],
        ['module' => 'leaves', 'route' => 'leaves', 'can' => 'add-leaves', 'label' => __('ui::common.palette.actions.new_leave'), 'icon' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2M12 14v4M10 16h4'],
    ])
        ->filter(static fn (array $action): bool => $moduleState->enabled($action['module'])
            && \Illuminate\Support\Facades\Route::has($action['route'])
            && (auth()->user()?->can($action['can']) ?? false))
        ->map(static fn (array $action): array => [
            'label' => $action['label'],
            'icon' => $action['icon'],
            'url' => route($action['route'], ['create' => 1]),
        ])
        ->values();

    $paletteCanSearchPeople = $moduleState->enabled('personnel') && (auth()->user()?->can('show-personnels') ?? false);

    // Pins come from what the user may actually open (taking the first five before the
    // permission check left gaps), ordered for their working role.
    [$pinnedMenus, $otherMenus] = MenuPresentation::splitPinned($paletteMenus, auth()->user());
@endphp

{{-- mobile scrim --}}
<div
    x-cloak
    x-show="$store.hrmShell.railOpen"
    x-transition.opacity
    @click="$store.hrmShell.railOpen = false"
    class="fixed inset-0 z-30 bg-zinc-900/30 backdrop-blur-[2px] lg:hidden"
    aria-hidden="true"
></div>

<aside
    id="hrm-rail"
    x-cloak
    :style="$store.hrmShell.railOpen ? 'transform: translateX(0)' : ''"
    class="fixed inset-y-0 left-0 z-40 flex h-screen w-[280px] shrink-0 -translate-x-full flex-col items-stretch overflow-visible border-r border-hairline bg-white px-2 py-3 transition-transform duration-200 lg:sticky lg:top-0 lg:bottom-auto lg:w-rail lg:translate-x-0 lg:items-center lg:px-0"
    aria-label="{{ __('ui::common.labels.module_navigation') }}"
>
    <div class="mb-2 flex items-center justify-between px-1 lg:mb-1 lg:px-0">
        <a href="{{ route('home') }}" wire:navigate data-rail-tip="{{ config('app.name') }}" class="flex items-center gap-2">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-ink text-[12px] font-bold tracking-tight text-white">HR</span>
            <span class="text-[14px] font-semibold text-ink lg:hidden">{{ __('ui::common.labels.modules') }}</span>
        </a>
        <button type="button" @click="$store.hrmShell.railOpen = false" class="flex h-11 w-11 items-center justify-center rounded-xl text-ink-muted hover:bg-[#f4f4f5] lg:hidden" aria-label="{{ __('ui::common.labels.collapse_panel') }}">
            <x-icons.close-icon size="w-5 h-5" color="text-current" hover="text-current" />
        </button>
    </div>

    {{-- command palette trigger --}}
    <button
        type="button"
        @click="$store.hrmShell.openPalette()"
        data-rail-tip="{{ __('ui::common.labels.search') }}"
        class="flex h-11 w-full shrink-0 items-center gap-3 rounded-[10px] border border-hairline bg-[#fafafa] px-3 text-[14px] text-ink-muted transition hover:bg-white hover:text-ink lg:h-[34px] lg:w-10 lg:justify-center lg:px-0"
    >
        <x-icons.search-file size="w-[18px] h-[18px]" color="text-current" hover="text-current" />
        <span class="lg:sr-only">{{ __('ui::common.labels.search') }}</span>
    </button>

    <span class="my-1 h-px w-full shrink-0 bg-hairline lg:w-7"></span>

    {{-- only the module list scrolls, so the bell and the avatar never leave the viewport --}}
    <div class="hrm-scroll flex w-full min-h-0 flex-1 flex-col items-stretch gap-1.5 overflow-y-auto overflow-x-hidden lg:items-center">
    {{-- pinned modules --}}
    <nav class="flex w-full flex-col items-stretch gap-1 lg:items-center">
        @foreach ($pinnedMenus as $menu)
            @module($menu->moduleName)
                @can($menu->permissionName)
                    <a
                        href="{{ $menu->route }}"
                        wire:navigate
                        data-rail-tip="{{ $menu->label }}"
                        @click="$store.hrmShell.railOpen = false"
                        @class([
                            'hrm-rail-item hrm-rail-link',
                            'hrm-rail-link--active bg-ink text-[#fafafa]' => $menu->isActive,
                            'text-ink-muted hover:bg-[#fafafa] hover:text-ink' => ! $menu->isActive,
                        ])
                        @if ($menu->isActive) aria-current="page" @endif
                    >
                        <x-dynamic-component :component="$menu->iconComponent" color="text-current" hover="text-current" size="w-[18px] h-[18px]" />
                        <span class="hrm-rail-label lg:hidden">{{ $menu->label }}</span>
                        <span class="hrm-rail-label hidden lg:block">{{ $menu->shortLabel }}</span>
                    </a>
                @endcan
            @endmodule
        @endforeach
    </nav>

    @if ($otherMenus->isNotEmpty())
        <button
            type="button"
            @click="$store.hrmShell.openPalette()"
            class="hrm-rail-item !hidden text-ink-muted hover:bg-[#fafafa] hover:text-ink lg:!flex"
            data-rail-tip="{{ __('ui::common.labels.modules') }}"
        >
            <x-icons.layout-icon size="w-[18px] h-[18px]" color="text-current" hover="text-current" />
            <span class="hrm-rail-label">{{ __('ui::common.labels.modules') }}</span>
        </button>
        <span class="my-1 h-px w-full shrink-0 bg-hairline lg:w-7"></span>

        <nav class="flex w-full flex-col items-stretch gap-0.5 lg:items-center">
            @foreach ($otherMenus as $menu)
                @module($menu->moduleName)
                    @can($menu->permissionName)
                        <a
                            href="{{ $menu->route }}"
                            wire:navigate
                            data-rail-tip="{{ $menu->label }}"
                            @click="$store.hrmShell.railOpen = false"
                            @class([
                                'hrm-rail-item hrm-rail-link',
                                'hrm-rail-link--active bg-ink text-[#fafafa]' => $menu->isActive,
                                'text-ink-faint hover:bg-[#fafafa] hover:text-ink' => ! $menu->isActive,
                            ])
                            @if ($menu->isActive) aria-current="page" @endif
                        >
                            <x-dynamic-component :component="$menu->iconComponent" color="text-current" hover="text-current" size="w-[18px] h-[18px]" />
                            <span class="hrm-rail-label lg:hidden">{{ $menu->label }}</span>
                            <span class="hrm-rail-label hidden lg:block">{{ $menu->shortLabel }}</span>
                        </a>
                    @endcan
                @endmodule
            @endforeach
        </nav>
    @endif

    </div>

    {{-- bottom utilities: always pinned to the foot of the rail --}}
    <div class="flex w-full shrink-0 flex-col items-center gap-1.5 bg-white pt-3">
        <span class="h-px w-full shrink-0 bg-hairline lg:w-7"></span>

        @module('services')
            @can('access-settings')
                @php $settingsActive = request()->routeIs('services') || request()->routeIs('services.*'); @endphp
                <a
                    href="{{ route('services') }}"
                    wire:navigate
                    data-rail-tip="{{ __('ui::menu.items.settings') }}"
                    @class([
                        'hrm-rail-item hrm-rail-link',
                        'bg-ink text-[#fafafa]' => $settingsActive,
                        'text-ink-muted hover:bg-[#fafafa] hover:text-ink' => ! $settingsActive,
                    ])
                >
                    <x-icons.line-settings-icon color="text-current" hover="text-current" size="w-[18px] h-[18px]" />
                    <span class="hrm-rail-label">{{ __('ui::menu.rail.settings') }}</span>
                </a>
            @endcan
        @endmodule

        @can('access-admin')
            <a
                href="{{ route('admin') }}"
                wire:navigate
                data-rail-tip="{{ __('ui::common.labels.admin_panel') }}"
                class="hrm-rail-item hrm-rail-link text-amber-500 hover:bg-[#fafafa]"
            >
                <x-icons.admin-icon color="text-current" hover="text-current" size="w-[18px] h-[18px]" />
                <span class="hrm-rail-label">{{ __('ui::menu.rail.admin') }}</span>
            </a>
        @endcan

        @module('notifications')
            @can('get-notification')
                <div
                    class="hrm-rail-popover flex w-full justify-center"
                    x-data="{ isOpen: false, loadingRequest: false }"
                    @click.outside="isOpen = false"
                    @keydown.escape.window="isOpen = false"
                    x-on:livewire:navigating.window="isOpen = false"
                >
                    <livewire:notification.notifications />
                </div>
            @endcan
        @endmodule

        {{-- user chip --}}
        @php
            $userName = trim((string) (Auth::user()?->name ?? ''));
            $initials = collect(preg_split('/\s+/u', $userName, -1, PREG_SPLIT_NO_EMPTY) ?: [])
                ->take(2)
                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('');
        @endphp
        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
            <button
                type="button"
                @click="open = ! open"
                data-rail-tip="{{ $userName }}"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-[#f4f4f5] text-[12px] font-semibold text-ink transition hover:bg-hairline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400"
                :aria-expanded="open.toString()"
            >
                {{ $initials !== '' ? $initials : '—' }}
                <span class="sr-only">{{ $userName }}</span>
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition.origin.bottom.left
                class="absolute bottom-0 left-[calc(100%+12px)] z-50 w-60 overflow-hidden rounded-2xl border border-hairline bg-white shadow-overlay"
            >
                <div class="border-b border-hairline-subtle px-4 py-3">
                    <p class="truncate text-[13px] font-semibold text-ink">{{ $userName }}</p>
                    <p class="truncate text-[11.5px] text-ink-faint">{{ Auth::user()?->email }}</p>
                </div>
                <a href="{{ route('profile.edit') }}" wire:navigate class="block px-4 py-2.5 text-[13px] text-ink-soft transition hover:bg-[#fafafa]">
                    {{ __('ui::profile.titles.profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2.5 text-left text-[13px] text-ink-soft transition hover:bg-[#fafafa]">
                        {{ __('ui::auth.actions.log_out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>

<x-command-palette :menus="$paletteMenus" :actions="$paletteActions" :search-people="$paletteCanSearchPeople" />
