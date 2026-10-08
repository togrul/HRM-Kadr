@props([
    'disabled' => false,
    'label' => '',
    'placeholder' => null,
    'searchable' => null, // null: a search box appears once the list has more than 8 choices
    'triggerClass' => null,
    'direction' => 'auto',
])

@php
    /*
     * The app's select. Views keep writing plain option children (with foreach loops,
     * selected flags, optgroups, disabled options); they are read here and drawn as the
     * shared x-ui.select-dropdown list instead of the operating system's menu.
     *
     * The binding is not re-implemented: a hidden native select keeps every wire:model
     * modifier, wire:change, wire:island and name exactly as written, and the list writes
     * the picked value into it (strings, '' for the empty choice — what the browser sent).
     * Only `multiple` stays a visible native control.
     */
    $isMultiple = $attributes->has('multiple');
    $bindingKeys = collect($attributes->getAttributes())
        ->keys()
        ->filter(fn ($key) => preg_match('/^(wire:(model|change|input|island)|x-model|x-on:change|@change|name$|form$|required$)/', (string) $key) === 1)
        ->values()
        ->all();
    $wireModelKey = collect($bindingKeys)->first(fn ($key) => str_starts_with((string) $key, 'wire:model'));
    $wireModel = $wireModelKey ? (string) $attributes->get($wireModelKey) : null;

    $options = $isMultiple ? [] : \App\Support\Ui\NativeSelectOptions::parse((string) $slot);
    $emptyOption = collect($options)->first(fn (array $option) => $option['id'] === '');
    $choices = collect($options)->reject(fn (array $option) => $option['id'] === '')->values()->all();
    $resolvedPlaceholder = $placeholder ?? ($emptyOption['label'] ?? '---');
    $resolvedSearchable = $searchable ?? count($choices) > 8;
    $selected = collect($options)->first(fn (array $option) => $option['selected']);

    $controlId = $attributes->get('id');
    $instance = $attributes->get('wire:key') ?: 'native-select-'.substr(md5(implode('|', [
        $wireModel ?? (string) $attributes->get('wire:change', ''),
        (string) $controlId,
        (string) $attributes->get('aria-label', ''),
        (string) $attributes->get('class', ''),
        $label,
    ])), 0, 12);
@endphp

@if ($isMultiple)
    <div class="relative">
        <select @disabled($disabled) {{ $attributes->merge(['class' => \App\Support\Ui\FieldStyles::select()]) }}>
            {{ $slot }}
        </select>
    </div>
@else
    <x-ui.select-dropdown
        :label="$label"
        :placeholder="$resolvedPlaceholder"
        :model="$choices"
        :clearable="$emptyOption !== null"
        :searchable="$resolvedSearchable"
        :disabled="$disabled"
        :direction="$direction"
        :instance="$instance"
        :trigger-class="$triggerClass"
        :button-id="$controlId"
        :native-model="$wireModel"
        :native-empty="$emptyOption['id'] ?? ''"
        :selected-label="$selected['label'] ?? null"
        {{ $attributes->except(array_merge($bindingKeys, ['id', 'multiple'])) }}
    >
        <x-slot:native>
            <select hidden tabindex="-1" aria-hidden="true" data-ui-native-select @disabled($disabled) {{ $attributes->only($bindingKeys) }}>
                {{ $slot }}
            </select>
        </x-slot:native>
    </x-ui.select-dropdown>
@endif
