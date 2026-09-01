{{--
    Mobile top bar. On desktop every one of these controls lives in the 60px icon rail
    (search, settings, admin, notifications, profile/logout), so the bar is hidden there.
--}}
<nav class="sticky top-0 z-20 flex items-center gap-2 border-b border-hairline bg-white/90 px-3 py-2 backdrop-blur lg:hidden">
    <button
        type="button"
        @click="$store.hrmShell.railOpen = ! $store.hrmShell.railOpen"
        class="inline-flex h-9 w-9 items-center justify-center rounded-[10px] border border-hairline bg-[#fafafa] text-ink-muted transition hover:text-ink"
        aria-controls="hrm-rail"
    >
        <x-icons.menu-icon size="w-5 h-5" color="text-current" hover="text-current" />
        <span class="sr-only">{{ __('ui::common.labels.open_menu') }}</span>
    </button>

    <a href="{{ route('home') }}" wire:navigate title="{{ config('app.name') }}">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-ink text-[12px] font-bold tracking-tight text-white">HR</span>
    </a>

    <button
        type="button"
        @click="$store.hrmShell.openPalette()"
        class="ml-auto inline-flex h-9 items-center gap-2 rounded-[10px] border border-hairline bg-[#fafafa] px-3 text-[12.5px] text-ink-faint transition hover:text-ink"
    >
        <x-icons.search-file size="w-4 h-4" color="text-current" hover="text-current" />
        <span>{{ __('ui::common.labels.search') }}</span>
    </button>

    <x-dropdown align="right">
        <x-slot name="trigger">
            <button class="inline-flex h-9 items-center rounded-[10px] border border-hairline bg-[#f4f4f5] px-3 text-[12.5px] font-medium text-ink-soft">
                <span class="max-w-[9rem] truncate">{{ Auth::user()?->name }}</span>
                <x-icons.arrow-icon size="w-4 h-4" />
            </button>
        </x-slot>

        <x-slot name="content">
            <x-dropdown-link :href="route('profile.edit')">
                {{ __('ui::profile.titles.profile') }}
            </x-dropdown-link>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-dropdown-link :href="route('logout')"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('ui::auth.actions.log_out') }}
                </x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>
</nav>
