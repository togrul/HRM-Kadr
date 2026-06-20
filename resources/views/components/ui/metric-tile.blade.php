@props([
    'label',
    'value' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-zinc-200 bg-zinc-50/80 px-4 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]']) }}>
    <x-ui.field-label as="div" class="tracking-tight">{{ $label }}</x-ui.field-label>
    <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950">{{ $value ?? $slot }}</p>
</div>
