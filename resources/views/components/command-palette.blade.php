@props(['menus' => [], 'actions' => [], 'searchPeople' => false])

{{--
    ⌘K / Ctrl+K palette: people (fetched as you type), everyday actions and modules.
    Actions and modules come pre-gated from the rail's prepared collections, so the palette
    can never offer more than the user may open. Behaviour lives in resources/js/command-palette.js.
--}}

@php
    $labels = collect($menus)->map(fn ($menu) => mb_strtolower($menu->label))
        ->merge(collect($actions)->map(fn ($action) => mb_strtolower($action['label'])))
        ->values();

    $rowClass = 'flex w-full min-h-11 items-center gap-3 rounded-xl px-2 py-1.5 text-left text-[13.5px] text-ink-soft outline-none transition-colors aria-selected:bg-[#f4f4f5] aria-selected:text-ink';
    $iconClass = 'hrm-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] border border-hairline bg-[#fafafa] text-ink-muted';
@endphp

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
        x-data="hrmCommandPalette({ labels: @js($labels), searchUrl: @js($searchPeople ? route('personnel.palette-search') : null) })"
        x-effect="if ($store.hrmShell.paletteOpen) { open() }"
        @mousemove="hover($event)"
        class="w-full max-w-[620px] overflow-hidden rounded-2xl border border-hairline bg-white shadow-overlay"
    >
        <div class="flex items-center gap-3 border-b border-hairline px-4 py-3.5">
            <x-icons.search-file size="w-[18px] h-[18px]" color="text-ink-faint" hover="text-ink-faint" />
            <input
                type="text"
                x-model="query"
                x-ref="paletteInput"
                @input="onInput()"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter.prevent="choose()"
                role="combobox"
                aria-expanded="true"
                aria-controls="hrm-palette-list"
                aria-label="{{ __('ui::common.labels.search') }}"
                placeholder="{{ __('ui::common.labels.command_palette_hint') }}"
                autocomplete="off"
                class="w-full border-0 bg-transparent p-0 text-[14px] text-ink placeholder:text-ink-faint focus:outline-none focus:ring-0"
            >
            <span x-show="loading" x-cloak class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-hairline border-t-ink-muted" aria-label="{{ __('ui::common.palette.searching') }}"></span>
            <kbd class="hidden shrink-0 rounded-md border border-hairline bg-[#fafafa] px-1.5 py-0.5 font-mono text-[10.5px] text-ink-faint sm:block">ESC</kbd>
            <button type="button" @click="$store.hrmShell.paletteOpen = false" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] text-ink-muted hover:bg-[#f4f4f5] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 sm:hidden" aria-label="{{ __('ui::common.actions.close') }}">
                <x-icons.close-icon size="w-4 h-4" color="text-current" hover="text-current" />
            </button>
        </div>

        <div id="hrm-palette-list" role="listbox" class="hrm-scroll max-h-[62vh] overflow-y-auto p-2">
            @if ($searchPeople)
                <div x-show="people.length > 0" x-cloak>
                    <p class="hrm-eyebrow px-2 pb-1 pt-2">{{ __('ui::common.palette.people') }}</p>

                    <template x-for="person in people" :key="person.id">
                        <a
                            :href="person.url"
                            wire:navigate
                            data-palette-item
                            role="option"
                            @click="$store.hrmShell.paletteOpen = false"
                            class="{{ $rowClass }}"
                        >
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#f4f4f5] text-[11px] font-semibold text-ink-muted" x-text="person.name.split(' ').slice(0, 2).map((part) => part.charAt(0)).join('')"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium text-ink" x-text="person.name"></span>
                                <span class="block truncate text-[11.5px] text-ink-faint" x-text="person.position"></span>
                            </span>
                            <span x-show="person.left" class="shrink-0 rounded-full bg-[#f4f4f5] px-2 py-0.5 text-[10.5px] text-ink-muted">{{ __('ui::common.palette.left') }}</span>
                            <span class="hrm-num shrink-0 text-[11.5px] text-ink-faint" x-text="person.tabel_no"></span>
                        </a>
                    </template>
                </div>
            @endif

            @if (count($actions) > 0)
                <div x-show="@js(collect($actions)->pluck('label')->map(fn ($label) => mb_strtolower($label))->values()).some((label) => matches(label))">
                    <p class="hrm-eyebrow px-2 pb-1 pt-2">{{ __('ui::common.palette.actions_title') }}</p>

                    @foreach ($actions as $action)
                        <a
                            href="{{ $action['url'] }}"
                            wire:navigate
                            data-palette-item
                            role="option"
                            x-show="matches(@js(mb_strtolower($action['label'])))"
                            @click="$store.hrmShell.paletteOpen = false"
                            class="{{ $rowClass }}"
                        >
                            <span class="{{ $iconClass }}">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $action['icon'] }}"/></svg>
                            </span>
                            <span class="truncate font-medium">{{ $action['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div x-show="labels.slice(0, {{ count($menus) }}).some((label) => matches(label))">
                <p class="hrm-eyebrow px-2 pb-1 pt-2">{{ __('ui::common.labels.modules') }}</p>

                <div class="grid grid-cols-1 gap-1 sm:grid-cols-2">
                @foreach ($menus as $menu)
                    <a
                        href="{{ $menu->route }}"
                        wire:navigate
                        data-palette-item
                        role="option"
                        x-show="matches(@js(mb_strtolower($menu->label)))"
                        @click="$store.hrmShell.paletteOpen = false"
                        class="{{ $rowClass }}"
                    >
                        <span class="{{ $iconClass }}">
                            <x-dynamic-component :component="$menu->iconComponent" color="text-current" hover="text-current" size="w-[16px] h-[16px]" />
                        </span>
                        <span class="truncate font-medium">{{ $menu->label }}</span>
                        @if ($menu->isActive)
                            <span class="ml-auto h-1.5 w-1.5 shrink-0 rounded-full bg-ink-muted" aria-hidden="true"></span>
                        @endif
                    </a>
                @endforeach
                </div>
            </div>

            <p x-show="nothingFound" x-cloak class="px-2 py-6 text-center text-[13px] text-ink-faint">{{ __('ui::common.labels.no_results') }}</p>
        </div>

        <div class="hidden items-center gap-4 border-t border-hairline bg-[#fafafa] px-4 py-2 text-[11px] text-ink-faint sm:flex">
            <span class="flex items-center gap-1.5"><kbd class="rounded border border-hairline bg-white px-1 font-mono">↑</kbd><kbd class="rounded border border-hairline bg-white px-1 font-mono">↓</kbd>{{ __('ui::common.palette.hint_navigate') }}</span>
            <span class="flex items-center gap-1.5"><kbd class="rounded border border-hairline bg-white px-1 font-mono">↵</kbd>{{ __('ui::common.palette.hint_open') }}</span>
        </div>
    </div>
</div>
