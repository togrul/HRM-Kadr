@props([
    'number' => null,     // null renders a dot instead of a numbered circle
    'state' => 'upcoming', // completed | active | upcoming
    'count' => null,
    'pending' => null,     // optional Alpine expression: true while this step is being opened
    'live' => null,        // optional Alpine expression returning the state; follows it client-side
])

{{--
    Vertical step row for the contextual panel: the same completed / active / upcoming
    semantics as the wizard's horizontal stepper, in the panel's own visual language.
    With `live`, the server-rendered state is only the starting point: the classes,
    the check mark and aria-current follow the Alpine expression without a re-render.
--}}

@php
    $markerMap = [
        'completed' => 'border-[#059669] bg-[#059669] text-white',
        'active' => 'border-ink bg-white text-ink ring-4 ring-[#f4f4f5]',
        'upcoming' => 'border-hairline bg-white text-ink-faint',
    ];

    $labelMap = [
        'active' => 'font-semibold text-ink',
        'completed' => 'font-medium text-ink-soft',
        'upcoming' => 'font-medium text-ink-muted',
    ];

    $rowMap = [
        'active' => 'bg-[#f4f4f5]',
        'completed' => 'hover:bg-[#fafafa]',
        'upcoming' => 'hover:bg-[#fafafa]',
    ];

    $state = array_key_exists($state, $markerMap) ? $state : 'upcoming';

    // Alpine: the state map picked by the live expression, as a plain class string.
    $bind = fn (array $map): string => '('.json_encode($map).')[('.$live.')]';
@endphp

<button
    type="button"
    {{ $attributes->merge(['class' => 'group relative flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-left transition '.($live ? '' : $rowMap[$state])]) }}
    @if ($live)
        x-bind:class="{{ $bind($rowMap) }}"
        x-bind:aria-current="({{ $live }}) === 'active' ? 'step' : null"
    @endif
    @if ($state === 'active') aria-current="step" @endif
>
    <span
        class="flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded-full border text-[12px] font-semibold transition {{ $live ? '' : $markerMap[$state] }}"
        @if ($live) x-bind:class="{{ $bind($markerMap) }}" @endif
    >
        @if ($pending)
            <svg x-cloak x-show="{{ $pending }}" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z" fill="currentColor"></path></svg>
        @endif
        <span class="contents" @if ($pending) x-show="!({{ $pending }})" @endif>
            @if ($live)
                <svg x-show="({{ $live }}) === 'completed'" @if ($state !== 'completed') x-cloak @endif class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 13 4 4L19 7"/></svg>
                <span x-show="({{ $live }}) !== 'completed'" @if ($state === 'completed') x-cloak @endif class="hrm-num">{{ $number }}</span>
            @elseif ($state === 'completed')
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 13 4 4L19 7"/></svg>
            @elseif ($number !== null)
                <span class="hrm-num">{{ $number }}</span>
            @else
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
            @endif
        </span>
    </span>

    <span
        class="min-w-0 flex-1 truncate text-[13.5px] leading-tight {{ $live ? '' : $labelMap[$state] }}"
        @if ($live) x-bind:class="{{ $bind($labelMap) }}" @endif
    >{{ $slot }}</span>

    @if ($count !== null)
        <span class="hrm-num shrink-0 rounded-full bg-[#f4f4f5] px-1.5 py-0.5 text-[11px] text-ink-muted group-hover:bg-hairline">{{ $count }}</span>
    @endif
</button>
