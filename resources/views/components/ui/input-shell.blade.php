@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'labelAs' => 'label',
    'containerClass' => '',
    'labelClass' => '',
])

<div {{ $attributes->merge(['class' => trim('space-y-2 '.$containerClass)]) }}>
    @if (filled($label))
        <x-ui.field-label :for="$for" :as="$labelAs" :class="$labelClass">{{ $label }}</x-ui.field-label>
    @endif

    {{ $slot }}

    @if (filled($hint))
        <p class="text-xs leading-5 text-zinc-500">{{ $hint }}</p>
    @endif

    @if (filled($error))
        <x-validation>{{ $error }}</x-validation>
    @endif
</div>
