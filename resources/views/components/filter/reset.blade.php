@props([
    'active' => false,   // any filter differs from its default
    'action' => 'resetFilter',
])

{{-- Filter reset: a quiet ghost control that only appears once there is something to reset. --}}
@if ($active)
    <button
        type="button"
        wire:click="{{ $action }}"
        wire:loading.attr="disabled"
        wire:target="{{ $action }}"
        {{ $attributes->merge(['class' => 'inline-flex h-10 shrink-0 items-center gap-1.5 rounded-[10px] px-3 text-[13px] font-medium text-ink-muted transition hover:bg-[#f4f4f5] hover:text-ink']) }}
    >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        <span>{{ $slot->isEmpty() ? __('ui::common.actions.reset_filters') : $slot }}</span>
    </button>
@endif
