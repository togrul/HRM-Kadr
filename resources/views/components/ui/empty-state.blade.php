@props([
    'icon' => 'icons.info-circle-icon',
    'title' => null,
    'message' => null,
])

{{-- Quiet, centered empty state: the surrounding card already draws a frame, so a second
     bordered box inside it reads as a stray alert. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-6 text-center']) }}>
    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-hairline bg-[#fafafa] text-ink-faint">
        <x-dynamic-component :component="$icon" size="w-5 h-5" />
    </span>

    @if (filled($title))
        <p class="mt-3 text-[13px] font-medium text-ink">{{ $title }}</p>
    @endif

    @if (filled($message))
        <p class="mt-1 max-w-sm text-[12px] leading-5 text-ink-faint">{{ $message }}</p>
    @endif
</div>
