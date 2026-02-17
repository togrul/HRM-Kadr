@props([
    'size' => 'large',
    'showHeaderClose' => true,
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

<div
    x-data="{
        isOpen: @entangle('isSideModalOpen').live,
        closeEvents: @js($closeOnEvents),
        previousFocus: null,
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
        onOpen() {
            this.previousFocus = document.activeElement;
            this.lockBody();
            this.$nextTick(() => {
                if (this.$refs.closeBtn) {
                    this.$refs.closeBtn.focus();
                }
            });
        },
        onClose() {
            this.unlockBody();
            if (this.previousFocus && typeof this.previousFocus.focus === 'function') {
                this.$nextTick(() => this.previousFocus.focus());
            }
        },
        close() {
            if ($wire && typeof $wire.call === 'function') {
                $wire.call('closeSideMenu');
            }
            this.isOpen = false;
        }
    }"
    x-init="
        if (isOpen) onOpen();

        $watch('isOpen', value => {
            if (value) {
                onOpen();
            } else {
                onClose();
            }
        });

        if ($wire && typeof $wire.on === 'function') {
            $wire.on('openSideMenu', () => {
                isOpen = true;
            });

            if (Array.isArray(closeEvents)) {
                closeEvents.forEach((eventName) => {
                    $wire.on(eventName, () => {
                        if (isOpen) {
                            close();
                        }
                    });
                });
            }
        }
    "
    class="fixed inset-0 z-[100] !m-0"
    x-show="isOpen"
    x-cloak
    aria-labelledby="slide-over-title"
    role="dialog"
    aria-modal="true"
    x-on:keydown.escape.window="if (isOpen) close()"
>
    <div class="absolute inset-0 overflow-hidden">
        <div
            class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]"
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:click="close()"
            aria-hidden="true"
        ></div>

        <div class="fixed inset-y-0 right-0 flex max-w-full sm:pl-10">
            <section
                class="pointer-events-auto relative w-screen {{ $sizeClass }}"
                x-show="isOpen"
                x-transition:enter="transform transition ease-out duration-250"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
                <div class="flex h-full flex-col overflow-hidden rounded-l-3xl border border-slate-200 bg-white shadow-2xl">
                    @if($showHeaderClose)
                        <div class="absolute top-3 right-3 z-30">
                            <button
                                x-ref="closeBtn"
                                type="button"
                                @click="close()"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white/95 text-slate-500 shadow-sm backdrop-blur transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300"
                            >
                                <span class="sr-only">{{ __('Close') }}</span>
                                <x-icons.remove-icon size="w-6 h-6" color="text-slate-500" hover="text-slate-900"></x-icons.remove-icon>
                            </button>
                        </div>
                    @endif

                    <div class="relative flex-1 overflow-y-auto px-4 py-4 pr-14 sm:px-6 sm:pr-16" wire:loading.remove>
                        {{ $slot }}
                    </div>

                    <div class="relative flex-1 overflow-y-auto px-4 py-4 sm:px-6" wire:loading>
                        <div class="w-full space-y-4 animate-pulse">
                            <div class="h-6 w-52 rounded-md bg-slate-200"></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="h-10 rounded-lg bg-slate-200"></div>
                                <div class="h-10 rounded-lg bg-slate-200"></div>
                                <div class="h-10 rounded-lg bg-slate-200"></div>
                                <div class="h-10 rounded-lg bg-slate-200"></div>
                            </div>
                            <div class="h-24 rounded-lg bg-slate-200"></div>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="h-10 rounded-lg bg-slate-200"></div>
                                <div class="h-10 rounded-lg bg-slate-200"></div>
                                <div class="h-10 rounded-lg bg-slate-200"></div>
                            </div>
                            <div class="h-12 rounded-lg bg-slate-200"></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
