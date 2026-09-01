@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[12.5px] font-medium text-ink-soft']) }}>
    {{ $value ?? $slot }}
</label>
