{{--
    Admin back-office shell. Keeps its own dedicated navigation (it is not part of the
    module rail) but shares the premium design tokens with the rest of the app.
--}}
<main class="mx-auto flex min-h-screen w-full max-w-shell items-stretch gap-2 p-2">
    <aside class="hrm-scroll sticky top-2 hidden max-h-[calc(100vh-1rem)] w-panel shrink-0 flex-col overflow-y-auto rounded-2xl bg-ink px-3 py-4 text-white lg:flex">
        <a href="{{ route('admin') }}" wire:navigate class="mb-6 flex items-center gap-2.5 px-2">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-white/10 text-[12px] font-bold tracking-tight text-white">HR</span>
            <span class="hrm-eyebrow !text-white/50">{{ __('ui::common.labels.admin_panel') }}</span>
        </a>

        <nav class="flex flex-1 flex-col gap-0.5">
            @foreach (config('admin.menu_items') as $menuItem)
                @continue($menuItem['route'] !== '#' && ! \App\Support\Navigation\MenuPresentation::hasRoute($menuItem['route']))
                @php
                    $name = "icons.{$menuItem['icon']}";
                    $route = \App\Support\Navigation\MenuPresentation::route($menuItem['route']);
                    $active = request()->routeIs($menuItem['route']);
                @endphp
                <a
                    href="{{ $route }}"
                    wire:navigate
                    @class([
                        'hrm-icon flex min-h-10 items-center gap-2.5 rounded-xl px-2.5 py-2 text-[14px] transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white',
                        'bg-white text-ink font-semibold' => $active,
                        'text-white/60 hover:bg-white/10 hover:text-white' => ! $active,
                    ])
                >
                    <x-dynamic-component :component="$name" color="text-current" hover="text-current" size="w-[17px] h-[17px]" />
                    <span class="truncate">{{ __($menuItem['label']) }}</span>
                </a>
            @endforeach
        </nav>

        <div class="mt-4 space-y-2 border-t border-white/10 pt-4">
            <a href="{{ route('home') }}" wire:navigate class="hrm-icon flex min-h-10 items-center gap-2.5 rounded-xl px-2.5 py-2 text-[14px] text-white/60 transition hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                <x-icons.shutdown-icon size="w-[17px] h-[17px]" color="text-current" hover="text-current" />
                <span>{{ __('ui::common.labels.return_to_dashboard') }}</span>
            </a>

            <div class="rounded-xl bg-white/5 px-3 py-2.5">
                <p class="truncate text-[14px] font-medium text-white">{{ Auth::user()?->name }}</p>
                <p class="truncate text-[12px] text-white/60">{{ Auth::user()?->email }}</p>
            </div>
        </div>
    </aside>

    {{-- compact admin bar for small screens: the sidebar above is desktop-only --}}
    <div class="flex min-w-0 flex-1 flex-col gap-2">
        @php
            $availableAdminItems = collect(config('admin.menu_items'))
                ->filter(fn (array $item): bool => $item['route'] === '#' || \App\Support\Navigation\MenuPresentation::hasRoute($item['route']))
                ->values();
            $activeAdminItem = $availableAdminItems->first(fn (array $item): bool => request()->routeIs($item['route']));
            $adminSearchLabels = $availableAdminItems->map(fn (array $item): string => mb_strtolower(__($item['label'])))->all();
        @endphp
        <nav
            class="relative z-30 rounded-2xl border border-hairline bg-white p-2 lg:hidden"
            aria-label="{{ __('ui::common.labels.admin_panel') }}"
            x-data="{ open: false, query: '', labels: @js($adminSearchLabels) }"
            @click.outside="open = false"
            @keydown.escape.window="open = false"
        >
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="open = ! open; if (open) $nextTick(() => $refs.search.focus())"
                    :aria-expanded="open.toString()"
                    aria-controls="admin-mobile-menu"
                    class="flex h-10 min-w-0 flex-1 items-center justify-between gap-2 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-left text-[14px] font-semibold text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400"
                >
                    <span class="truncate">{{ $activeAdminItem ? __($activeAdminItem['label']) : __('ui::common.labels.admin_panel') }}</span>
                    <svg class="h-4 w-4 shrink-0 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <a href="{{ route('home') }}" wire:navigate class="inline-flex h-10 shrink-0 items-center rounded-[10px] border border-hairline px-3 text-[14px] font-medium text-ink-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400">
                    {{ __('ui::common.labels.return_to_dashboard') }}
                </a>
            </div>

            <div id="admin-mobile-menu" x-cloak x-show="open" class="absolute left-2 right-2 top-full z-40 mt-1 rounded-xl border border-hairline bg-white p-2 shadow-overlay">
                <label class="sr-only" for="admin-mobile-search">{{ __('ui::common.labels.search') }}</label>
                <input id="admin-mobile-search" x-ref="search" x-model="query" type="search" autocomplete="off" placeholder="{{ __('ui::common.placeholders.search') }}" class="mb-2 h-10 w-full rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-base text-ink outline-none focus:border-ink focus:bg-white focus:ring-[3px] focus:ring-[#e4e4e7] sm:text-sm">
                <div class="hrm-scroll max-h-[min(55vh,420px)] space-y-0.5 overflow-y-auto">
                    @foreach ($availableAdminItems as $menuItem)
                        @php $active = request()->routeIs($menuItem['route']); @endphp
                        <a
                            href="{{ \App\Support\Navigation\MenuPresentation::route($menuItem['route']) }}"
                            wire:navigate
                            x-show="@js(mb_strtolower(__($menuItem['label']))).includes(query.trim().toLocaleLowerCase())"
                            @class([
                                'flex min-h-10 items-center rounded-[10px] px-3 text-[14px] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400',
                                'bg-ink font-semibold text-white' => $active,
                                'text-ink-muted hover:bg-[#f4f4f5]' => ! $active,
                            ])
                        >{{ __($menuItem['label']) }}</a>
                    @endforeach
                    <p x-show="query.trim() && ! labels.some(label => label.includes(query.trim().toLocaleLowerCase()))" class="px-3 py-4 text-center text-[14px] text-ink-muted">{{ __('ui::common.labels.no_results') }}</p>
                </div>
            </div>
        </nav>

        <section class="min-w-0 flex-1 overflow-hidden rounded-2xl border border-hairline bg-white p-4 shadow-card">
            {{ $slot }}
        </section>
    </div>
</main>
