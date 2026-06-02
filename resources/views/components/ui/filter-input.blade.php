@props([
    'type' => 'search',
    'icon' => null,
])

<div class="relative">
    @if ($icon === 'search')
        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400 transition group-focus-within:text-zinc-700" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
        </svg>
    @endif

    <input
        type="{{ $type }}"
        {{ $attributes->merge(['class' => trim(($icon === 'search' ? 'pl-11 ' : 'px-4 ').'h-12 w-full rounded-2xl border-0 bg-zinc-100 pr-4 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.7)] outline-none ring-0 transition placeholder:font-medium placeholder:text-zinc-400 hover:bg-zinc-100/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-zinc-200')]) }}
    />
</div>
