@props([
    'disabled' => false,
    'min' => null,
    'max' => null,
    'step' => null,
])

@php
    /*
     * The shared date-and-time field: the app's date field next to the app's time field,
     * replacing the browser's datetime-local input. It binds ONE value in the format the
     * native input produced (Y-m-d\TH:i, e.g. 2026-10-08T09:30), so the Livewire properties
     * and their "date" validation rules stay as they are. The value is written once both
     * halves are filled; emptying the date empties it.
     */
    $wireModelKey = collect($attributes->getAttributes())
        ->keys()
        ->first(fn ($key) => str_starts_with((string) $key, 'wire:model'));
    $wireModel = $wireModelKey ? (string) $attributes->get($wireModelKey) : null;
    $syncLive = $wireModelKey !== null && preg_match('/\.(live|blur|change|lazy)/', (string) $wireModelKey) === 1;
    $name = $attributes->get('name');
    $value = (string) ($attributes->get('value') ?? '');
    $id = $attributes->get('id');
    $minDate = filled($min) ? \Carbon\Carbon::parse($min)->toDateString() : null;
    $maxDate = filled($max) ? \Carbon\Carbon::parse($max)->toDateString() : null;
    $hasError = (is_string($wireModel) && $errors->has($wireModel)) || (is_string($name) && $errors->has($name));
@endphp

<div
    {{ $attributes->only(['class', 'wire:key'])->merge(['class' => 'grid grid-cols-[minmax(0,1fr)_minmax(0,7.5rem)] gap-2']) }}
    @if ($hasError) data-error-key="{{ $wireModel ?? $name }}" @endif
    x-data="{
        ...window.hrmDateTimeField({ value: @js($value) }),
        @if ($wireModel)
            @if ($syncLive) value: @entangle($wireModel).live, @else value: @entangle($wireModel), @endif
        @endif
    }"
>
    <x-ui.date-input
        x-model="datePart"
        :disabled="$disabled"
        :min="$minDate"
        :max="$maxDate"
        :id="$id"
        :aria-label="__('ui::date.date_part')"
        :aria-invalid="$hasError ? 'true' : null"
    />
    <x-ui.time-input
        x-model="timePart"
        :disabled="$disabled"
        :step="$step"
        :id="$id ? $id.'-time' : null"
        :aria-label="__('ui::date.time_part')"
        :aria-invalid="$hasError ? 'true' : null"
    />
    @if (! $wireModel && filled($name))
        <input type="hidden" name="{{ $name }}" x-bind:value="value" value="{{ $value }}" />
    @endif
</div>
