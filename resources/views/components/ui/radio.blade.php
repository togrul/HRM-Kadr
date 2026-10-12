@props([
    'model',
    'value',
    'label'
])

<label class="hrm-radio">
    <input type="radio" name="{{ $model }}" wire:model="{{ $model }}" value="{{ $value }}">
    <span>{{ __($label) }}</span>
</label>
