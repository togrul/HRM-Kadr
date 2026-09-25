@props([
    'title',
    'count' => null,
    'countLabel' => null,
    'breadcrumb' => null,
    'breadcrumbRoot' => null,
    'guideTitle' => null,
    'guideDescription' => null,
    'guideUrl' => null,
    'collapsibleFilters' => false, // phones: the toolbar folds behind a "Filters" button
    'filtersActive' => false,      // marks that button while a filter is applied
])

{{--
    Premium module page header ("Nazik rail" design system). Pair it with the
    x-pill-button component for consistent action buttons. Named slots: icon, actions, stats.
    The default slot renders filters / tabs inside the same card.
--}}

<div class="border-b border-hairline bg-white">
    {{-- header row --}}
    {{-- wraps: when the actions no longer fit beside the title they drop to their own line --}}
    <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:px-5">
        <div class="flex min-w-0 items-center gap-3">
            @isset($icon)
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-ink text-white">
                    {{ $icon }}
                </div>
            @endisset
            <div class="min-w-0 leading-tight">
                <p class="truncate text-[11.5px] text-ink-faint">
                    @php
                        $crumbRoot = $breadcrumbRoot ?? __('ui::common.labels.breadcrumb_root');
                        $crumb = $breadcrumb ?? $title;
                    @endphp
                    {{ $crumbRoot }}
                    @if ($crumb !== $crumbRoot)
                        <span class="px-0.5 text-ink-faint/70">/</span> {{ $crumb }}
                    @endif
                </p>
                <h1 class="truncate text-[18px] font-semibold tracking-[-0.025em] text-ink">{{ $title }}</h1>
            </div>

            @if ($count !== null)
                <span class="hidden shrink-0 items-center gap-1.5 rounded-full border border-hairline bg-[#fafafa] px-2.5 py-1 sm:inline-flex">
                    <span class="hrm-num text-[12.5px] font-semibold text-ink">{{ $count }}</span>
                    @if ($countLabel)
                        <span class="text-[11.5px] text-ink-faint">{{ $countLabel }}</span>
                    @endif
                </span>
            @endif

        </div>

        @if (isset($stats) || isset($actions))
            <div class="flex min-w-0 flex-wrap items-center gap-3 sm:ml-auto sm:justify-end sm:gap-4">
                @isset($stats)
                    <div class="hidden items-center gap-5 md:flex">{{ $stats }}</div>
                @endisset

                @if (isset($stats) && isset($actions))
                    <span class="hidden h-8 w-px shrink-0 bg-hairline md:block"></span>
                @endif

                @isset($actions)
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">{{ $actions }}</div>
                @endisset
            </div>
        @endif
    </div>

    {{-- optional guide strip --}}
    @if ($guideTitle)
        @php $isExternal = $guideUrl && \Illuminate\Support\Str::startsWith($guideUrl, ['http', '#']); @endphp
        <{{ $guideUrl ? 'a' : 'div' }}
            @if ($guideUrl) href="{{ $guideUrl }}" @unless ($isExternal) wire:navigate @endunless @endif
            class="flex items-center gap-2 border-t border-hairline-subtle bg-[#fafafa] px-4 py-2 text-[12px] text-ink-muted sm:px-5 {{ $guideUrl ? 'transition hover:bg-hairline-subtle' : '' }}">
            <svg class="h-3.5 w-3.5 shrink-0 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <span class="font-medium text-ink-soft">{{ $guideTitle }}</span>
            @if ($guideDescription)
                <span class="hidden truncate sm:inline">{{ $guideDescription }}</span>
            @endif
        </{{ $guideUrl ? 'a' : 'div' }}>
    @endif

    {{-- optional body (filters / tabs) rendered inside the same card --}}
    @if (trim($slot) !== '' && $collapsibleFilters)
        {{-- on a phone a full filter toolbar pushes the list below the fold, so it folds away --}}
        <div x-data="{ filtersOpen: false }" class="border-t border-hairline-subtle px-4 py-3 sm:px-5 sm:py-3.5">
            <button
                type="button"
                @click="filtersOpen = ! filtersOpen"
                :aria-expanded="filtersOpen.toString()"
                class="inline-flex h-10 items-center gap-2 rounded-[10px] border border-hairline bg-[#f4f4f5] px-3.5 text-[13.5px] font-semibold text-ink-soft transition hover:bg-[#e4e4e7] sm:hidden"
            >
                <svg class="h-4 w-4 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18M6 12h12M10 19h4"/></svg>
                {{ __('ui::common.labels.filters') }}
                @if ($filtersActive)
                    <span class="h-1.5 w-1.5 rounded-full bg-ink" aria-label="{{ __('ui::common.labels.filters_active') }}"></span>
                @endif
                <svg class="h-3.5 w-3.5 text-ink-faint transition" :class="filtersOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div x-cloak :class="filtersOpen ? 'mt-3 block' : 'hidden'" class="sm:!mt-0 sm:!block">{{ $slot }}</div>
        </div>
    @elseif (trim($slot) !== '')
        <div class="hrm-header-body border-t border-hairline-subtle px-4 py-3.5 sm:px-5">{{ $slot }}</div>
    @endif
</div>
