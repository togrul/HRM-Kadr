@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[14px] font-medium leading-5 text-ink-soft']) }}>
    {{ $value ?? $slot }}
</label>
