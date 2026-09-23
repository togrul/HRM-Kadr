@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'block text-[14px] font-medium leading-5 text-ink-soft']) }}>
    {{ $value ?? $slot }}@if ($required)<span aria-hidden="true" class="ml-0.5 text-rose-600">*</span>@endif
</label>
