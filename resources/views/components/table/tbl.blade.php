@props([
    'headers',
    'divide' => true,
    'title' => null,
    'bordered' => false,
])

@php
    use Illuminate\Support\Str;
@endphp

{{-- Flush table: the surrounding content card already supplies the frame. --}}
<div class="bg-white">
    @if (filled($title))
        <div class="border-b border-hairline-subtle px-4 py-2.5 sm:px-5">
            <h3 class="hrm-eyebrow">{{ $title }}</h3>
        </div>
    @endif

    <div
        x-data="{
            overflowing: false,
            atStart: true,
            atEnd: true,
            observer: null,
            measure() {
                const scroller = this.$refs.scroller;
                const max = Math.max(0, scroller.scrollWidth - scroller.clientWidth);
                this.overflowing = max > 4;
                this.atStart = scroller.scrollLeft <= 2;
                this.atEnd = scroller.scrollLeft >= max - 2;
            },
            nudge(direction) {
                this.$refs.scroller.scrollBy({ left: direction * Math.max(180, this.$refs.scroller.clientWidth * 0.7), behavior: 'smooth' });
            },
            destroy() { this.observer?.disconnect(); },
        }"
        x-init="$nextTick(() => {
            measure();
            if (window.ResizeObserver) {
                observer = new ResizeObserver(() => measure());
                observer.observe($refs.scroller);
                observer.observe($refs.scroller.querySelector('table'));
            }
        })"
        @resize.window.debounce.150ms="measure()"
    >
        <div x-cloak x-show="overflowing" class="flex items-center justify-end gap-1 border-b border-hairline-subtle px-3 py-1.5 lg:hidden">
            <button type="button" @click="nudge(-1)" :disabled="atStart" aria-label="{{ __('ui::common.pagination.previous') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-[10px] border border-hairline bg-white text-ink-muted transition hover:bg-[#f4f4f5] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 disabled:opacity-35">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button type="button" @click="nudge(1)" :disabled="atEnd" aria-label="{{ __('ui::common.pagination.next') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-[10px] border border-hairline bg-white text-ink-muted transition hover:bg-[#f4f4f5] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 disabled:opacity-35">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
        <div x-ref="scroller" @scroll.passive="measure()" class="hrm-scroll overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'min-w-full w-full p-[5px] pb-0 border-separate border-spacing-0 bg-white text-sm']) }}>
            <thead class="bg-transparent">
                <tr class="align-middle text-xs uppercase">
                    @foreach ($headers as $header)
                      @php
                        $headerLabel = is_array($header) ? (string) ($header['label'] ?? '') : (string) $header;
                        $headerIcon = is_array($header) ? ($header['icon'] ?? null) : null;
                        $headerTitle = is_array($header) ? ($header['title'] ?? null) : null;
                        $headerDayType = is_array($header) ? ($header['day_type'] ?? null) : null;
                        $headerClasses = is_array($header) ? (string) ($header['th_classes'] ?? '') : '';
                        $headerContentClasses = is_array($header) ? (string) ($header['content_classes'] ?? '') : '';
                        $headerDay = is_array($header) ? ($header['day'] ?? null) : null;
                        $isDay = is_array($header)
                            ? (bool) ($header['is_day'] ?? is_numeric($headerLabel))
                            : is_numeric($header);
                        $normalizedHeader = Str::of(strip_tags($headerLabel))->lower()->squish()->toString();
                        $isActionHeader = in_array($normalizedHeader, [
                            'action',
                            'actions',
                            'əməliyyat',
                            'əməliyyatlar',
                        ], true);
                      @endphp
                        @if (! $isActionHeader)
                            <th
                                scope="col"
                                @if($headerTitle) title="{{ $headerTitle }}" @endif
                                @if($headerDayType) data-day-type="{{ $headerDayType }}" @endif
                                @if($headerDay !== null) data-day="{{ $headerDay }}" @endif
                                @class([
                                  'text-left text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-muted whitespace-nowrap bg-white border-b border-hairline ',
                                  'stats-cell-header py-1 px-4' => $bordered,
                                  'py-2.5 px-4' => !$bordered,
                                  'w-10 min-w-10 max-w-10 text-center !px-0 !py-0' => $bordered && $isDay,
                                  $headerClasses => $headerClasses !== '',
                                ])
                            >
                                @if($headerIcon || $headerContentClasses !== '')
                                    <span @class([
                                        'inline-flex items-center justify-center gap-1',
                                        $headerContentClasses => $headerContentClasses !== '',
                                    ])>
                                        @if($headerIcon)
                                            <x-dynamic-component :component="$headerIcon" size="w-3.5 h-3.5" color="text-current" />
                                        @endif
                                        <span>{{ $headerLabel }}</span>
                                    </span>
                                @else
                                    {{ $headerLabel }}
                                @endif
                            </th>
                        @else
                            <th scope="col" class="relative px-4 py-2.5 bg-white border-b border-hairline"></th>
                        @endif
                    @endforeach
                </tr>
            </thead>

            <tbody @class([
                'bg-white [&_tr]:transition-colors [&_tr:hover]:bg-[#fafafa]',
                '[&_tr>td]:border-b [&_tr>td]:border-hairline-subtle [&_tr:last-child>td]:border-b-0' => $divide,
                '[&_tr>td]:border-r [&_tr>td]:border-hairline-subtle [&_tr>td:last-child]:border-r-0 [&_tr>td.stats-cell]:border-r-0 [&_tr>td]:px-2 [&_tr>td]:py-2' => $bordered,
            ])>
                {{ $slot }}
            </tbody>
        </table>
        </div>
    </div>
</div>
