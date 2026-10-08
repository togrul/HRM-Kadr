@props([
    'title' => null,
    'subtitle' => null,
])

{{--
    Contextual panel: ONE continuous card for the whole second column (matching the
    prototype), with internal sections divided by hairlines. Compose it from
    <x-context-panel.section>, <x-context-panel.item>, <x-context-panel.meta> and
    <x-context-panel.progress>; put it in a page's <x-slot name="sidebar">.
--}}

@php
    // An empty panel (title only) starts collapsed — the layout reads this after the page.
    app(\App\Support\Ui\ContextPanelState::class)->record(! $slot->isEmpty() || isset($footer));
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col overflow-hidden rounded-2xl border border-hairline bg-white shadow-card lg:min-h-[calc(100vh-1.5rem)]']) }}>
    @if ($title)
        <div class="border-b border-hairline py-3 pl-3.5 pr-14">
            <p class="truncate text-[13.5px] font-semibold tracking-[-0.02em] text-ink">{{ $title }}</p>
            @if ($subtitle)
                <p class="mt-0.5 truncate text-[11.5px] text-ink-faint">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    <div class="hrm-scroll min-h-0 flex-1 overflow-y-auto">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-hairline bg-[#fafafa] px-3.5 py-3">{{ $footer }}</div>
    @endisset

    {{-- Every panel ends with the way into this page's user guide. It opens in a new tab so
         whatever the user was doing here stays as it was. --}}
    @php
        $guideModule = \App\Support\Docs\GuideRegistry::forCurrentPage();
    @endphp
    @if ($guideModule)
        <a
            href="{{ route('docs.guide', ['focus' => $guideModule]) }}"
            target="_blank"
            rel="noopener"
            class="group flex items-center gap-2.5 border-t border-hairline px-3.5 py-2.5 text-[12.5px] text-ink-muted transition hover:bg-[#fafafa] hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-zinc-400"
        >
            <svg class="h-4 w-4 shrink-0 text-ink-faint transition group-hover:text-ink" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5V5a2 2 0 0 1 2-2h13v16H6.5A2.5 2.5 0 0 0 4 21.5v-2z"/><path d="M8 7h7M8 11h5"/></svg>
            <span class="min-w-0 flex-1">
                <span class="block font-medium">{{ __('ui::common.labels.user_guide') }}</span>
                <span class="block truncate text-[11.5px] text-ink-faint">{{ \App\Support\Docs\GuideRegistry::get($guideModule)['label'] }}</span>
            </span>
            <svg class="h-3.5 w-3.5 shrink-0 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
            <span class="sr-only">({{ __('ui::common.labels.opens_in_new_tab') }})</span>
        </a>
    @endif
</div>
