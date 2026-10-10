@props([
    'mode' => 'secondary',
    'dot' => false,
    'icon' => null,
    'iconPosition' => 'inline-start',
    'as' => 'span',
])

@php
    $tag = in_array($as, ['span', 'a', 'button', 'div'], true) ? $as : 'span';

    $variant = strtolower((string) $mode);

    $baseClasses = 'h-5 gap-1 rounded-full border border-transparent px-2 py-0.5 text-[11px] font-medium transition-all has-data-[icon=inline-end]:pr-1.5 has-data-[icon=inline-start]:pl-1.5 [&>svg]:size-3! inline-flex items-center justify-center w-fit max-w-full whitespace-nowrap shrink-0 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive overflow-hidden group/badge';

    // A dotted badge is a status value: it takes the uppercase status chip (.hrm-badge);
    // a plain one labels free text (scopes, descriptions) and keeps its case.
    if ($dot) {
        $baseClasses = 'hrm-badge max-w-full overflow-hidden [&>svg]:size-3! [&>svg]:pointer-events-none';
    }

    // Design-system chip palette (see the Colors token table).
    $modeClasses = match ($variant) {
        'secondary' => 'bg-[#f4f4f5] text-[#3f3f46] [a]:hover:bg-hairline',

        'blue', 'sky', 'info' => 'bg-[#f0f9ff] text-[#0369a1] [a]:hover:bg-sky-100',
        'green', 'emerald', 'success' => 'bg-[#ecfdf5] text-[#047857] [a]:hover:bg-emerald-100',
        'purple', 'violet' => 'bg-[#f5f3ff] text-[#6d28d9] [a]:hover:bg-violet-100',
        'red', 'rose', 'danger' => 'bg-[#fff1f2] text-[#e11d48] [a]:hover:bg-rose-100',
        'amber', 'warning' => 'bg-[#fff7ed] text-[#c2410c] [a]:hover:bg-orange-100',
        'teal' => 'bg-[#f0fdfa] text-[#0f766e] [a]:hover:bg-teal-100',
        'indigo' => 'bg-[#eef2ff] text-[#4338ca] [a]:hover:bg-indigo-100',

        default => 'bg-[#f4f4f5] text-[#3f3f46] [a]:hover:bg-hairline',
    };
    $dotClasses = match ($variant) {
        'blue', 'sky', 'info' => 'bg-[#0284c7]',
        'green', 'emerald', 'success' => 'bg-[#059669]',
        'purple', 'violet' => 'bg-[#7c3aed]',
        'red', 'rose', 'danger' => 'bg-[#e11d48]',
        'amber', 'warning' => 'bg-[#f97316]',
        'teal' => 'bg-[#0d9488]',
        'indigo' => 'bg-[#4f46e5]',
        default => 'bg-[#a1a1aa]',
    };
@endphp

<{{ $tag }}
    data-slot="badge"
    data-variant="{{ $variant }}"
    @if($icon) data-icon="{{ $iconPosition }}" @endif
    {{ $attributes->merge(['class' => $baseClasses . ' ' . $modeClasses]) }}
>
    @if($dot)
        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dotClasses }}"></span>
    @endif

    @if($icon && $iconPosition === 'inline-start')
        <x-dynamic-component :component="$icon" />
    @endif

    {{ $slot }}

    @if($icon && $iconPosition === 'inline-end')
        <x-dynamic-component :component="$icon" />
    @endif
</{{ $tag }}>
