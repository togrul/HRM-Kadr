@props([
    'rows',
    'filtered' => false,   // the list is empty because of an active filter
    'hint' => null,
    'resettable' => true,  // false when the filters live in a parent component
])

{{--
    Empty table state: a small icon, one sentence and the next step. When a filter
    emptied the list, the way out is clearing it; otherwise the page may pass its
    create action through the `action` slot.
--}}
@php
    $title = $filtered
        ? __('ui::common.empty.filtered_title')
        : ($slot->hasActualContent() ? trim((string) $slot) : __('ui::common.empty.title'));
    $hint = $filtered ? __('ui::common.empty.filtered_hint') : $hint;
    $hasAction = isset($action) && $action->hasActualContent();
@endphp

<tr>
    <td colspan="{{ $rows }}">
        <div class="flex flex-col items-center px-6 py-12 text-center">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl border border-hairline bg-[#fafafa] text-ink-faint" aria-hidden="true">
                @if ($filtered)
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8.5 11h5"/></svg>
                @else
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                @endif
            </span>

            <p class="mt-3 text-[14px] font-semibold tracking-[-0.01em] text-ink">{{ $title }}</p>

            @if (filled($hint))
                <p class="mt-1 max-w-sm text-[12.5px] leading-snug text-ink-faint">{{ $hint }}</p>
            @endif

            @if (($filtered && $resettable) || $hasAction)
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    @if ($filtered && $resettable)
                        <x-filter.reset :active="true" class="border border-hairline" />
                    @endif
                    @if ($hasAction)
                        {{ $action }}
                    @endif
                </div>
            @endif
        </div>
    </td>
</tr>
