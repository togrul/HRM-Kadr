@props([
    'menuActions' => [],
    'forceUp' => false,
])

    <div
        class="relative inline-block text-left"
        x-data="{
            open: false,
            openUp: @js((bool) $forceUp),
            forceUp: @js((bool) $forceUp),
            panelStyle: '',
            toggle() {
                this.open = !this.open;
                if (!this.open) return;
                this.openUp = this.forceUp;
                this.$nextTick(() => this.reposition());
            },
            reposition() {
                const panel = this.$refs.menuPanel;
                const button = this.$refs.menuButton;
                if (!panel || !button) return;

                const buttonRect = button.getBoundingClientRect();
                const panelHeight = panel.offsetHeight || 220;
                const panelWidth = panel.offsetWidth || 260;
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

                let left = buttonRect.right - panelWidth;
                left = Math.max(8, Math.min(left, viewportWidth - panelWidth - 8));

                let top;
                if (this.openUp) {
                    top = buttonRect.top - panelHeight - 8;
                    if (top < 8) top = 8;
                } else {
                    top = buttonRect.bottom + 8;
                    if (top + panelHeight > viewportHeight - 8) {
                        top = Math.max(8, viewportHeight - panelHeight - 8);
                    }
                }

                this.panelStyle = `left:${left}px; top:${top}px;`;
            }
        }"
        x-on:keydown.escape.window="open = false"
        x-on:resize.window="if (open) reposition()"
        x-on:scroll.window="if (open) reposition()"
    >
    <button
        x-ref="menuButton"
        type="button"
        x-on:click.stop="toggle()"
        class="flex items-center justify-center w-8 h-8 text-xs font-medium uppercase rounded-lg bg-blue-50 hover:bg-blue-100"
        title="{{ __('More actions') }}"
    >
        <x-icons.settings-icon />
    </button>

    <template x-teleport="body">
        <div x-cloak x-show="open" class="fixed inset-0 z-[120]">
            <div class="absolute inset-0" x-on:click="open = false"></div>

            <div
                x-ref="menuPanel"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                x-bind:class="openUp ? 'origin-bottom-right' : 'origin-top-right'"
                x-bind:style="panelStyle"
                class="fixed z-[121] rounded-md bg-white shadow-lg shadow-black/5 ring-1 ring-black ring-opacity-5 focus:outline-none"
                x-on:click.stop
            >
                <div class="flex items-center divide-x divide-neutral-100">
                    @foreach ($menuActions as $menuAction)
                        <div class="px-4 py-2 hover:bg-slate-100">
                            @if ($menuAction->type === 'link')
                                <a
                                    href="{{ $menuAction->href }}"
                                    @if ($menuAction->targetBlank)
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    @endif
                                    class="inline-flex items-center justify-center"
                                    title="{{ $menuAction->label }}"
                                    x-on:click="open = false"
                                >
                                    <x-dynamic-component
                                        :component="$menuAction->icon"
                                        :color="$menuAction->iconProps['color'] ?? null"
                                        :hover="$menuAction->iconProps['hover'] ?? null"
                                    />
                                </a>
                            @else
                                <button
                                    type="button"
                                    wire:click="handleRowAction('{{ $menuAction->id }}', @js($menuAction->actionPayload))"
                                    @if ($menuAction->confirmMessage)
                                        wire:confirm="{{ $menuAction->confirmMessage }}"
                                    @endif
                                    @if ($menuAction->wireTarget)
                                        wire:loading.attr="disabled"
                                        wire:target="{{ $menuAction->wireTarget }}"
                                    @else
                                        wire:loading.attr="disabled"
                                        wire:target="handleRowAction"
                                    @endif
                                    class="inline-flex items-center justify-center"
                                    title="{{ $menuAction->label }}"
                                    x-on:click="open = false"
                                >
                                    <x-dynamic-component
                                        :component="$menuAction->icon"
                                        :color="$menuAction->iconProps['color'] ?? null"
                                        :hover="$menuAction->iconProps['hover'] ?? null"
                                    />
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </template>
</div>
