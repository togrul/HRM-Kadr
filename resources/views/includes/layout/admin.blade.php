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
                        'hrm-icon flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-[13px] transition',
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
            <a href="{{ route('home') }}" wire:navigate class="hrm-icon flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-[13px] text-white/60 transition hover:bg-white/10 hover:text-white">
                <x-icons.shutdown-icon size="w-[17px] h-[17px]" color="text-current" hover="text-current" />
                <span>{{ __('ui::common.labels.return_to_dashboard') }}</span>
            </a>

            <div class="rounded-xl bg-white/5 px-3 py-2.5">
                <p class="truncate text-[12.5px] font-medium text-white">{{ Auth::user()?->name }}</p>
                <p class="truncate text-[11px] text-white/40">{{ Auth::user()?->email }}</p>
            </div>
        </div>
    </aside>

    {{-- compact admin bar for small screens: the sidebar above is desktop-only --}}
    <div class="flex min-w-0 flex-1 flex-col gap-2">
        <nav class="hrm-scroll flex items-center gap-1.5 overflow-x-auto rounded-2xl border border-hairline bg-white px-2 py-2 lg:hidden">
            <a href="{{ route('home') }}" wire:navigate class="shrink-0 rounded-[10px] border border-hairline px-2.5 py-1.5 text-[12px] text-ink-muted">
                {{ __('ui::common.labels.return_to_dashboard') }}
            </a>
            @foreach (config('admin.menu_items') as $menuItem)
                @continue($menuItem['route'] !== '#' && ! \App\Support\Navigation\MenuPresentation::hasRoute($menuItem['route']))
                @php $active = request()->routeIs($menuItem['route']); @endphp
                <a
                    href="{{ \App\Support\Navigation\MenuPresentation::route($menuItem['route']) }}"
                    wire:navigate
                    @class([
                        'shrink-0 rounded-[10px] px-2.5 py-1.5 text-[12px] transition',
                        'bg-ink text-white' => $active,
                        'text-ink-muted hover:bg-[#fafafa]' => ! $active,
                    ])
                >{{ __($menuItem['label']) }}</a>
            @endforeach
        </nav>

        <section class="min-w-0 flex-1 overflow-hidden rounded-2xl border border-hairline bg-white p-4 shadow-card">
            {{ $slot }}
        </section>
    </div>
</main>

@push('js')
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
@endpush
