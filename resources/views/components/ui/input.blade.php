@props([
    'type' => 'text',
    'disabled' => false,
    'icon' => null,   // 'search' draws the leading magnifier
])

@php
    $wireModelKey = collect($attributes->getAttributes())
        ->keys()
        ->first(fn ($key) => str_starts_with((string) $key, 'wire:model'));
    $errorKey = $wireModelKey ? $attributes->get($wireModelKey) : $attributes->get('name');
    $hasError = is_string($errorKey) && $errorKey !== '' && $errors->has($errorKey);
    $errorClasses = 'border-rose-300 bg-rose-50';
    $classes = \App\Support\Ui\FieldStyles::input(trim(($icon === 'search' ? 'pl-9 ' : '').($hasError ? $errorClasses : '')));
@endphp

@if ($type === 'date')
    {{-- one calendar app-wide instead of the browser's native date picker --}}
    <x-ui.date-input :disabled="$disabled" {{ $attributes }} />
@elseif ($icon === 'search')
    <div class="relative">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
        </svg>
        <input type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }} />
    </div>
@else
    <input
        type="{{ $type }}"
        @disabled($disabled)
        @if ($hasError) aria-invalid="true" data-error-classes="{{ $errorClasses }}" data-error-key="{{ $errorKey }}" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    />
@endif
