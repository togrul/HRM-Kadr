@props([
    'title' => null,
    'icon' => null,
    'iconProps' => [],
    'clip' => false,
    'bodyClass' => '',
    'contentClass' => 'p-4',
])

@php
    $overflowClass = $clip ? 'overflow-hidden' : 'overflow-visible';
@endphp

<div {{ $attributes->merge(['class' => $overflowClass.' rounded-xl flex flex-col border border-zinc-200 bg-zinc-100/80 shadow-[0_1px_2px_rgba(16,24,40,0.04)]']) }}>
    <div class="flex items-center justify-between px-[5px] py-2.5 flex-none">
        <div class="text-xs uppercase font-semibold text-slate-600 tracking-tight w-full">
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

    <div class="px-1 pb-1 flex-1">
        <div class="h-full rounded-lg border border-zinc-200 bg-white {{ $bodyClass }}">
            <div class="{{ $contentClass }}">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
