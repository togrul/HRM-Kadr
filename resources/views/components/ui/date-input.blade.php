@props([
    'disabled' => false,
    'min' => null,
    'max' => null,
])

@php
    /*
     * The shared date field. It replaces the browser's <input type="date"> (whose look and
     * format — 08/10/2026 or 08.10.2026 — depended on the browser's language) with the app's
     * own calendar: the app locale, Monday-first weeks, DD.MM.YYYY with a typing mask.
     *
     * The bound value stays ISO (Y-m-d), exactly what a native date input produced, so the
     * Livewire properties and validation rules behind it need no change.
     */
    $wireModelKey = collect($attributes->getAttributes())
        ->keys()
        ->first(fn ($key) => str_starts_with((string) $key, 'wire:model'));
    $wireModel = $wireModelKey ? (string) $attributes->get($wireModelKey) : null;
    // A deferred wire:model stays deferred; .live/.blur/.change sync once a whole date is in.
    $syncLive = $wireModelKey !== null && preg_match('/\.(live|blur|change|lazy)/', (string) $wireModelKey) === 1;
    $name = $attributes->get('name');
    $value = (string) ($attributes->get('value') ?? '');
    $hasError = (is_string($wireModel) && $errors->has($wireModel)) || (is_string($name) && $errors->has($name));
    $errorClasses = 'border-rose-300 bg-rose-50';
    $minDate = filled($min) ? \Carbon\Carbon::parse($min)->toDateString() : null;
    $maxDate = filled($max) ? \Carbon\Carbon::parse($max)->toDateString() : null;
    // x-model (an Alpine parent, e.g. the date half of x-ui.datetime-input) binds the ISO value too.
    $alpineModelKey = collect($attributes->getAttributes())
        ->keys()
        ->first(fn ($key) => str_starts_with((string) $key, 'x-model'));
    $fieldAttributes = $attributes->except(array_filter([$wireModelKey, $alpineModelKey, 'name', 'value', 'type', 'min', 'max', 'wire:key']));
@endphp

<div
    class="relative"
    @if ($attributes->has('wire:key')) wire:key="{{ $attributes->get('wire:key') }}" @endif
    x-data="{
        ...window.hrmDateField({ value: @js($value), min: @js($minDate), max: @js($maxDate) }),
        @if ($wireModel)
            @if ($syncLive) iso: @entangle($wireModel).live, @else iso: @entangle($wireModel), @endif
        @endif
    }"
    @if ($alpineModelKey) x-modelable="iso" {{ $alpineModelKey }}="{{ $attributes->get($alpineModelKey) }}" @endif
>
    <input
        type="text"
        x-ref="display"
        data-date-input
        inputmode="numeric"
        maxlength="10"
        autocomplete="off"
        placeholder="{{ $attributes->get('placeholder', __('ui::date.placeholder')) }}"
        x-on:input="onInput($event)"
        x-on:change="onChange()"
        @disabled($disabled)
        @if ($wireModel) data-error-key="{{ $wireModel }}" @endif
        @if ($hasError) aria-invalid="true" data-error-classes="{{ $errorClasses }}" @endif
        {{ $fieldAttributes->except('placeholder')->merge(['class' => \App\Support\Ui\FieldStyles::input('hrm-num '.($hasError ? $errorClasses : ''))]) }}
    />
    @if (! $wireModel && ! $alpineModelKey && filled($name))
        <input type="hidden" name="{{ $name }}" x-bind:value="iso" value="{{ $value }}" />
    @endif
</div>
