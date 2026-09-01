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

<div {{ $attributes->merge(['class' => $overflowClass.' rounded-2xl flex flex-col border border-hairline bg-white shadow-card']) }}>
    <div class="flex items-center justify-between px-3 py-2.5 flex-none">
        <div class="hrm-eyebrow w-full">
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
        <div class="h-full rounded-xl border border-hairline bg-white {{ $bodyClass }}">
            <div class="{{ $contentClass }}">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
