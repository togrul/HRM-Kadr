@props([
    'mode' => 'secondary',
    'icon' => null,
    'iconPosition' => 'inline-start',
    'as' => 'span',
])

@php
    $tag = in_array($as, ['span', 'a', 'button', 'div'], true) ? $as : 'span';

    $variant = strtolower((string) $mode);

    $baseClasses = 'h-5 gap-1 rounded-4xl border border-transparent px-2 py-0.5 text-xs font-medium transition-all has-data-[icon=inline-end]:pr-1.5 has-data-[icon=inline-start]:pl-1.5 [&>svg]:size-3! inline-flex items-center justify-center w-fit whitespace-nowrap shrink-0 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive overflow-hidden group/badge';

    $modeClasses = match ($variant) {
        // Default (requested exact shadcn-like secondary style)
        'secondary' => 'bg-gray-100 text-gray-900 [a]:hover:bg-gray-100/80',

        // Color modes (requested palette)
        'blue' => 'bg-blue-100 text-blue-700 [a]:hover:bg-blue-200',
        'green' => 'bg-emerald-100 text-emerald-700 [a]:hover:bg-emerald-200',
        'sky' => 'bg-sky-100 text-sky-700 [a]:hover:bg-sky-200',
        'purple' => 'bg-violet-100 text-violet-700 [a]:hover:bg-violet-200',
        'red' => 'bg-rose-100 text-rose-700 [a]:hover:bg-rose-200',

        default => 'bg-gray-100 text-gray-900 [a]:hover:bg-gray-100/80',
    };
@endphp

<{{ $tag }}
    data-slot="badge"
    data-variant="{{ $variant }}"
    @if($icon) data-icon="{{ $iconPosition }}" @endif
    {{ $attributes->merge(['class' => $baseClasses . ' ' . $modeClasses]) }}
>
    @if($icon && $iconPosition === 'inline-start')
        <x-dynamic-component :component="$icon" />
    @endif

    {{ $slot }}

    @if($icon && $iconPosition === 'inline-end')
        <x-dynamic-component :component="$icon" />
    @endif
</{{ $tag }}>
