@props(['statusId', 'label', 'type' => null, 'design' => 'default'])

@php
    $map = [
        10 => 'bg-neutral-200/60 text-neutral-600 border-neutral-200',
        20 => 'bg-amber-50 border-amber-200 text-amber-600',
        30 => 'bg-sky-50 border-sky-200 text-sky-600',
        40 => 'bg-indigo-50 border-indigo-200 text-indigo-600',
        70 => 'bg-emerald-50 border-emerald-200 text-emerald-600',
        90 => 'bg-rose-50 border-rose-200 text-rose-600',
    ];

    if ($type === 'order') {
        $statusId = match ($statusId) {
            10 => 10,
            20 => 70,
            30 => 90,
            default => $statusId,
        };
    }

    $color = $map[$statusId] ?? 'bg-slate-50 text-slate-600 border-slate-200';

    $iconMap = [
        10 => 'icons.timer-icon',   // nümunə
        20 => 'icons.clock-icon',
        30 => 'icons.info-icon',
        40 => 'icons.sparkle-icon',
        70 => 'icons.check-icon',
        90 => 'icons.x-circle-icon',
    ];
    $iconComponent = $iconMap[$statusId] ?? null;
@endphp

@if($design == 'default')
<span class="text-xs border uppercase font-medium px-3 py-2 rounded-lg w-max {{ $color }}">
    {{ $label }}
</span>
@else
<span class="inline-flex w-max items-center gap-1.5 rounded-full px-2.5 py-1 font-medium text-xs uppercase tracking-wide border {{ $color }}">
        @if($iconComponent)
            <x-dynamic-component :component="$iconComponent" size="w-5 h-5" color="text-current" />
        @endif
        <span>{{ $label }}</span>
</span>
@endif
{{-- text-emerald-700 bg-emerald-50 --}}
