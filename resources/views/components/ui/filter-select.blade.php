@props([
    'label',
    'options' => [],
    'searchModel',
    'placeholder' => '---',
    'loadOnFocus' => null,
    'loadOnOpen' => null,
])

@php
    $resolvedLoadOnOpen = $loadOnOpen ?? $loadOnFocus;
@endphp

<div class="flex flex-col">
    <x-ui.select-dropdown
        :label="$label"
        :placeholder="$placeholder"
        mode="gray"
        :load-on-open="$resolvedLoadOnOpen"
        :search-model="$searchModel"
        :search-placeholder="__('Search...')"
        class="w-full"
        :model="$options"
        {{ $attributes }}
    />
</div>
