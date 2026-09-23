@props(['menus' => []])

{{--
    ⌘K / Ctrl+K module switcher. Fed by the same prepared rail menu collection so it can
    never drift from the icon rail. Filtering is client-side over an already rendered list.
--}}

<div
    x-cloak
    x-show="$store.hrmShell.paletteOpen"
    x-transition.opacity
    @keydown.escape.window="$store.hrmShell.paletteOpen = false"
    class="fixed inset-0 z-[60] flex items-start justify-center bg-zinc-900/[0.32] px-4 pt-[12vh] backdrop-blur-[3px]"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('ui::common.labels.command_palette') }}"
>
    <div
        @click.outside="$store.hrmShell.paletteOpen = false"
        x-data="{ query: '', labels: @js(collect($menus)->map(fn ($menu) => mb_strtolower($menu->label))->values()) }"
        x-effect="if ($store.hrmShell.paletteOpen) { query = ''; $nextTick(() => $refs.paletteInput.focus()) }"
        class="w-full max-w-[620px] overflow-hidden rounded-2xl border border-hairline bg-white shadow-overlay"
    >
        <div class="flex items-center gap-3 border-b border-hairline px-4 py-3.5">
            <x-icons.search-file size="w-[18px] h-[18px]" color="text-ink-faint" hover="text-ink-faint" />
            <input
                type="text"
                x-model="query"
                x-ref="paletteInput"
                @keydown.enter="[...$root.querySelectorAll('[data-palette-item]')].find((item) => item.offsetParent !== null)?.click()"
                aria-label="{{ __('ui::common.labels.search') }}"
                placeholder="{{ __('ui::common.labels.command_palette_hint') }}"
                class="w-full border-0 bg-transparent p-0 text-[14px] text-ink placeholder:text-ink-faint focus:outline-none focus:ring-0"
            >
            <kbd class="hidden shrink-0 rounded-md border border-hairline bg-[#fafafa] px-1.5 py-0.5 font-mono text-[10.5px] text-ink-faint sm:block">ESC</kbd>
            <button type="button" @click="$store.hrmShell.paletteOpen = false" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] text-ink-muted hover:bg-[#f4f4f5] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400" aria-label="{{ __('ui::common.actions.close') }}">
                <x-icons.close-icon size="w-4 h-4" color="text-current" hover="text-current" />
            </button>
        </div>

        <div class="hrm-scroll max-h-[70vh] overflow-y-auto p-2">
            <p class="hrm-eyebrow px-2 pb-1 pt-2">{{ __('ui::common.labels.modules') }}</p>

            <div class="grid grid-cols-1 gap-1 sm:grid-cols-2">
            @foreach ($menus as $menu)
                <a
                    href="{{ $menu->route }}"
                    wire:navigate
                    data-palette-item
                    x-show="query === '' || '{{ mb_strtolower($menu->label) }}'.includes(query.toLowerCase())"
                    @click="$store.hrmShell.paletteOpen = false"
                    class="flex min-h-12 items-center gap-3 rounded-xl px-2 py-2 text-[14px] text-ink-soft transition hover:bg-[#f4f4f5] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink"
                >
                    <span class="hrm-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] border border-hairline bg-[#fafafa] text-ink-muted">
                        <x-dynamic-component :component="$menu->iconComponent" color="text-current" hover="text-current" size="w-[16px] h-[16px]" />
                    </span>
                    <span class="truncate font-medium">{{ $menu->label }}</span>
                    @if ($menu->isActive)
                        <span class="ml-auto rounded-full bg-[#f4f4f5] px-2 py-0.5 font-mono text-[10.5px] uppercase text-ink-muted">•</span>
                    @endif
                </a>
            @endforeach
            </div>

            <p
                x-show="query !== '' && ! labels.some((label) => label.includes(query.toLowerCase()))"
                x-cloak
                class="px-2 py-6 text-center text-[13px] text-ink-faint"
            >{{ __('ui::common.labels.no_results') }}</p>
        </div>
    </div>
</div>
