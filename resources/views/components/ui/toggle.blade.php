@props([
    'disabled' => false,
])

{{-- Switch control of the settings screens: a real checkbox, so wire:model and the keyboard work. --}}
<label @class(['relative inline-flex shrink-0 items-center', 'cursor-pointer' => ! $disabled, 'opacity-50' => $disabled])>
    <input type="checkbox" @disabled($disabled) {{ $attributes->merge(['class' => 'peer sr-only']) }} />
    <span class="h-6 w-11 rounded-full bg-[#e4e4e7] transition after:absolute after:left-[3px] after:top-[3px] after:h-[18px] after:w-[18px] after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:bg-ink peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-ink/20"></span>
</label>
