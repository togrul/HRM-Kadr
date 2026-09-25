@php
    $hasSidebar = isset($sidebar);
@endphp

@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('hrmShell', {
                railOpen: false,
                paletteOpen: false,
                mobilePanelOpen: false,
                panelCollapsed: localStorage.getItem('hrm.panelCollapsed') === '1',
                togglePanel() {
                    this.panelCollapsed = ! this.panelCollapsed;
                    localStorage.setItem('hrm.panelCollapsed', this.panelCollapsed ? '1' : '0');
                    document.documentElement.toggleAttribute('data-panel-collapsed', this.panelCollapsed);
                },
                openPalette() {
                    this.paletteOpen = true;
                    this.railOpen = false;
                },
            });
        });

        // Body scripts re-run on every wire:navigate while document persists — register once.
        if (! window.__hrmShellKeys) {
            window.__hrmShellKeys = true;

            document.addEventListener('keydown', (event) => {
                if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                    event.preventDefault();
                    window.Alpine?.store('hrmShell')?.openPalette();
                }
            });

            // A rail drawer / palette opened on one screen must never survive a navigation.
            document.addEventListener('livewire:navigating', () => {
                const shell = window.Alpine?.store('hrmShell');
                if (shell) {
                    shell.railOpen = false;
                    shell.paletteOpen = false;
                    shell.mobilePanelOpen = false;
                }
            });
        }
    </script>
@endonce

{{-- full-bleed up to a 24" monitor, centred beyond it --}}
<div class="mx-auto flex w-full max-w-shell items-start">
    @include('includes.header')

    <div class="min-w-0 flex-1">
        @include('layouts.navigation')

        <main class="flex w-full flex-col items-stretch gap-2 px-2 pb-4 pt-2 lg:flex-row lg:items-start">
            @if ($hasSidebar)
                {{-- phones: the context panel (sections, status filters, structure tree) opens from one compact control --}}
                <button
                    type="button"
                    @click="$store.hrmShell.mobilePanelOpen = ! $store.hrmShell.mobilePanelOpen"
                    :aria-expanded="$store.hrmShell.mobilePanelOpen.toString()"
                    aria-controls="sidebar"
                    class="sticky top-[52px] z-20 inline-flex h-10 w-fit items-center gap-2 self-start rounded-[10px] border border-hairline bg-white px-3.5 text-[13.5px] font-semibold text-ink-soft shadow-card lg:hidden"
                >
                    <svg class="h-4 w-4 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/></svg>
                    <span>{{ __('ui::common.labels.sections_and_filters') }}</span>
                    <svg class="h-3.5 w-3.5 text-ink-faint transition" :class="$store.hrmShell.mobilePanelOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <aside
                    id="sidebar"
                    :class="{ '!block': $store.hrmShell.mobilePanelOpen, 'lg:w-0 lg:opacity-0 lg:pointer-events-none': $store.hrmShell.panelCollapsed, 'lg:w-panel lg:opacity-100': ! $store.hrmShell.panelCollapsed }"
                    class="hrm-panel-shell hidden w-full shrink-0 overflow-x-hidden lg:sticky lg:top-2 lg:block lg:w-panel"
                    role="complementary"
                    aria-label="{{ __('ui::common.labels.module_navigation') }}"
                >
                    <div class="relative w-full lg:w-panel">
                        <button
                            type="button"
                            @click="$store.hrmShell.togglePanel()"
                            class="absolute right-2 top-1 z-10 hidden h-10 w-10 items-center justify-center rounded-[10px] text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 lg:inline-flex"
                            aria-controls="sidebar"
                            :aria-expanded="(! $store.hrmShell.panelCollapsed).toString()"
                            title="{{ __('ui::common.labels.collapse_panel') }}"
                        >
                            <x-icons.sidebar-toggle-icon size="w-4 h-4" color="text-current" hover="text-current" />
                            <span class="sr-only">{{ __('ui::common.labels.collapse_panel') }}</span>
                        </button>

                        {{ $sidebar }}
                    </div>
                </aside>
            @endif

            <section class="relative min-w-0 flex-1 overflow-hidden rounded-2xl border border-hairline bg-white shadow-card" aria-live="polite">
                @if ($hasSidebar)
                    <div
                        x-cloak
                        x-show="$store.hrmShell.panelCollapsed"
                        x-transition.opacity
                        class="sidebar-collapse-toggle hidden border-b border-hairline-subtle px-3 py-1 lg:flex"
                    >
                        <button
                            type="button"
                            @click="$store.hrmShell.togglePanel()"
                            class="inline-flex h-10 items-center gap-2 rounded-[10px] px-3 text-[14px] font-medium text-ink-muted transition hover:bg-[#f4f4f5] hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400"
                            aria-controls="sidebar"
                            :aria-expanded="(! $store.hrmShell.panelCollapsed).toString()"
                        >
                            <x-icons.sidebar-toggle-icon size="w-4 h-4" color="text-current" hover="text-current" />
                            {{ __('ui::common.labels.expand_panel') }}
                        </button>
                    </div>
                @endif

                {{ $slot }}
            </section>
        </main>
    </div>
</div>
