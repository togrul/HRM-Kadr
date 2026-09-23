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
                <button
                    type="button"
                    @click="$store.hrmShell.mobilePanelOpen = ! $store.hrmShell.mobilePanelOpen"
                    :aria-expanded="$store.hrmShell.mobilePanelOpen.toString()"
                    aria-controls="sidebar"
                    class="sticky top-[52px] z-20 flex min-h-11 w-full items-center justify-between rounded-xl border border-hairline bg-white px-4 text-[14px] font-medium text-ink shadow-card lg:hidden"
                >
                    <span>{{ __('ui::common.labels.module_navigation') }}</span>
                    <span x-text="$store.hrmShell.mobilePanelOpen ? @js(__('ui::common.labels.collapse_panel')) : @js(__('ui::common.labels.expand_panel'))" class="text-ink-muted"></span>
                </button>
                <aside
                    id="sidebar"
                    x-cloak
                    :class="{ '!block': $store.hrmShell.mobilePanelOpen, 'lg:w-0 lg:opacity-0 lg:pointer-events-none': $store.hrmShell.panelCollapsed, 'lg:w-panel lg:opacity-100': ! $store.hrmShell.panelCollapsed }"
                    class="hrm-panel-shell hidden w-full shrink-0 overflow-x-hidden lg:sticky lg:top-2 lg:block"
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
