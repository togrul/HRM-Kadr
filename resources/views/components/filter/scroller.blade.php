@props([
    'label' => null,
])

{{--
    Horizontal chip strip that admits it scrolls: edge fades plus arrow buttons that
    only appear when the content actually overflows, and the active chip is scrolled
    into view on load. Wrap a <x-filter.nav> (or plain chips) in it.
--}}

<div
    class="relative flex min-w-0 items-center gap-2"
    x-data="{
        atStart: true,
        atEnd: true,
        overflowing: false,
        measure() {
            const el = this.$refs.strip;
            if (! el) return;
            const max = el.scrollWidth - el.clientWidth;
            this.overflowing = max > 4;
            this.atStart = el.scrollLeft <= 2;
            this.atEnd = el.scrollLeft >= max - 2;
        },
        nudge(direction) {
            const el = this.$refs.strip;
            if (! el) return;
            el.scrollBy({ left: direction * Math.max(160, el.clientWidth * 0.7), behavior: 'smooth' });
        },
    }"
    x-init="
        $nextTick(() => {
            const el = $refs.strip;
            if (! el) return;
            measure();
            el.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest', inline: 'center' });
            if (window.ResizeObserver) {
                new ResizeObserver(() => measure()).observe(el);
            }
        });
    "
    @resize.window.debounce.150ms="measure()"
>
    @if ($label)
        <span class="hidden shrink-0 text-[11.5px] text-ink-faint sm:inline">{{ $label }}</span>
    @endif

    <button
        type="button"
        x-cloak
        x-show="overflowing && ! atStart"
        x-transition.opacity
        @click="nudge(-1)"
        class="absolute left-0 z-10 inline-flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded-full border border-hairline bg-white text-ink-muted shadow-card transition hover:text-ink"
        aria-label="{{ __('ui::common.pagination.previous') }}"
        tabindex="-1"
    >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    </button>

    {{-- fades sit above the strip but must never eat clicks --}}
    <div x-cloak x-show="overflowing && ! atStart" class="pointer-events-none absolute inset-y-0 left-0 z-[5] w-12 bg-gradient-to-r from-white to-transparent"></div>
    <div x-cloak x-show="overflowing && ! atEnd" class="pointer-events-none absolute inset-y-0 right-0 z-[5] w-12 bg-gradient-to-l from-white to-transparent"></div>

    <div
        x-ref="strip"
        @scroll.passive="measure()"
        class="hrm-scroll-hidden min-w-0 flex-1 overflow-x-auto"
    >
        {{ $slot }}
    </div>

    <button
        type="button"
        x-cloak
        x-show="overflowing && ! atEnd"
        x-transition.opacity
        @click="nudge(1)"
        class="absolute right-0 z-10 inline-flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded-full border border-hairline bg-white text-ink-muted shadow-card transition hover:text-ink"
        aria-label="{{ __('ui::common.pagination.next') }}"
        tabindex="-1"
    >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
    </button>
</div>
