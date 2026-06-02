<div class="relative">
    <select
        style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none;"
        {{ $attributes->merge(['class' => 'h-12 w-full rounded-2xl border-0 bg-zinc-100 px-4 pr-12 text-sm font-semibold text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.7)] outline-none ring-0 transition hover:bg-zinc-100/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-zinc-200 [&::-ms-expand]:hidden']) }}
    >
        {{ $slot }}
    </select>
    <svg class="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" viewBox="0 0 20 20" aria-hidden="true" fill="currentColor">
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
    </svg>
</div>
