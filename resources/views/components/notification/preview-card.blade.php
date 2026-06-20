@props([
    'title' => null,
    'subjectLabel' => null,
    'subject' => null,
    'bodyLabel' => null,
    'body' => null,
    'meta' => [],
    'audience' => [],
    'fallback' => '—',
    'titleClass' => '',
    'bodyClass' => '',
])

@php
    $hasExtra = trim((string) $slot) !== '';
    $subjectText = filled($subject) ? $subject : $fallback;
    $bodyText = filled($body) ? $body : $fallback;
@endphp

<div class="rounded-[1.45rem] border border-zinc-200 bg-white p-4 shadow-[0_12px_28px_rgba(15,23,42,0.045)]">
    @if (filled($title))
        <p class="{{ trim('text-sm font-semibold tracking-tight text-zinc-950 '.$titleClass) }}">{{ $title }}</p>
    @endif

    @if (filled($subjectLabel))
        <div class="@if(filled($title)) mt-4 @endif space-y-2">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ $subjectLabel }}</p>
            <p class="break-words text-[1.05rem] font-semibold leading-8 tracking-tight text-zinc-950">{{ $subjectText }}</p>
        </div>
    @endif

    @if (filled($bodyLabel))
        <div class="@if(filled($subjectLabel)) my-4 h-px bg-zinc-200 @endif"></div>
        <div class="space-y-2">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ $bodyLabel }}</p>
            <p class="{{ trim('break-words text-[15px] leading-7 text-zinc-700 '.$bodyClass) }}">{{ $bodyText }}</p>
        </div>
    @endif

    @if ($hasExtra)
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif

    @if ($meta !== [])
        <div class="mt-5 flex flex-wrap gap-2">
            @foreach ($meta as $metaItem)
                <x-notification.chip mode="muted">{{ $metaItem }}</x-notification.chip>
            @endforeach
        </div>
    @endif

    @if ($audience !== [])
        <div class="mt-4 border-t border-zinc-200 pt-4">
            <div class="flex flex-wrap gap-2">
                @foreach ($audience as $audienceItem)
                    <x-notification.chip mode="sky">{{ $audienceItem }}</x-notification.chip>
                @endforeach
            </div>
        </div>
    @endif
</div>
