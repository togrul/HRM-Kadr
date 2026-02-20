@props([
    'title' => null,
    'icon' => null,
    'iconProps' => [],
    'bodyClass' => '',
    'contentClass' => 'p-4',
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100/80 shadow-[0_1px_2px_rgba(16,24,40,0.04)]']) }}>
    <div class="flex min-h-[52px] items-center justify-between px-4 py-2.5">
        <div class="text-base font-medium text-slate-600 tracking-tight">
            {{ $title }}
        </div>

        @if (filled($icon))
            <x-dynamic-component
                :component="$icon"
                :color="$iconProps['color'] ?? 'text-zinc-500'"
                :hover="$iconProps['hover'] ?? 'text-zinc-700'"
                :size="$iconProps['size'] ?? 'w-5 h-5'"
            />
        @endif
    </div>

    <div class="px-1 pb-1">
        <div class="rounded-xl border border-zinc-200 bg-white {{ $bodyClass }}">
            <div class="{{ $contentClass }}">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
