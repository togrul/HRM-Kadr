@props([
    'rows' => null, // set inside a <tbody>: renders a full-width row spanning this many columns
])

{{--
    Compact empty state for lists inside form cards (employee wizard steps, sub-tables).
    One look everywhere: a dashed hairline box, a small inbox icon and one short sentence.
    Full-page tables keep using <x-table.empty>; this one is for "nothing added yet" blocks.
--}}
@php
    $text = $slot->hasActualContent() ? trim((string) $slot) : __('personnel::common.labels.no_information_added');
@endphp

@if ($rows)
    <tr>
        <td colspan="{{ $rows }}" class="pt-3">
@endif

<div {{ $attributes->class('flex items-center justify-center gap-2 rounded-lg border border-dashed border-hairline bg-[#fafafa] px-4 py-4 text-center') }}>
    <svg class="h-4 w-4 flex-none text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
    <span class="text-[13px] font-medium text-ink-faint">{{ $text }}</span>
</div>

@if ($rows)
        </td>
    </tr>
@endif
