@props(['statusId', 'label', 'type' => null, 'design' => 'default'])

@php
    // Design-system status palette: bg / text / dot, one row per status id.
    $map = [
        10 => 'bg-[#f4f4f5] border-transparent text-[#52525b]',
        20 => 'bg-[#fef3c7] border-transparent text-[#b45309]',
        30 => 'bg-[#e0f2fe] border-transparent text-[#0369a1]',
        40 => 'bg-[#ede9fe] border-transparent text-[#6d28d9]',
        70 => 'bg-[#d1fae5] border-transparent text-[#047857]',
        90 => 'bg-[#ffe4e6] border-transparent text-[#be123c]',
    ];

    $dotMap = [
        10 => 'bg-[#a1a1aa]',
        20 => 'bg-[#f59e0b]',
        30 => 'bg-[#0ea5e9]',
        40 => 'bg-[#8b5cf6]',
        70 => 'bg-[#10b981]',
        90 => 'bg-[#f43f5e]',
    ];

    if ($type === 'order') {
        $statusId = match ($statusId) {
            10 => 10,
            20 => 70,
            30 => 90,
            default => $statusId,
        };
    }

    $color = $map[$statusId] ?? 'bg-[#f4f4f5] border-transparent text-[#52525b]';
    $dot = $dotMap[$statusId] ?? 'bg-[#a1a1aa]';
@endphp

@if($design == 'default')
<span class="inline-flex w-max items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-[11.5px] font-medium tracking-[-0.01em] {{ $color }}">
    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
    {{ $label }}
</span>
@else
<span class="inline-flex w-max items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11.5px] font-semibold tracking-[-0.01em] {{ $color }}">
        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}"></span>
        <span>{{ $label }}</span>
</span>
@endif
