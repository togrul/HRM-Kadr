@props([
    'size' => 'large',
    'showHeaderClose' => true,
    'localState' => false,
])

@php
    $uiEvents = app(\App\Modules\Personnel\Services\PersonnelUiEvents::class);
    $sizeClass = match ($size) {
        'large' => 'md:max-w-3xl lg:max-w-4xl',
        'x-large' => 'md:max-w-4xl lg:max-w-5xl',
        'xx-large' => 'md:max-w-5xl lg:max-w-6xl',
        default => 'md:max-w-3xl lg:max-w-4xl',
    };

    $closeOnEvents = $uiEvents->sideModalCloseEvents();
@endphp

@teleport('body')
    <div
        x-data="{
            @if($localState)
            serverOpen: false,
            @else
            serverOpen: @entangle('isSideModalOpen').live,
            @endif
            isOpen: false,
            closing: false,
            activeMenu: '',
            closeEvents: @js($closeOnEvents),
            previousFocus: null,
            cleanupCallbacks: [],
            lockBody() {
                document.body.classList.add('overflow-hidden');
                document.body.classList.add('side-modal-open');
                document.documentElement.classList.add('overflow-hidden');
            },
            unlockBody() {
                document.body.classList.remove('overflow-hidden');
                document.body.classList.remove('side-modal-open');
                document.documentElement.classList.remove('overflow-hidden');
            },
            focusables() {
                return [...$el.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type=hidden]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex=\'-1\'])')]
                    .filter((element) => element.offsetParent !== null);
            },
            firstFocusable() {
                return this.focusables()[0];
            },
            lastFocusable() {
                return this.focusables().slice(-1)[0];
            },
            handleTab(event) {
                if (! this.isOpen) {
                    return;
                }

                const first = this.firstFocusable();
                const last = this.lastFocusable();

                if (! first || ! last) {
                    return;
                }

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                }

                if (! event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            },
            open(menu = '') {
                if (typeof menu === 'string' && menu.length > 0) {
                    this.activeMenu = menu;
                }

                this.closing = false;
                this.previousFocus = document.activeElement;
                this.lockBody();
                this.isOpen = true;
                this.$nextTick(() => this.$refs.closeBtn?.focus());
            },
            close() {
                if (this.closing) {
                    return;
                }

                this.closing = true;
                this.isOpen = false;

                window.setTimeout(() => {
                    this.unlockBody();
                    this.activeMenu = '';

                    if ($wire && typeof $wire.call === 'function') {
                        $wire.call('closeSideMenu');
                    }

                    if (this.previousFocus && typeof this.previousFocus.focus === 'function') {
                        this.$nextTick(() => this.previousFocus.focus());
                    }

                    this.closing = false;
                }, 220);
            },
            closeFromServer() {
                if (this.closing) {
                    return;
                }

                this.closing = true;
                this.isOpen = false;

                window.setTimeout(() => {
                    this.unlockBody();
                    this.activeMenu = '';

                    if (this.previousFocus && typeof this.previousFocus.focus === 'function') {
                        this.$nextTick(() => this.previousFocus.focus());
                    }

                    this.closing = false;
                }, 220);
            },
            destroy() {
                this.cleanupCallbacks.forEach((cleanup) => cleanup());
                this.cleanupCallbacks = [];
                this.unlockBody();
            }
        }"
        x-init="
            if (serverOpen) {
                open();
            }

            $watch('serverOpen', value => {
                if (value && ! isOpen) {
                    open(activeMenu);
                }

                if (! value && isOpen && ! closing) {
                    closeFromServer();
                }
            });

            const registerCloseListener = (eventName) => {
                const closeHandler = () => {
                    if (isOpen) {
                        close();
                    }
                };

                window.addEventListener(eventName, closeHandler);
                cleanupCallbacks.push(() => window.removeEventListener(eventName, closeHandler));

                if (window.Livewire && typeof window.Livewire.on === 'function') {
                    const cleanup = window.Livewire.on(eventName, closeHandler);

                    if (typeof cleanup === 'function') {
                        cleanupCallbacks.push(cleanup);
                    }
                }
            };

            if ($wire && typeof $wire.on === 'function') {
                $wire.on('openSideMenu', payload => {
                    const menu = payload?.showSideMenu ?? payload?.detail?.showSideMenu ?? payload?.[0]?.showSideMenu ?? '';
                    open(menu);
                });

                if (Array.isArray(closeEvents)) {
                    closeEvents.forEach((eventName) => {
                        $wire.on(eventName, () => {
                            if (isOpen) {
                                close();
                            }
                        });

                        registerCloseListener(eventName);
                    });
                }
            }
        "
        class="fixed inset-0 z-[100] !m-0 overflow-hidden"
        x-show="isOpen || closing"
        x-cloak
        aria-labelledby="slide-over-title"
        role="dialog"
        aria-modal="true"
        x-on:keydown.escape.window.prevent.stop="if (isOpen) close()"
        x-on:keydown.tab="handleTab($event)"
    >
        <div class="absolute inset-0 overflow-hidden">
            <button
                type="button"
                class="absolute inset-0 h-full w-full cursor-default bg-zinc-950/35 backdrop-blur-sm"
                x-show="isOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                x-on:click="close()"
                aria-label="{{ __('ui::common.actions.close') }}"
            ></button>

            <div class="fixed inset-y-0 right-0 flex max-w-full sm:pl-10">
                <section
                    class="side-modal-shell pointer-events-auto relative w-screen {{ $sizeClass }}"
                    x-show="isOpen"
                    x-transition:enter="transform transition ease-out duration-300"
                    x-transition:enter-start="translate-x-full opacity-95"
                    x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transform transition ease-in duration-200"
                    x-transition:leave-start="translate-x-0 opacity-100"
                    x-transition:leave-end="translate-x-full opacity-95"
                >
                    <div class="flex h-full flex-col overflow-hidden border-l border-zinc-200 bg-white shadow-2xl">
                        @if($showHeaderClose)
                            <div class="absolute top-6 right-6 z-30">
                                <button
                                    x-ref="closeBtn"
                                    type="button"
                                    @click="close()"
                                    class="inline-flex h-14 w-14 items-center justify-center rounded-2xl border border-zinc-200 bg-white text-zinc-500 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-950 focus:outline-none focus:ring-2 focus:ring-zinc-300"
                                >
                                    <span class="sr-only">{{ __('ui::common.actions.close') }}</span>
                                    <x-icons.default.close-icon size="w-6 h-6" color="text-zinc-500" hover="text-zinc-950"></x-icons.default.close-icon>
                                </button>
                            </div>
                        @endif

                        <div class="relative flex-1 overflow-y-auto px-4 py-4 pr-16 sm:px-8 sm:py-8 sm:pr-24" wire:loading.remove>
                            {{ $slot }}
                        </div>

                        <div class="relative flex-1 overflow-y-auto px-4 py-4 sm:px-8 sm:py-8" wire:loading>
                            <div class="w-full space-y-4 animate-pulse">
                                <div class="h-6 w-52 rounded-md bg-zinc-200"></div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="h-10 rounded-lg bg-zinc-200"></div>
                                    <div class="h-10 rounded-lg bg-zinc-200"></div>
                                    <div class="h-10 rounded-lg bg-zinc-200"></div>
                                    <div class="h-10 rounded-lg bg-zinc-200"></div>
                                </div>
                                <div class="h-24 rounded-lg bg-zinc-200"></div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="h-10 rounded-lg bg-zinc-200"></div>
                                    <div class="h-10 rounded-lg bg-zinc-200"></div>
                                    <div class="h-10 rounded-lg bg-zinc-200"></div>
                                </div>
                                <div class="h-12 rounded-lg bg-zinc-200"></div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endteleport
