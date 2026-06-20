@props([
    'label',
])

<button
    type="button"
    {{ $attributes->merge(['class' => 'inline-flex h-12 min-w-[7.5rem] items-center justify-center rounded-xl border border-zinc-950 bg-zinc-950 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-zinc-300 active:bg-zinc-900']) }}
    aria-label="{{ $label }}"
    title="{{ $label }}"
>
    {{ $slot }}
</button>
