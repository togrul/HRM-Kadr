@props([
    'type' => 'search',
    'icon' => null,
])

{{-- Toolbar field. Skin comes from FieldStyles so every screen moves together. --}}
<x-ui.input :type="$type" :icon="$icon" {{ $attributes }} />
