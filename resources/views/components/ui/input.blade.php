@props([
    'type' => 'text',
    'disabled' => false,
    'icon' => null,   // 'search' draws the leading magnifier
])

@php $classes = \App\Support\Ui\FieldStyles::input($icon === 'search' ? 'pl-9' : ''); @endphp

@if ($icon === 'search')
    <div class="relative">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
        </svg>
        <input type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }} />
    </div>
@else
    <input type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }} />
@endif
