@props([
    'icon' => 'icons.info-circle-icon',
    'title' => null,
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-[28px] bg-[#f5f5f7] px-6 py-8 shadow-[inset_0_1px_0_rgba(255,255,255,0.75),0_10px_22px_rgba(0,0,0,0.035)]']) }}>
    <div class="flex items-start gap-3">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-zinc-400 shadow-sm">
            <x-dynamic-component :component="$icon" size="w-5 h-5" />
        </span>
        <div class="min-w-0">
            @if (filled($title))
                <p class="text-sm font-semibold text-zinc-800">{{ $title }}</p>
            @endif
            @if (filled($message))
                <p class="mt-1 text-sm leading-6 text-zinc-500">{{ $message }}</p>
            @endif
        </div>
    </div>
</div>
