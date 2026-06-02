@props([
    'label',
])

<label {{ $attributes->merge(['class' => 'group flex min-w-0 flex-col gap-2']) }}>
    <span class="text-sm font-semibold tracking-tight text-zinc-500 transition group-focus-within:text-zinc-800">{{ $label }}</span>
    {{ $slot }}
</label>
