@props([
    'label',
])

{{-- A reset is a secondary step: a quiet ghost control, like x-filter.reset. --}}
<button
    type="button"
    {{ $attributes->merge(['class' => 'inline-flex h-10 items-center justify-center rounded-[10px] px-3 text-[13px] font-medium text-ink-muted transition hover:bg-[#f4f4f5] hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400']) }}
    aria-label="{{ $label }}"
    title="{{ $label }}"
>
    {{ $slot }}
</button>
