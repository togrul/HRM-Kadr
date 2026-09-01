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

<div {{ $attributes->merge(['class' => 'flex min-h-[calc(100vh-1.5rem)] flex-col overflow-hidden rounded-2xl border border-hairline bg-white shadow-card']) }}>
    @if ($title)
        <div class="border-b border-hairline px-3.5 py-3">
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
</div>
