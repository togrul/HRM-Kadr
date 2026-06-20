{{--
    Global confirmation modal — a single, premium (shadcn / Apple-style) replacement for
    the browser's native wire:confirm / window.confirm dialog. Mounted once in the app
    layout; teleported to <body> so it always sits above every stacking context.

    Trigger from any button with an Alpine $dispatch carrying the action to run on
    confirm (a closure, so it keeps the originating component's $wire):

        <button type="button"
            x-on:click="$dispatch('confirm-action', {
                title: 'Əmr',
                message: 'Əmr ləğv edilsin?',
                confirmText: 'Ləğv et',
                tone: 'rose',                       // rose | emerald | amber | teal | zinc
                run: () => $wire.cancelOrder('123'),
            })">…</button>
--}}
<div
    x-data="{
        show: false,
        title: '',
        message: '',
        confirmText: '',
        cancelText: @js(__('ui::common.actions.close')),
        defaultCancelText: @js(__('ui::common.actions.close')),
        tone: 'rose',
        run: null,
        tones: {
            rose:    { btn: 'bg-rose-600 hover:bg-rose-500 focus-visible:ring-rose-300',       chip: 'bg-rose-50 text-rose-600 ring-rose-100' },
            emerald: { btn: 'bg-emerald-600 hover:bg-emerald-500 focus-visible:ring-emerald-300', chip: 'bg-emerald-50 text-emerald-600 ring-emerald-100' },
            amber:   { btn: 'bg-amber-500 hover:bg-amber-400 focus-visible:ring-amber-300',     chip: 'bg-amber-50 text-amber-600 ring-amber-100' },
            teal:    { btn: 'bg-teal-600 hover:bg-teal-500 focus-visible:ring-teal-300',        chip: 'bg-teal-50 text-teal-600 ring-teal-100' },
            zinc:    { btn: 'bg-zinc-900 hover:bg-zinc-800 focus-visible:ring-zinc-400',        chip: 'bg-zinc-100 text-zinc-700 ring-zinc-200' },
        },
        tone3() { return this.tones[this.tone] || this.tones.rose; },
        openModal(detail) {
            this.title = detail.title || '';
            this.message = detail.message || '';
            this.confirmText = detail.confirmText || 'OK';
            this.cancelText = detail.cancelText || this.defaultCancelText;
            this.tone = detail.tone || 'rose';
            this.run = typeof detail.run === 'function' ? detail.run : null;
            this.show = true;
            this.$nextTick(() => this.$refs.confirmBtn && this.$refs.confirmBtn.focus());
        },
        closeModal() { this.show = false; this.run = null; },
        accept() {
            const fn = this.run;
            this.show = false;
            this.run = null;
            if (typeof fn === 'function') fn();
        },
    }"
    @confirm-action.window="openModal($event.detail)"
>
    <template x-teleport="body">
        <div x-show="show" x-cloak class="fixed inset-0 z-[120]" role="dialog" aria-modal="true"
             x-on:keydown.escape.window="show && closeModal()">

            {{-- Backdrop --}}
            <div x-show="show"
                 x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0 bg-zinc-950/40 backdrop-blur-[3px]"
                 x-on:click="closeModal()" aria-hidden="true"></div>

            {{-- Centering wrapper --}}
            <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6">
                <div x-show="show"
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                     class="relative w-full max-w-[420px] overflow-hidden rounded-[22px] bg-white shadow-[0_24px_70px_-16px_rgba(15,23,42,0.45)] ring-1 ring-zinc-950/[0.06]">

                    {{-- Close --}}
                    <button type="button" x-on:click="closeModal()"
                            class="absolute right-3.5 top-3.5 inline-flex h-8 w-8 items-center justify-center rounded-full text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300"
                            aria-label="{{ __('ui::common.actions.close') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>

                    <div class="px-6 pb-5 pt-7">
                        {{-- Icon chip --}}
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl ring-1 ring-inset transition-colors"
                             :class="tone3().chip">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>

                        {{-- Title + message --}}
                        <h3 class="mt-4 text-[17px] font-semibold leading-6 tracking-[-0.01em] text-zinc-900" x-text="title"></h3>
                        <p class="mt-1.5 text-[13.5px] leading-relaxed text-zinc-500" x-text="message"></p>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-2.5 border-t border-zinc-100 bg-zinc-50/60 px-6 py-4">
                        <button type="button" x-on:click="closeModal()"
                                class="inline-flex h-9 items-center justify-center rounded-xl border border-zinc-200 bg-white px-4 text-[13px] font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-300">
                            <span x-text="cancelText"></span>
                        </button>
                        <button type="button" x-ref="confirmBtn" x-on:click="accept()"
                                :class="tone3().btn"
                                class="inline-flex h-9 items-center justify-center rounded-xl px-4 text-[13px] font-semibold text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">
                            <span x-text="confirmText"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
