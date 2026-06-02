@props([
    'titleId',
    'closeAction',
    'closeLabel',
    'width' => '3xl',
])

@php
    $widthClass = match ($width) {
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        default => 'max-w-3xl',
    };
@endphp

@teleport('body')
    <div
        x-data="{
            open: false,
            closing: false,
            previousFocus: null,
            lockBody() {
                document.body.classList.add('overflow-hidden');
                document.documentElement.classList.add('overflow-hidden');
            },
            unlockBody() {
                document.body.classList.remove('overflow-hidden');
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
            close() {
                if (this.closing) {
                    return;
                }

                this.closing = true;
                this.open = false;

                window.setTimeout(() => {
                    this.unlockBody();
                    {!! $closeAction !!};

                    if (this.previousFocus && typeof this.previousFocus.focus === 'function') {
                        this.$nextTick(() => this.previousFocus.focus());
                    }
                }, 220);
            },
            show() {
                this.open = true;
            },
            destroy() {
                this.unlockBody();
            }
        }"
        x-init="
            previousFocus = document.activeElement;
            lockBody();
            $nextTick(() => {
                show();
                $refs.closeButton?.focus();
            });
        "
        x-on:keydown.escape.window.prevent.stop="close()"
        x-on:keydown.tab="handleTab($event)"
        class="fixed inset-0 z-[100] overflow-hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $titleId }}"
    >
        <button
            type="button"
            x-on:click="close()"
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 h-full w-full cursor-default bg-zinc-950/35 backdrop-blur-sm"
            aria-label="{{ $closeLabel }}"
        ></button>

        <section
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full opacity-95"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-95"
            {{ $attributes->class("absolute inset-y-0 right-0 flex h-screen w-full {$widthClass} flex-col border-l border-zinc-200 bg-white shadow-2xl will-change-transform") }}
        >
            {{ $slot }}
        </section>
    </div>
@endteleport
