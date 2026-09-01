@once
    <style>
        /* Rail popovers (notification bell) must open to the RIGHT of the 60px rail. */
        .hrm-rail-popover [class*='absolute'][class*='right-0'] {
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

            // The rail is 60px wide against the left edge. Comparing one number lets every
            // pointer event elsewhere in the app bail out before any DOM tree walk.
            const RAIL_EDGE = 76;

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

    $pinnedMenus = $preparedMenus->take(5);
    $otherMenus = $preparedMenus->slice(5)->values();
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
    class="fixed inset-y-0 left-0 z-40 flex h-screen w-rail shrink-0 -translate-x-full flex-col items-center overflow-visible border-r border-hairline bg-white py-3 transition-transform duration-200 lg:sticky lg:top-0 lg:bottom-auto lg:translate-x-0"
    aria-label="{{ __('ui::common.labels.module_navigation') }}"
>
    {{-- logo mark --}}
    <a href="{{ route('home') }}" wire:navigate class="mb-1" data-rail-tip="{{ config('app.name') }}">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-ink text-[12px] font-bold tracking-tight text-white">HR</span>
    </a>

    {{-- command palette trigger --}}
    <button
        type="button"
        @click="$store.hrmShell.openPalette()"
        data-rail-tip="{{ __('ui::common.labels.search') }}"
        class="flex h-[34px] w-10 shrink-0 items-center justify-center rounded-[10px] border border-hairline bg-[#fafafa] text-ink-muted transition hover:bg-white hover:text-ink"
    >
        <x-icons.search-file size="w-[18px] h-[18px]" color="text-current" hover="text-current" />
        <span class="sr-only">{{ __('ui::common.labels.search') }}</span>
    </button>

    <span class="my-1 h-px w-7 shrink-0 bg-hairline"></span>

    {{-- only the module list scrolls, so the bell and the avatar never leave the viewport --}}
    <div class="hrm-scroll flex w-full min-h-0 flex-1 flex-col items-center gap-1.5 overflow-y-auto overflow-x-hidden">
    {{-- pinned modules --}}
    <nav class="flex w-full flex-col items-center gap-1">
        @foreach ($pinnedMenus as $menu)
            @module($menu->moduleName)
                @can($menu->permissionName)
                    <a
                        href="{{ $menu->route }}"
                        wire:navigate
                        data-rail-tip="{{ $menu->label }}"
                        @click="$store.hrmShell.railOpen = false"
                        @class([
                            'hrm-rail-link relative flex h-10 w-11 shrink-0 items-center justify-center rounded-xl transition',
                            'hrm-rail-link--active bg-ink text-[#fafafa]' => $menu->isActive,
                            'text-ink-muted hover:bg-[#fafafa] hover:text-ink' => ! $menu->isActive,
                        ])
                        @if ($menu->isActive) aria-current="page" @endif
                    >
                        <x-dynamic-component :component="$menu->iconComponent" color="text-current" hover="text-current" size="w-[18px] h-[18px]" />
                        <span class="sr-only">{{ $menu->label }}</span>
                    </a>
                @endcan
            @endmodule
        @endforeach
    </nav>

    @if ($otherMenus->isNotEmpty())
        <span class="my-1 h-px w-7 shrink-0 bg-hairline"></span>

        <nav class="flex w-full flex-col items-center gap-0.5">
            @foreach ($otherMenus as $menu)
                @module($menu->moduleName)
                    @can($menu->permissionName)
                        <a
                            href="{{ $menu->route }}"
                            wire:navigate
                            data-rail-tip="{{ $menu->label }}"
                            @click="$store.hrmShell.railOpen = false"
                            @class([
                                'hrm-rail-link relative flex h-[38px] w-11 shrink-0 items-center justify-center rounded-xl transition',
                                'hrm-rail-link--active bg-ink text-[#fafafa]' => $menu->isActive,
                                'text-ink-faint hover:bg-[#fafafa] hover:text-ink' => ! $menu->isActive,
                            ])
                            @if ($menu->isActive) aria-current="page" @endif
                        >
                            <x-dynamic-component :component="$menu->iconComponent" color="text-current" hover="text-current" size="w-[18px] h-[18px]" />
                            <span class="sr-only">{{ $menu->label }}</span>
                        </a>
                    @endcan
                @endmodule
            @endforeach
        </nav>
    @endif

    </div>

    {{-- bottom utilities: always pinned to the foot of the rail --}}
    <div class="flex w-full shrink-0 flex-col items-center gap-1.5 bg-white pt-3">
        <span class="h-px w-7 shrink-0 bg-hairline"></span>

        @module('services')
            @can('access-settings')
                @php $settingsActive = request()->routeIs('services') || request()->routeIs('services.*'); @endphp
                <a
                    href="{{ route('services') }}"
                    wire:navigate
                    data-rail-tip="{{ __('ui::menu.items.settings') }}"
                    @class([
                        'hrm-rail-link relative flex h-[34px] w-10 shrink-0 items-center justify-center rounded-[10px] transition',
                        'bg-ink text-[#fafafa]' => $settingsActive,
                        'text-ink-muted hover:bg-[#fafafa] hover:text-ink' => ! $settingsActive,
                    ])
                >
                    <x-icons.line-settings-icon color="text-current" hover="text-current" size="w-[18px] h-[18px]" />
                    <span class="sr-only">{{ __('ui::menu.items.settings') }}</span>
                </a>
            @endcan
        @endmodule

        @can('access-admin')
            <a
                href="{{ route('admin') }}"
                wire:navigate
                data-rail-tip="{{ __('ui::common.labels.admin_panel') }}"
                class="hrm-rail-link relative flex h-[34px] w-10 shrink-0 items-center justify-center rounded-[10px] text-amber-500 transition hover:bg-[#fafafa]"
            >
                <x-icons.admin-icon color="text-current" hover="text-current" size="w-[18px] h-[18px]" />
                <span class="sr-only">{{ __('ui::common.labels.admin_panel') }}</span>
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
                class="flex h-8 w-8 items-center justify-center rounded-full bg-[#f4f4f5] text-[11px] font-semibold text-ink transition hover:bg-hairline"
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

<x-command-palette :menus="$paletteMenus" />
