@php
    $hasSidebar = isset($sidebar);
@endphp

@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('hrmShell', {
                railOpen: false,
                paletteOpen: false,
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
            }
        });
    </script>
@endonce

{{-- full-bleed up to a 24" monitor, centred beyond it --}}
<div class="mx-auto flex w-full max-w-shell items-start">
    @include('includes.header')

    <div class="min-w-0 flex-1">
        @include('layouts.navigation')

        <main class="flex w-full flex-col items-stretch gap-2 px-2 pb-4 pt-2 lg:flex-row lg:items-start">
            @if ($hasSidebar)
                <aside
                    id="sidebar"
                    x-cloak
                    :class="$store.hrmShell.panelCollapsed ? 'lg:w-0 lg:opacity-0 lg:pointer-events-none' : 'lg:w-panel lg:opacity-100'"
                    class="hrm-panel-shell w-full shrink-0 overflow-x-hidden lg:sticky lg:top-2"
                    role="complementary"
                    aria-label="{{ __('ui::common.labels.module_navigation') }}"
                >
                    <div class="relative w-full lg:w-panel">
                        <button
                            type="button"
                            @click="$store.hrmShell.togglePanel()"
                            class="absolute right-2.5 top-2.5 z-10 hidden h-6 w-6 items-center justify-center rounded-md text-ink-faint transition hover:bg-[#f4f4f5] hover:text-ink lg:inline-flex"
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
                    <button
                        type="button"
                        @click="$store.hrmShell.togglePanel()"
                        x-cloak
                        x-show="$store.hrmShell.panelCollapsed"
                        x-transition.opacity
                        class="sidebar-collapse-toggle absolute left-3 top-3 z-10 hidden h-7 w-7 items-center justify-center rounded-lg border border-hairline bg-white text-ink-muted transition hover:bg-[#fafafa] hover:text-ink lg:inline-flex"
                        aria-controls="sidebar"
                        title="{{ __('ui::common.labels.expand_panel') }}"
                    >
                        <x-icons.sidebar-toggle-icon size="w-4 h-4" color="text-current" hover="text-current" />
                        <span class="sr-only">{{ __('ui::common.labels.expand_panel') }}</span>
                    </button>
                @endif

                {{ $slot }}
            </section>
        </main>
    </div>
</div>
