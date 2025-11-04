@props([
    'model',
    'value',
    'label'
])

<label class="inline-flex items-center bg-neutral-100 rounded shadow-sm py-2 px-2">
    <input type="radio" class="form-radio" name="{{ $model }}" wire:model="{{ $model }}" value="{{ $value }}">
    <span class="ml-2 text-sm font-normal">{{ __($label) }}</span>
</label>
