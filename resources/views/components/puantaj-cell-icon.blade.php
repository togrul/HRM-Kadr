@props([
    'name',
    'color',
    'hover',
])

{{-- The icon's own root <svg>; its paths come from the <symbol> the puantaj grid defines once per render. --}}
<x-icons.root animated="true" x-show="true" size="w-4 h-4" :color="$color" :hover="$hover"><use href="#{{ str_replace('icons.', 'puantaj-', $name) }}"></use></x-icons.root>
