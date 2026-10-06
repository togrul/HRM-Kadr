@props([
    'number' => null,     // null renders a dot instead of a numbered circle
    'state' => 'upcoming', // completed | active | upcoming
    'count' => null,
])

{{--
    Vertical step row for the contextual panel: the same completed / active / upcoming
    semantics as the wizard's horizontal stepper, in the panel's own visual language.
--}}

@php
    $markerClasses = match ($state) {
        'completed' => 'border-[#059669] bg-[#059669] text-white',
        'active' => 'border-ink bg-white text-ink ring-4 ring-[#f4f4f5]',
        default => 'border-hairline bg-white text-ink-faint',
    };

    $labelClasses = match ($state) {
        'active' => 'font-semibold text-ink',
        'completed' => 'font-medium text-ink-soft',
        default => 'font-medium text-ink-muted',
    };
@endphp

<button
    type="button"
    {{ $attributes->merge(['class' => 'group relative flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition '.($state === 'active' ? 'bg-[#f4f4f5]' : 'hover:bg-[#fafafa]')]) }}
    @if ($state === 'active') aria-current="step" @endif
>
    <span class="flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded-full border text-[12px] font-semibold transition {{ $markerClasses }}">
        @if ($state === 'completed')
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 13 4 4L19 7"/></svg>
        @elseif ($number !== null)
            <span class="hrm-num">{{ $number }}</span>
        @else
            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
        @endif
    </span>

    <span class="min-w-0 flex-1 truncate text-[13.5px] leading-tight {{ $labelClasses }}">{{ $slot }}</span>

    @if ($count !== null)
        <span class="hrm-num shrink-0 rounded-full bg-[#f4f4f5] px-1.5 py-0.5 text-[11px] text-ink-muted group-hover:bg-hairline">{{ $count }}</span>
    @endif
</button>
