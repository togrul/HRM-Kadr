@props([
    'rows' => 4,
])

<textarea
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => 'min-h-32 w-full rounded-2xl border-0 bg-zinc-100 px-4 py-3 text-sm font-semibold leading-6 text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.7)] outline-none ring-0 transition placeholder:font-medium placeholder:text-zinc-400 hover:bg-zinc-100/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-zinc-200']) }}
>{{ $slot }}</textarea>
