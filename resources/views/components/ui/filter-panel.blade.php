@props([
    'innerClass' => 'grid grid-cols-1 gap-4 md:grid-cols-3',
])

<section {{ $attributes->merge(['class' => 'rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm']) }}>
    <div class="{{ $innerClass }}">
        {{ $slot }}
    </div>
</section>
