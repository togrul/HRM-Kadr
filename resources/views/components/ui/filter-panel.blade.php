@props([
    'innerClass' => 'grid grid-cols-1 gap-4 md:grid-cols-3',
])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-hairline bg-white p-4 shadow-card']) }}>
    <div class="{{ $innerClass }}">
        {{ $slot }}
    </div>
</section>
