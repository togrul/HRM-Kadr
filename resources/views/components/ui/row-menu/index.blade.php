@props([
    'label' => null,   // accessible name of the "⋯" trigger
    'forceUp' => false,
])

{{--
    Row overflow menu: a "⋯" trigger and a vertical list teleported to <body>, so a table's
    overflow never clips it. Fill it with x-ui.row-menu.item / x-ui.row-menu.separator.
    Any click inside the panel closes it after the item's own handler has run.
--}}
{{-- Every item hidden by permissions → no trigger at all, rather than an empty dropdown. --}}
@if (trim($slot) !== '')
<div class="relative inline-block text-left" x-data="rowMenu(@js((bool) $forceUp))" {{ $attributes }}>
    <button
        x-ref="menuButton"
        type="button"
        x-on:click.stop="toggle()"
        x-bind:aria-expanded="open.toString()"
        aria-haspopup="menu"
        aria-label="{{ $label ?? __('ui::common.labels.more_actions') }}"
        title="{{ $label ?? __('ui::common.labels.more_actions') }}"
        class="inline-flex h-9 w-9 items-center justify-center rounded-full text-ink-muted transition hover:bg-[#f4f4f5] hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
    </button>

    <template x-teleport="body">
        <div x-cloak x-show="open" class="fixed inset-0 z-[120]">
            <div class="absolute inset-0" x-on:click="close()"></div>

            <div
                x-ref="menuPanel"
                role="menu"
                x-transition.opacity.duration.100ms
                x-bind:style="panelStyle"
                x-on:click="close()"
                class="fixed z-[121] min-w-48 overflow-hidden rounded-xl border border-hairline bg-white py-1 shadow-overlay"
            >
                {{ $slot }}
            </div>
        </div>
    </template>
</div>
@endif
