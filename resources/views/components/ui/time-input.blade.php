@props([
    'disabled' => false,
    'min' => null,
    'max' => null,
    'step' => null, // seconds, like the native attribute: 900 moves ↑/↓ by 15 minutes
])

@php
    /*
     * The shared time field. It replaces the browser's time input (12 or 24 hours and a
     * different spinner on every operating system) with a 24-hour HH:MM field: a typing
     * mask, ↑/↓ by step, and a list of times in the app's own style.
     *
     * The bound value stays HH:MM, exactly what the native input produced, so the Livewire
     * properties and validation rules behind it need no change. Bind it with wire:model
     * (any modifier) or x-model; a plain name posts through a hidden input.
     */
    $wireModelKey = collect($attributes->getAttributes())
        ->keys()
        ->first(fn ($key) => str_starts_with((string) $key, 'wire:model'));
    $wireModel = $wireModelKey ? (string) $attributes->get($wireModelKey) : null;
    // A deferred wire:model stays deferred; .live/.blur/.change sync once a whole time is in.
    $syncLive = $wireModelKey !== null && preg_match('/\.(live|blur|change|lazy)/', (string) $wireModelKey) === 1;
    $alpineModelKey = collect($attributes->getAttributes())
        ->keys()
        ->first(fn ($key) => str_starts_with((string) $key, 'x-model'));
    $name = $attributes->get('name');
    $value = (string) ($attributes->get('value') ?? '');
    $hasError = (is_string($wireModel) && $errors->has($wireModel)) || (is_string($name) && $errors->has($name));
    $errorClasses = 'border-rose-300 bg-rose-50';
    $fieldAttributes = $attributes->except(array_filter([$wireModelKey, $alpineModelKey, 'name', 'value', 'type', 'min', 'max', 'step', 'wire:key', 'placeholder']));
@endphp

<div
    class="relative"
    @if ($attributes->has('wire:key')) wire:key="{{ $attributes->get('wire:key') }}" @endif
    x-data="{
        ...window.hrmTimeField({ value: @js($value), min: @js($min), max: @js($max), step: @js($step !== null ? (int) $step : null) }),
        @if ($wireModel)
            @if ($syncLive) value: @entangle($wireModel).live, @else value: @entangle($wireModel), @endif
        @endif
    }"
    @if ($alpineModelKey) x-modelable="value" {{ $alpineModelKey }}="{{ $attributes->get($alpineModelKey) }}" @endif
    x-on:click.window="if (isOpen && !$el.contains($event.target) && !($refs.panel && $refs.panel.contains($event.target))) isOpen = false"
    x-on:resize.window.debounce.100ms="if (isOpen) reposition()"
    x-on:scroll.window.debounce.50ms="if (isOpen) reposition()"
>
    <input
        type="text"
        x-ref="display"
        data-time-input
        inputmode="numeric"
        maxlength="5"
        autocomplete="off"
        role="combobox"
        aria-autocomplete="none"
        x-bind:aria-expanded="isOpen"
        aria-haspopup="listbox"
        placeholder="{{ $attributes->get('placeholder', __('ui::date.time_placeholder')) }}"
        x-on:input="onInput($event)"
        x-on:change="onChange($event)"
        x-on:keydown="onKeydown($event)"
        x-on:click="if (!isOpen) toggle()"
        @disabled($disabled)
        @if ($wireModel) data-error-key="{{ $wireModel }}" @endif
        @if ($hasError) aria-invalid="true" data-error-classes="{{ $errorClasses }}" @endif
        {{ $fieldAttributes->merge(['class' => \App\Support\Ui\FieldStyles::input('hrm-num pr-9 '.($hasError ? $errorClasses : ''))]) }}
    />
    <button
        type="button"
        tabindex="-1"
        class="absolute inset-y-0 right-0 flex w-9 items-center justify-center text-ink-faint hover:text-ink disabled:cursor-not-allowed"
        aria-label="{{ __('ui::date.time_open') }}"
        x-on:click.stop="toggle(); $refs.display.focus({ preventScroll: true })"
        @disabled($disabled)
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
    </button>

    <template x-teleport="body">
        <ul
            x-ref="panel"
            role="listbox"
            aria-label="{{ __('ui::date.time_list') }}"
            x-show="isOpen" x-cloak x-transition.opacity.duration.100ms
            :style="panelStyles"
            class="hrm-scroll fixed z-[9999] overflow-auto rounded-xl border border-hairline bg-white p-1 text-[14px] shadow-overlay"
        >
            <template x-for="(slot, index) in slots()" :key="slot">
                <li
                    role="option"
                    class="hrm-select-option hrm-num"
                    :data-time-slot="index"
                    :data-active="index === activeIndex ? '' : null"
                    :aria-selected="slot === value"
                    x-on:mousedown.prevent
                    x-on:click.stop="pick(slot)"
                >
                    <span x-text="slot"></span>
                    <span x-show="slot === value" class="hrm-select-check">✓</span>
                </li>
            </template>
        </ul>
    </template>

    @if (! $wireModel && ! $alpineModelKey && filled($name))
        <input type="hidden" name="{{ $name }}" x-bind:value="value" value="{{ $value }}" />
    @endif
</div>
